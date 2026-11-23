// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spinning wheel of names. The teacher can add a second wheel beside the first, which splits the names at random
 * between the two; each wheel is spun on its own.
 *
 * @module     mod_classroomtoolbox/wheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Pending from 'core/pending';
import {get_strings as getStrings} from 'core/str';

import {LIGHT_TEXT, readableText} from 'mod_classroomtoolbox/colour';
import * as sound from 'mod_classroomtoolbox/sound';
import * as store from 'mod_classroomtoolbox/store';

const TWO_PI = Math.PI * 2;
// The pointer sits at the top of the wheel (canvas angle 3 * PI / 2).
const POINTER = Math.PI * 1.5;
// Soft clicks as wedges pass the pointer, but never faster than this (milliseconds apart).
const MIN_TICK_GAP = 28;

/**
 * Make a dialog modal: everything outside it becomes inert (not focusable, hidden from assistive technology), except
 * live regions so that announcements still work.
 *
 * @param {HTMLElement} dialog The dialog container.
 * @returns {Function} Call to undo.
 */
const makeModal = (dialog) => {
    const inerted = [];
    const ancestors = [];
    for (let node = dialog; node && node !== document.body; node = node.parentElement) {
        ancestors.push(node);
    }
    ancestors.forEach((ancestor) => {
        [...ancestor.parentElement.children].forEach((sibling) => {
            if (sibling !== ancestor && !sibling.inert && sibling.tagName !== 'SCRIPT' && !sibling.matches('[role="status"]')) {
                sibling.inert = true;
                inerted.push(sibling);
            }
        });
    });
    return () => inerted.forEach((el) => {
        el.inert = false;
    });
};

/**
 * Random float in [0, 1) from the browser's crypto source.
 *
 * @returns {number}
 */
const random = () => {
    const buf = new Uint32Array(1);
    window.crypto.getRandomValues(buf);
    return buf[0] / 4294967296;
};

/**
 * Shuffle a list in place with the crypto random source.
 *
 * @param {Array} list The list.
 * @returns {Array} The same list.
 */
const shuffleInPlace = (list) => {
    for (let i = list.length - 1; i > 0; i--) {
        const j = Math.floor(random() * (i + 1));
        [list[i], list[j]] = [list[j], list[i]];
    }
    return list;
};

/**
 * Initialise the wheel.
 *
 * @param {Object} config Names and language strings from PHP.
 */
