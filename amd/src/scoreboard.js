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
 * Scoreboard: teachers add teams or names and give points; everyone sees a ranked, auto-refreshing board.
 *
 * @module     mod_classroomtoolbox/scoreboard
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Pending from 'core/pending';

const POLL_MS = 5000;
const MAX_POLL_MS = 30000;
const MAX_FAILED_POLLS = 3;

/**
 * Ranks with ties sharing a place (1, 1, 3).
 *
 * @param {Object[]} entries Entries sorted by points, highest first.
 * @returns {number[]} Rank for each entry.
 */
export const ranks = (entries) => entries.map((entry) => 1 + entries.filter((other) => other.score > entry.score).length);

/**
 * Initialise the scoreboard.
 *
 * @param {Object} config Entries, permissions and language strings from PHP.
 */
export const init = (config) => {
    const root = document.querySelector('[data-region="scoreboard"]');
    if (!root) {
        return;
    }
    const strings = config.strings;
    const canManage = config.canmanage;
    const list = root.querySelector('[data-region="score-list"]');
    const emptyEl = root.querySelector('[data-region="score-empty"]');
    const liveEl = root.querySelector('[data-region="score-live"]');
    const stepInput = root.querySelector('[data-region="score-step"]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fill = (text, value) => text.replace('{$a}', () => value);

    let entries = config.entries;
    let busy = false;
    let failedPolls = 0;
    let delay = POLL_MS;
    let timer = null;

    const step = () => Math.max(1, Math.min(100, parseInt(stepInput ? stepInput.value : '1', 10) || 1));

    const announce = (text) => {
        liveEl.textContent = '';
        window.setTimeout(() => {
            liveEl.textContent = text;
        }, 50);
    };

    const button = (className, text, label, action, id) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = className;
        el.textContent = text;
        el.setAttribute('aria-label', label);
        el.dataset.action = action;
        el.dataset.id = String(id);
        return el;
    };

    let call;

    const buildRow = (entry, rank) => {
        const item = document.createElement('li');
        item.className = 'mod_classroomtoolbox-score-row';
        item.dataset.id = String(entry.id);

        const rankEl = document.createElement('span');
        rankEl.className = 'mod_classroomtoolbox-score-rank';
        // The digit is for the eyes; the hidden text says "Place 1" to a screen reader.
        const digit = document.createElement('span');
        digit.setAttribute('aria-hidden', 'true');
        digit.textContent = String(rank);
        const spoken = document.createElement('span');
        spoken.className = 'visually-hidden';
        spoken.textContent = fill(strings.score_rank, rank);
        rankEl.append(digit, spoken);

        if (!canManage) {
            const label = document.createElement('span');
            label.className = 'mod_classroomtoolbox-score-label';
            label.textContent = entry.label;
            const points = document.createElement('span');
            points.className = 'mod_classroomtoolbox-score-points';
            points.textContent = String(entry.score);
            item.append(rankEl, label, points);
            return item;
        }

        const label = document.createElement('input');
        label.type = 'text';
        label.className = 'form-control mod_classroomtoolbox-score-label';
        label.value = entry.label;
        label.maxLength = config.maxlabel;
        label.setAttribute('aria-label', fill(strings.score_namefor, rank));
        label.dataset.id = String(entry.id);
        label.dataset.field = 'label';

        const points = document.createElement('input');
        points.type = 'number';
        points.className = 'form-control mod_classroomtoolbox-score-points-input';
        points.value = String(entry.score);
        points.setAttribute('aria-label', fill(strings.score_points, entry.label));
        points.dataset.id = String(entry.id);
        points.dataset.field = 'points';

        // Send only the field that changed, so renaming never overwrites points another teacher just gave.
        label.addEventListener('change', () => {
            call('mod_classroomtoolbox_update_score', {id: entry.id, label: label.value});
        });
        points.addEventListener('change', () => {
            const value = parseInt(points.value, 10);
            if (Number.isNaN(value)) {
                points.value = String(entry.score);
                return;
            }
            call('mod_classroomtoolbox_update_score', {id: entry.id, score: value});
        });

        const down = button('btn btn-secondary', '\u2212', fill(strings.score_down, entry.label), 'score-down', entry.id);
        const up = button('btn btn-primary', '+', fill(strings.score_up, entry.label), 'score-up', entry.id);
        const remove = button('btn btn-outline-danger btn-sm', '\u00d7', fill(strings.score_delete, entry.label),
            'score-delete', entry.id);
        down.addEventListener('click', () => call('mod_classroomtoolbox_change_score', {id: entry.id, delta: -step()}, entry));
        up.addEventListener('click', () => call('mod_classroomtoolbox_change_score', {id: entry.id, delta: step()}, entry));
        remove.addEventListener('click', () => call('mod_classroomtoolbox_delete_score', {id: entry.id}));

        item.append(rankEl, label, down, points, up, remove);
        return item;
    };

    const render = () => {
        const focused = document.activeElement;
        const refocus = focused && list.contains(focused)
            ? {id: focused.dataset.id, action: focused.dataset.action, field: focused.dataset.field}
            : null;
        const before = new Map([...list.children].map((li) => [li.dataset.id, li.getBoundingClientRect().top]));

        list.textContent = '';
        const placed = ranks(entries);
        entries.forEach((entry, i) => list.append(buildRow(entry, placed[i])));
        let emptyText = '';
        if (entries.length === 0) {
            emptyText = canManage ? strings.score_empty : strings.score_emptyviewer;
        }
        emptyEl.textContent = emptyText;

        if (!reduceMotion) {
            [...list.children].forEach((li) => {
                const old = before.get(li.dataset.id);
                if (old === undefined) {
                    return;
                }
                const shift = old - li.getBoundingClientRect().top;
                if (shift) {
                    li.style.transition = 'none';
                    li.style.transform = 'translateY(' + shift + 'px)';
                    window.requestAnimationFrame(() => {
                        li.style.transition = 'transform 0.4s ease';
                        li.style.transform = '';
                    });
                }
            });
        }
        if (refocus) {
            const selector = refocus.action
                ? '[data-id="' + refocus.id + '"][data-action="' + refocus.action + '"]'
                : '[data-id="' + refocus.id + '"][data-field="' + refocus.field + '"]';
            const target = list.querySelector(selector);
            if (target) {
                target.focus();
            }
        }
        root.querySelectorAll('[data-region="score-add-form"] button, [data-region="score-add-form"] input').forEach((el) => {
            el.disabled = entries.length >= config.maxentries;
        });
    };

    // Every teacher action is one AJAX call that returns the whole fresh list.
    call = async(methodname, args, entry = null) => {
        const pending = new Pending('mod_classroomtoolbox/scoreboard:' + methodname);
        busy = true;
        try {
            entries = await Ajax.call([{methodname, args: {toolboxid: config.toolboxid, ...args}}])[0];
            delay = POLL_MS;
            render();
            if (entry) {
                const now = entries.find((e) => e.id === entry.id);
                if (now) {
                    announce(strings.score_announce.replace('{$a->label}', () => now.label)
                        .replace('{$a->points}', () => now.score));
                }
            }
        } catch (error) {
            Notification.exception(error);
        } finally {
            busy = false;
            pending.resolve();
        }
    };

    if (canManage) {
        const form = root.querySelector('[data-region="score-add-form"]');
        const input = root.querySelector('[data-region="score-add-input"]');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const labels = input.value.split(/[,\n]/).map((s) => s.trim()).filter((s) => s !== '');
            if (labels.length) {
                input.value = '';
                call('mod_classroomtoolbox_add_scores', {labels});
            }
        });
        const addGroups = root.querySelector('[data-action="score-add-groups"]');
        if (addGroups) {
            addGroups.addEventListener('click', () => call('mod_classroomtoolbox_add_scores', {labels: config.groups}));
        }
        const addParticipants = root.querySelector('[data-action="score-add-participants"]');
        if (addParticipants) {
            addParticipants.addEventListener('click', () => {
                call('mod_classroomtoolbox_add_scores', {labels: config.participants});
            });
        }
        root.querySelector('[data-action="score-reset"]').addEventListener('click', async() => {
            try {
                await Notification.saveCancelPromise(
                    strings.score_resettitle,
                    strings.score_resetquestion,
                    strings.score_resetconfirm
                );
            } catch (e) {
                return; // Cancelled.
            }
            call('mod_classroomtoolbox_reset_scores', {});
        });
    }

    // Keep the board fresh for everyone, including a second teacher. It backs off while nothing changes, and is
    // skipped while the page or tab is hidden, a teacher is typing, or the viewer has paused updates.
    const pauseBox = root.querySelector('[data-action="score-pause"]');
    let poll;
    const schedule = () => {
        window.clearTimeout(timer);
        if (failedPolls < MAX_FAILED_POLLS) {
            timer = window.setTimeout(poll, delay);
        }
    };
    poll = async() => {
        const panel = root.closest('[data-region="tool-panel"]');
        // Typing in a text or number box pauses the refresh; a focused switch or button does not.
        const editing = canManage && root.contains(document.activeElement)
            && document.activeElement.matches('input:not([type="checkbox"])');
        if (busy || document.hidden || (panel && panel.hidden) || editing || (pauseBox && pauseBox.checked)) {
            schedule();
            return;
        }
        try {
            const fresh = await Ajax.call([{
                methodname: 'mod_classroomtoolbox_get_scores',
                args: {toolboxid: config.toolboxid},
            }])[0];
            failedPolls = 0;
            if (JSON.stringify(fresh) !== JSON.stringify(entries) && !busy) {
                entries = fresh;
                render();
                delay = POLL_MS;
            } else {
                delay = Math.min(delay * 1.5, MAX_POLL_MS);
            }
        } catch (e) {
            failedPolls++; // For example the session ended: stop asking after a few tries.
        }
        schedule();
    };
    const pollNow = () => {
        delay = POLL_MS;
        window.clearTimeout(timer);
        poll();
    };
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            pollNow();
        }
    });
    if (pauseBox) {
        pauseBox.addEventListener('change', () => {
            if (!pauseBox.checked) {
                pollNow();
            }
        });
    }
    schedule();

    render();
};
