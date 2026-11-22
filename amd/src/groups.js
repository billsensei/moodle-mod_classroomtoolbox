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
 * Random group generator.
 *
 * @module     mod_classroomtoolbox/groups
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
 * Shuffle the names and deal them into groups whose sizes differ by at most one.
 *
 * @param {string[]} names The people.
 * @param {string} mode 'count' (amount is the number of groups) or 'size' (amount is the people per group).
 * @param {number} amount A whole number of at least 1.
 * @param {Function} rand Random source returning a float in [0, 1).
 * @returns {string[][]} The groups (never more groups than people).
 */
export const partition = (names, mode, amount, rand = random) => {
    const list = [...names];
    for (let i = list.length - 1; i > 0; i--) {
        const j = Math.floor(rand() * (i + 1));
        [list[i], list[j]] = [list[j], list[i]];
    }
    if (list.length === 0) {
        return [];
    }
    const per = Math.max(1, Math.floor(amount) || 1);
    const count = mode === 'size' ? Math.ceil(list.length / per) : Math.min(per, list.length);
    const groups = Array.from({length: count}, () => []);
    list.forEach((name, i) => {
        groups[i % count].push(name);
    });
    return groups;
};

/**
 * Replace the {$a} placeholder in a language string.
 *
 * @param {string} text Language string.
 * @param {string|number} value Value to insert.
 * @returns {string}
 */
const fill = (text, value) => text.replace('{$a}', () => value);

/**
 * Initialise the group generator.
 *
 * @param {Object} config Names, groups and language strings from PHP.
 */
export const init = (config) => {
    const root = document.querySelector('[data-region="groups"]');
    if (!root) {
        return;
    }
    const strings = config.strings;
    const pool = config.names;
    const groups = config.groups || [];
    const selected = new Set(groups.map((g) => g.id));
    const textarea = root.querySelector('[data-region="groupgen-names"]');
    const countEl = root.querySelector('[data-region="groupgen-count"]');
    const amountEl = root.querySelector('[data-region="groupgen-amount"]');
    const resultEl = root.querySelector('[data-region="groupgen-result"]');
    const liveEl = root.querySelector('[data-region="groupgen-live"]');
    let lastGroups = [];

    const derive = () => pool
        .filter((e) => e.groups === null || groups.length === 0 || e.groups.some((id) => selected.has(id)))
        .map((e) => e.name);

    const currentNames = () => textarea.value.split(/\r?\n/).map((n) => n.trim()).filter((n) => n !== '');

    const updateCount = () => {
        countEl.textContent = fill(strings.groupgen_peoplecount, currentNames().length);
    };

    const announce = (text) => {
        liveEl.textContent = '';
        window.setTimeout(() => {
            liveEl.textContent = text;
        }, 50);
    };

    const reset = () => {
        textarea.value = derive().join('\n');
        updateCount();
    };

    const render = () => {
        resultEl.textContent = '';
        lastGroups.forEach((members, i) => {
            const card = document.createElement('div');
            card.className = 'card mod_classroomtoolbox-groupgen-card';
            card.style.animationDelay = (i * 0.12) + 's';
            const head = document.createElement('div');
            head.className = 'card-header fw-bold';
            head.textContent = fill(strings.groupgen_group, i + 1) + ' (' + members.length + ')';
            const list = document.createElement('ul');
            list.className = 'list-group list-group-flush';
            members.forEach((name) => {
                const item = document.createElement('li');
                item.className = 'list-group-item';
                item.textContent = name;
                list.append(item);
            });
            card.append(head, list);
            resultEl.append(card);
        });
    };

    const generate = () => {
        const names = currentNames();
        if (names.length === 0) {
            lastGroups = [];
            resultEl.textContent = '';
            const note = document.createElement('p');
            note.className = 'text-muted';
            note.textContent = strings.groupgen_nonames;
            resultEl.append(note);
            announce(strings.groupgen_nonames);
            return;
        }
        const mode = root.querySelector('input[type="radio"]:checked').value;
        lastGroups = partition(names, mode, parseInt(amountEl.value, 10));
        render();
        announce(fill(strings.groupgen_generated, lastGroups.length));
    };

    const copy = async() => {
        const text = lastGroups
            .map((members, i) => fill(strings.groupgen_group, i + 1) + '\n' + members.join('\n'))
            .join('\n\n');
        try {
            await navigator.clipboard.writeText(text);
            announce(strings.groupgen_copied);
        } catch (e) {
            announce(strings.groupgen_copyfailed);
        }
    };

    // Group chooser: tick several groups to limit who is split up.
    const filterBox = root.querySelector('[data-region="groupgen-filter"]');
    if (filterBox) {
        groups.forEach((g) => {
            const wrap = document.createElement('div');
            wrap.className = 'form-check';
            const box = document.createElement('input');
            box.type = 'checkbox';
            box.className = 'form-check-input';
            box.id = 'groupgen-group-' + g.id + '-' + Math.random().toString(36).slice(2, 8);
            box.checked = true;
            box.addEventListener('change', () => {
                if (box.checked) {
                    selected.add(g.id);
                } else {
                    selected.delete(g.id);
                }
                reset();
            });
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = box.id;
            label.textContent = g.name + ' (' + g.count + ')';
            wrap.append(box, label);
            filterBox.append(wrap);
        });
        const setAll = (on) => {
            groups.forEach((g) => {
                if (on) {
                    selected.add(g.id);
                } else {
                    selected.delete(g.id);
                }
            });
            filterBox.querySelectorAll('input').forEach((b) => {
                b.checked = on;
            });
            reset();
        };
        root.querySelector('[data-action="groupgen-all"]').addEventListener('click', () => setAll(true));
        root.querySelector('[data-action="groupgen-none"]').addEventListener('click', () => setAll(false));
    }

    textarea.addEventListener('input', updateCount);
    root.querySelector('[data-action="groupgen-generate"]').addEventListener('click', generate);
    root.querySelector('[data-action="groupgen-copy"]').addEventListener('click', copy);
    root.querySelector('[data-action="groupgen-reset"]').addEventListener('click', reset);
    reset();
};