export const init = (config) => {
    const root = document.querySelector('[data-region="wheel"]');
    if (!root) {
        return;
    }
    const strings = config.strings;
    const COLORS = config.scheme.colors;
    const pool = config.names;
    const groups = config.groups || [];
    const selected = new Set(groups.map((g) => g.id));
    // Names taken off the wheels by the teacher; shared, because a name is only ever on one wheel.
    const removed = new Set();
    // The groups ticked last time (only groups that still exist), so the same split is there after a reload.
    const savedGroups = store.load('wheel').groups;
    if (Array.isArray(savedGroups) && groups.length > 0) {
        selected.clear();
        groups.filter((g) => savedGroups.includes(g.id)).forEach((g) => selected.add(g.id));
    }
    const saveSettings = () => store.save('wheel', {groups: [...selected]});
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Names currently allowed on the wheels: typed names always, others when one of their groups is ticked.
    const derive = () => pool
        .filter((e) => !removed.has(e.name) && (e.groups === null || groups.length === 0 ||
            e.groups.some((id) => selected.has(id))))
        .map((e) => e.name);

    // The winner dialog is shared by the wheels; it belongs to whichever wheel just stopped.
    const resultEl = root.querySelector('[data-region="wheel-result"]');
    const winnerEl = root.querySelector('[data-region="wheel-winner"]');
    const labelEl = root.querySelector('[data-region="wheel-winnerlabel"]');
    const keepButton = root.querySelector('[data-action="keep"]');
    const removeButton = root.querySelector('[data-action="remove"]');
    const soundSwitch = root.querySelector('[data-action="wheel-sound"]');
    const stageCol = root.querySelector('[data-region="wheel-stagecol"]');
    const namesCard = root.querySelector('[data-region="wheel-namescard"]');
    const addButton = root.querySelector('[data-action="wheel-add"]');
    const resplitButton = root.querySelector('[data-action="wheel-resplit"]');
    const removeSecondButton = root.querySelector('[data-action="wheel-removesecond"]');
    const units = [];
    let activeUnit = null;
    let releaseModal = null;
    let hideResult;
    // Words for the second wheel are fetched when it is first added, to keep the wheel's start-up data small.
    const EXTRA_KEYS = ['title', 'picked', 'added', 'removedsecond', 'resplitdone'];
    let extra = null;
    const loadExtra = async() => {
        if (extra) {
            return;
        }
        const list = await getStrings(EXTRA_KEYS.map((key) => ({key: 'wheel_' + key, component: 'mod_classroomtoolbox'})));
        extra = {};
        EXTRA_KEYS.forEach((key, i) => {
            extra[key] = list[i];
        });
    };
    const anySpinning = () => units.some((unit) => unit.isSpinning());

    // While the winner dialog is open, Tab stays inside it and Escape closes it, wherever focus happens to be.
    const onDialogKey = (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            hideResult();
            return;
        }
        if (e.key !== 'Tab') {
            return;
        }
        const buttons = [...resultEl.querySelectorAll('button:not([disabled]):not([hidden])')];
        const first = buttons[0];
        const last = buttons[buttons.length - 1];
        const active = document.activeElement;
        if (!resultEl.contains(active)) {
            e.preventDefault();
            first.focus();
        } else if (e.shiftKey && active === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && active === last) {
            e.preventDefault();
            first.focus();
        }
    };

    const showResult = (unit, index) => {
        activeUnit = unit;
        unit.setWinner(index);
        const label = units.length > 1 ? strings.winner + ' (' + unit.title() + ')' : strings.winner;
        labelEl.textContent = label;
        winnerEl.textContent = unit.getNames()[index];
        resultEl.hidden = false;
        releaseModal = makeModal(resultEl);
        document.addEventListener('keydown', onDialogKey);
        sound.chime();
        unit.announce(label + ' ' + winnerEl.textContent);
        keepButton.focus();
    };

    hideResult = () => {
        document.removeEventListener('keydown', onDialogKey);
        if (releaseModal) {
            releaseModal();
            releaseModal = null;
        }
        resultEl.hidden = true;
        if (activeUnit) {
            activeUnit.setWinner(-1);
            activeUnit.focus();
        }
    };

    /**
     * Make one wheel: its picture, its list of names and its spin. Everything here belongs to that one wheel.
     *
     * @param {Object} els The elements of this wheel: unit, canvas, hub, textarea, count, and its names card.
     * @returns {Object} What the rest of the page may do with the wheel.
     */
    const createUnit = (els) => {
        const {unit: unitEl, canvas, hub, textarea, countEl, card} = els;
        const ctx = canvas.getContext('2d');
        const hintEl = unitEl.querySelector('[data-region="wheel-hint"]');
        const pickedEl = unitEl.querySelector('[data-region="wheel-picked"]');
        const liveEl = unitEl.querySelector('[data-region="wheel-live"]');
        const titleEl = unitEl.querySelector('[data-region="wheel-title"]');
        const entriesLabel = card.querySelector('label');
        const hubText = hub.textContent.trim();
        const entriesText = entriesLabel.textContent.trim();
        let names = [];
        let rotation = 0;
        let spinning = false;
        let winnerIndex = -1;
        let title = '';

        const draw = () => {
            const size = canvas.clientWidth || 600;
            const dpr = window.devicePixelRatio || 1;
            if (canvas.width !== Math.round(size * dpr)) {
                canvas.width = Math.round(size * dpr);
                canvas.height = Math.round(size * dpr);
            }
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.clearRect(0, 0, size, size);
            const cx = size / 2;
            const r = size / 2 - 4;

            if (names.length === 0) {
                ctx.beginPath();
                ctx.arc(cx, cx, r, 0, TWO_PI);
                ctx.fillStyle = '#e9ecef';
                ctx.fill();
                ctx.fillStyle = '#495057';
                ctx.font = '600 ' + Math.round(size / 24) + 'px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(strings.empty, cx, cx, r * 1.6);
                return;
            }

            const seg = TWO_PI / names.length;
            const fontSize = Math.max(10, Math.min(size / 14, (r * seg) * 0.55));
            ctx.font = '600 ' + Math.round(fontSize) + 'px sans-serif';
            ctx.textBaseline = 'middle';

            names.forEach((name, i) => {
                const start = rotation + i * seg;
                ctx.beginPath();
                ctx.moveTo(cx, cx);
                ctx.arc(cx, cx, r, start, start + seg);
                ctx.closePath();
                // Avoid two equal neighbours (first and last) when the count is not a multiple of the palette.
                let colour = COLORS[i % COLORS.length];
                if (i === names.length - 1 && i % COLORS.length === 0 && names.length > 1) {
                    colour = COLORS[2 % COLORS.length];
                }
                ctx.fillStyle = colour;
                ctx.fill();
                ctx.lineWidth = 1;
                ctx.strokeStyle = 'rgba(255,255,255,0.8)';
                ctx.stroke();

                ctx.save();
                ctx.translate(cx, cx);
                ctx.rotate(start + seg / 2);
                ctx.textAlign = 'right';
                const textColour = readableText(colour);
                ctx.fillStyle = textColour;
                ctx.shadowColor = textColour === LIGHT_TEXT ? 'rgba(0,0,0,0.5)' : 'rgba(255,255,255,0.5)';
                ctx.shadowBlur = 2;
                ctx.fillText(name, r - 14, 0, r * 0.62);
                ctx.restore();
            });

            ctx.beginPath();
            ctx.arc(cx, cx, r, 0, TWO_PI);
            ctx.lineWidth = 4;
            ctx.strokeStyle = '#fff';
            ctx.stroke();
        };

        const syncText = () => {
            textarea.value = names.join('\n');
            countEl.textContent = strings.entriescount.replace('{$a}', () => names.length);
            draw();
        };

        const announce = (text) => {
            liveEl.textContent = '';
            window.setTimeout(() => {
                liveEl.textContent = text;
            }, 50);
        };

        const spin = () => {
            if (spinning || anySpinning() || names.length === 0 || !resultEl.hidden) {
                return;
            }
            spinning = true;
            sound.prepare(); // A click or key press has just started the spin, so the browser allows sound now.
            // Tell Behat (and other automation) that async work is running until the wheel stops.
            const pending = new Pending('mod_classroomtoolbox/wheel:spin');
            hub.disabled = true;
            hintEl.textContent = strings.spinning;

            // Choose the winner first, then rotate so the pointer ends up inside that segment.
            const index = Math.floor(random() * names.length);
            const seg = TWO_PI / names.length;
            const offset = 0.1 + random() * 0.8; // Stay away from the segment edges.
            const target = POINTER - (index + offset) * seg;
            const current = rotation;
            const forward = (((target - current) % TWO_PI) + TWO_PI) % TWO_PI;
            const extraTurns = reduceMotion ? 1 : 5 + Math.floor(random() * 3);
            const delta = forward + extraTurns * TWO_PI;
            const duration = reduceMotion ? 800 : 5000 + random() * 1500;
            const startTime = performance.now();

            // One soft tick each time a wedge boundary passes the pointer, slowing down with the wheel.
            const slotAt = (angle) => Math.floor(((((POINTER - angle) % TWO_PI) + TWO_PI) % TWO_PI) / seg);
            let lastSlot = slotAt(current);
            let lastTick = 0;

            const frame = (now) => {
                const t = Math.min(1, (now - startTime) / duration);
                const eased = 1 - Math.pow(1 - t, 4);
                rotation = current + delta * eased;
                const slot = slotAt(rotation);
                if (slot !== lastSlot) {
                    lastSlot = slot;
                    if (now - lastTick >= MIN_TICK_GAP) {
                        sound.tick();
                        lastTick = now;
                    }
                }
                draw();
                if (t < 1) {
                    window.requestAnimationFrame(frame);
                    return;
                }
                rotation = target;
                rotation = ((rotation % TWO_PI) + TWO_PI) % TWO_PI;
                spinning = false;
                hub.disabled = false;
                hintEl.textContent = strings.clickhere;
                draw();
                showResult(api, index);
                pending.resolve();
            };
            window.requestAnimationFrame(frame);
        };

        canvas.addEventListener('click', spin);
        canvas.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                spin();
            }
        });
        hub.addEventListener('click', spin);

        card.querySelector('[data-action="shuffle"]').addEventListener('click', () => {
            if (!spinning) {
                shuffleInPlace(names);
                syncText();
            }
        });
        card.querySelector('[data-action="sort"]').addEventListener('click', () => {
            if (!spinning) {
                names.sort((a, b) => a.localeCompare(b));
                syncText();
            }
        });
        // Live editing of the list: one name per line.
        textarea.addEventListener('input', () => {
            if (spinning) {
                return;
            }
            names = textarea.value.split(/\r?\n/).map((n) => n.trim()).filter((n) => n !== '');
            countEl.textContent = strings.entriescount.replace('{$a}', () => names.length);
            draw();
        });
        window.addEventListener('resize', draw);

        const api = {
            el: unitEl,
            card,
            getNames: () => names,
            setNames: (list) => {
                names = [...list];
                pickedEl.hidden = true;
                syncText();
            },
            isSpinning: () => spinning,
            focus: () => canvas.focus(),
            announce,
            draw,
            title: () => title,
            setWinner: (index) => {
                winnerIndex = index;
            },
            winnerIndex: () => winnerIndex,
            // Take the name the wheel stopped on off the wheel.
            removeWinner: () => {
                const gone = names[winnerIndex];
                removed.add(gone);
                names.splice(winnerIndex, 1);
                syncText();
                return gone;
            },
            // Show which name this wheel last picked.
            showPicked: (name) => {
                if (units.length < 2) {
                    return;
                }
                pickedEl.textContent = extra ? extra.picked.replace('{$a}', () => name) : name;
                pickedEl.hidden = false;
            },
            // Name the wheel ("Wheel 1") when there are two, or go back to the plain wording when there is one.
            setTitle: (text) => {
                title = text;
                titleEl.textContent = text;
                titleEl.hidden = text === '';
                hub.setAttribute('aria-label', text ? hubText + ' (' + text + ')' : hubText);
                canvas.setAttribute('aria-label', text ? strings.clickhere + ' (' + text + ')' : strings.clickhere);
                entriesLabel.textContent = text ? entriesText + ' (' + text + ')' : entriesText;
            },
            destroy: () => window.removeEventListener('resize', draw),
        };
        return api;
    };

    const firstUnit = createUnit({
        unit: root.querySelector('[data-region="wheel-unit"]'),
        canvas: root.querySelector('[data-region="wheel-canvas"]'),
        hub: root.querySelector('[data-action="spin"]'),
        textarea: root.querySelector('[data-region="wheel-entries"]'),
        countEl: root.querySelector('[data-region="wheel-count"]'),
        card: namesCard,
    });
    units.push(firstUnit);
    firstUnit.setNames(derive());

    // Spread names over the wheels in a random order, as evenly as possible; with an odd number one wheel, picked
    // at random, gets the extra name.
    const split = (list) => {
        const mixed = shuffleInPlace([...list]);
        const larger = Math.ceil(mixed.length / 2);
        const firstGets = mixed.length % 2 === 0 || random() < 0.5 ? larger : mixed.length - larger;
        units[0].setNames(mixed.slice(0, firstGets));
        units[1].setNames(mixed.slice(firstGets));
    };

    // Names to share out again: everyone still on a wheel (so names the teacher removed stay removed).
    const everyone = () => units.flatMap((unit) => unit.getNames());

    const addSecondWheel = async() => {
        if (units.length > 1 || anySpinning() || !resultEl.hidden) {
            return;
        }
        const pending = new Pending('mod_classroomtoolbox/wheel:morestrings');
        try {
            await loadExtra();
        } finally {
            pending.resolve();
        }
        if (units.length > 1) {
            return;
        }
        // The second wheel is a copy of the first one's picture, placed beside it, with its own names box beneath
        // the first one's.
        const unitCopy = units[0].el.cloneNode(true);
        const col = document.createElement('div');
        col.className = 'col-lg-4 mb-3';
        col.dataset.region = 'wheel-stagecol-second';
        col.append(unitCopy);
        stageCol.after(col);
        stageCol.classList.replace('col-lg-8', 'col-lg-4');

        const cardCopy = namesCard.cloneNode(true);
        cardCopy.classList.add('mt-3');
        cardCopy.dataset.region = 'wheel-namescard-second';
        const copyArea = cardCopy.querySelector('textarea');
        const copyLabel = cardCopy.querySelector('label');
        const suffix = Math.random().toString(36).slice(2, 8);
        copyArea.id = 'wheel-entries-second-' + suffix;
        copyArea.value = '';
        copyLabel.htmlFor = copyArea.id;
        // One "Reset wheel" button is enough: it belongs to the first card and resets both wheels.
        cardCopy.querySelector('[data-action="reset"]').remove();
        namesCard.after(cardCopy);

        const second = createUnit({
            unit: unitCopy,
            canvas: unitCopy.querySelector('[data-region="wheel-canvas"]'),
            hub: unitCopy.querySelector('[data-action="spin"]'),
            textarea: copyArea,
            countEl: cardCopy.querySelector('[data-region="wheel-count"]'),
            card: cardCopy,
        });
        units.push(second);
        units[0].setTitle(extra.title.replace('{$a}', () => 1));
        second.setTitle(extra.title.replace('{$a}', () => 2));
        split(everyone());
        addButton.hidden = true;
        resplitButton.hidden = false;
        removeSecondButton.hidden = false;
        second.announce(extra.added);
        window.dispatchEvent(new Event('resize'));
    };

    const removeSecondWheel = () => {
        if (units.length < 2 || anySpinning() || !resultEl.hidden) {
            return;
        }
        const second = units.pop();
        const all = everyone().concat(second.getNames());
        second.destroy();
        root.querySelector('[data-region="wheel-stagecol-second"]').remove();
        second.card.remove();
        stageCol.classList.replace('col-lg-4', 'col-lg-8');
        units[0].setTitle('');
        units[0].setNames(all);
        addButton.hidden = false;
        resplitButton.hidden = true;
        removeSecondButton.hidden = true;
        units[0].announce(extra.removedsecond);
        window.dispatchEvent(new Event('resize'));
    };

    addButton.addEventListener('click', addSecondWheel);
    removeSecondButton.addEventListener('click', removeSecondWheel);
    resplitButton.addEventListener('click', () => {
        if (units.length > 1 && !anySpinning() && resultEl.hidden) {
            split(everyone());
            units[0].announce(extra.resplitdone);
        }
    });

    // Sound on or off, remembered in this browser and shared with the timer.
    if (soundSwitch) {
        soundSwitch.checked = sound.isEnabled();
        soundSwitch.addEventListener('change', () => {
            sound.setEnabled(soundSwitch.checked);
            sound.prepare();
        });
    }

    // The winner dialog: keep the name on its wheel, or take it off.
    keepButton.addEventListener('click', () => {
        if (activeUnit) {
            activeUnit.showPicked(winnerEl.textContent);
        }
        hideResult();
    });
    removeButton.addEventListener('click', () => {
        if (!activeUnit || activeUnit.winnerIndex() < 0) {
            return;
        }
        const unit = activeUnit;
        const gone = unit.removeWinner();
        hideResult();
        unit.showPicked(gone);
        unit.announce(strings.removed.replace('{$a}', () => gone));
    });

    root.querySelector('[data-action="reset"]').addEventListener('click', () => {
        if (anySpinning()) {
            return;
        }
        removed.clear();
        if (units.length > 1) {
            split(derive());
        } else {
            units[0].setNames(derive());
        }
    });

    // Group chooser: tick several groups to combine them; with two wheels they are shared out again.
    const groupBox = root.querySelector('[data-region="wheel-groups"]');
    const applyGroups = () => {
        if (anySpinning()) {
            return;
        }
        const names = derive();
        if (units.length > 1) {
            split(names);
        } else {
            units[0].setNames(names);
        }
        units[0].announce(strings.groupchanged.replace('{$a}', () => names.length));
    };
    if (groupBox) {
        groups.forEach((g) => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';
            const box = document.createElement('input');
            box.type = 'checkbox';
            box.className = 'form-check-input';
            box.id = 'wheel-group-' + g.id;
            box.value = g.id;
            box.checked = selected.has(g.id);
            box.addEventListener('change', () => {
                if (box.checked) {
                    selected.add(g.id);
                } else {
                    selected.delete(g.id);
                }
                saveSettings();
                applyGroups();
            });
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = box.id;
            label.textContent = g.name + ' (' + g.count + ')';
            wrap.append(box, label);
            groupBox.append(wrap);
        });
        const setAll = (on) => {
            groups.forEach((g) => {
                if (on) {
                    selected.add(g.id);
                } else {
                    selected.delete(g.id);
                }
            });
            groupBox.querySelectorAll('input').forEach((b) => {
                b.checked = on;
            });
            saveSettings();
            applyGroups();
        };
        root.querySelector('[data-action="groups-all"]').addEventListener('click', () => setAll(true));
        root.querySelector('[data-action="groups-none"]').addEventListener('click', () => setAll(false));
    }
};
