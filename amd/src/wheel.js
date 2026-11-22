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
 * Spinning wheel of names.
 *
 * @module     mod_namewheel/wheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const COLORS = ['#3369e8', '#d50f25', '#eeb211', '#009925', '#8e44ad', '#e67e22', '#16a0a0', '#c2185b'];
const TWO_PI = Math.PI * 2;
// The pointer sits at the top of the wheel (canvas angle 3 * PI / 2).
const POINTER = Math.PI * 1.5;

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
 * Initialise the wheel.
 *
 * @param {Object} config Names and language strings from PHP.
 */
export const init = (config) => {
    const root = document.querySelector('[data-region="namewheel"]');
    if (!root) {
        return;
    }
    const canvas = root.querySelector('[data-region="namewheel-canvas"]');
    const ctx = canvas.getContext('2d');
    const textarea = root.querySelector('[data-region="namewheel-entries"]');
    const countEl = root.querySelector('[data-region="namewheel-count"]');
    const liveEl = root.querySelector('[data-region="namewheel-live"]');
    const hintEl = root.querySelector('[data-region="namewheel-hint"]');
    const resultEl = root.querySelector('[data-region="namewheel-result"]');
    const winnerEl = root.querySelector('[data-region="namewheel-winner"]');
    const hub = root.querySelector('[data-action="spin"]');
    const strings = config.strings;
    const pool = config.names;
    const groups = config.groups || [];
    const selected = new Set(groups.map((g) => g.id));
    const removed = new Set();

    // Names currently allowed on the wheel: typed names always, others when one of their groups is ticked.
    const derive = () => pool
        .filter((e) => !removed.has(e.name) && (e.groups === null || groups.length === 0 ||
            e.groups.some((id) => selected.has(id))))
        .map((e) => e.name);

    let names = derive();
    let rotation = 0;
    let spinning = false;
    let winnerIndex = -1;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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
                colour = COLORS[2];
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
            ctx.fillStyle = '#fff';
            ctx.shadowColor = 'rgba(0,0,0,0.5)';
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
        countEl.textContent = strings.entriescount.replace('{$a}', names.length);
        draw();
    };

    const announce = (text) => {
        liveEl.textContent = '';
        window.setTimeout(() => {
            liveEl.textContent = text;
        }, 50);
    };

    const showResult = (index) => {
        winnerIndex = index;
        winnerEl.textContent = names[index];
        resultEl.hidden = false;
        announce(strings.winner + ' ' + names[index]);
        const btn = resultEl.querySelector('[data-action="keep"]');
        btn.focus();
    };

    const hideResult = () => {
        resultEl.hidden = true;
        winnerIndex = -1;
        canvas.focus();
    };

    const spin = () => {
        if (spinning || names.length === 0 || !resultEl.hidden) {
            return;
        }
        spinning = true;
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

        const frame = (now) => {
            const t = Math.min(1, (now - startTime) / duration);
            const eased = 1 - Math.pow(1 - t, 4);
            rotation = current + delta * eased;
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
            showResult(index);
        };
        window.requestAnimationFrame(frame);
    };

    const shuffle = () => {
        for (let i = names.length - 1; i > 0; i--) {
            const j = Math.floor(random() * (i + 1));
            [names[i], names[j]] = [names[j], names[i]];
        }
        syncText();
    };

    // Events.
    canvas.addEventListener('click', spin);
    canvas.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            spin();
        }
    });
    hub.addEventListener('click', spin);

    root.querySelector('[data-action="keep"]').addEventListener('click', hideResult);
    root.querySelector('[data-action="remove"]').addEventListener('click', () => {
        if (winnerIndex < 0) {
            return;
        }
        const gone = names[winnerIndex];
        removed.add(gone);
        names.splice(winnerIndex, 1);
        syncText();
        hideResult();
        announce(strings.removed.replace('{$a}', gone));
    });
    resultEl.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            hideResult();
        }
    });

    root.querySelector('[data-action="shuffle"]').addEventListener('click', () => {
        if (!spinning) {
            shuffle();
        }
    });
    root.querySelector('[data-action="sort"]').addEventListener('click', () => {
        if (!spinning) {
            names.sort((a, b) => a.localeCompare(b));
            syncText();
        }
    });
    root.querySelector('[data-action="reset"]').addEventListener('click', () => {
        if (!spinning) {
            removed.clear();
            names = derive();
            syncText();
        }
    });

    // Live editing of the list: one name per line.
    textarea.addEventListener('input', () => {
        if (spinning) {
            return;
        }
        names = textarea.value.split(/\r?\n/).map((n) => n.trim()).filter((n) => n !== '');
        countEl.textContent = strings.entriescount.replace('{$a}', names.length);
        draw();
    });

    // Group chooser: tick several groups to combine them on one wheel.
    const groupBox = root.querySelector('[data-region="namewheel-groups"]');
    const applyGroups = () => {
        if (spinning) {
            return;
        }
        names = derive();
        syncText();
        announce(strings.groupchanged.replace('{$a}', names.length));
    };
    if (groupBox) {
        groups.forEach((g) => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';
            const box = document.createElement('input');
            box.type = 'checkbox';
            box.className = 'form-check-input';
            box.id = 'namewheel-group-' + g.id;
            box.value = g.id;
            box.checked = true;
            box.addEventListener('change', () => {
                if (box.checked) {
                    selected.add(g.id);
                } else {
                    selected.delete(g.id);
                }
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
            applyGroups();
        };
        root.querySelector('[data-action="groups-all"]').addEventListener('click', () => setAll(true));
        root.querySelector('[data-action="groups-none"]').addEventListener('click', () => setAll(false));
    }

    window.addEventListener('resize', draw);
    syncText();
};
