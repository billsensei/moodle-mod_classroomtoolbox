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
 * Countdown timers with presets, a sound at the end and several timers at once.
 *
 * @module     mod_classroomtoolbox/timer
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as sound from 'mod_classroomtoolbox/sound';

const MAX_TIMERS = 4;

/**
 * Format milliseconds left as mm:ss (or h:mm:ss from one hour), rounding up so 0:00 means finished.
 *
 * @param {number} ms Milliseconds.
 * @returns {string}
 */
export const format = (ms) => {
    const pad = (n) => String(n).padStart(2, '0');
    const total = Math.ceil(ms / 1000);
    const seconds = total % 60;
    const minutes = Math.floor(total / 60) % 60;
    const hours = Math.floor(total / 3600);
    return (hours > 0 ? hours + ':' + pad(minutes) : pad(minutes)) + ':' + pad(seconds);
};

/**
 * Initialise the timers.
 *
 * @param {Object} config Presets (minutes) and language strings from PHP.
 */
export const init = (config) => {
    const root = document.querySelector('[data-region="timer"]');
    if (!root) {
        return;
    }
    const strings = config.strings;
    const presets = config.presets;
    const list = root.querySelector('[data-region="timer-list"]');
    const addButton = root.querySelector('[data-action="timer-add"]');
    const soundSwitch = root.querySelector('[data-action="timer-sound"]');
    // The sound preference is shared with the other tools and remembered in this browser.
    soundSwitch.checked = sound.isEnabled();
    soundSwitch.addEventListener('change', () => {
        sound.setEnabled(soundSwitch.checked);
        sound.prepare();
    });
    const liveEl = root.querySelector('[data-region="timer-live"]');
    const fill = (text, value) => text.replace('{$a}', () => value);
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let created = 0;
    let running = 0;
    let wakeLock = null;

    // Ask the browser to keep the screen on while a timer runs (projector use). Best effort only.
    const updateWakeLock = async() => {
        try {
            if (running > 0 && !wakeLock && navigator.wakeLock) {
                wakeLock = await navigator.wakeLock.request('screen');
                wakeLock.addEventListener('release', () => {
                    wakeLock = null;
                });
            } else if (running === 0 && wakeLock) {
                await wakeLock.release();
                wakeLock = null;
            }
        } catch (e) {
            wakeLock = null;
        }
    };

    const presetLabel = (minutes) => minutes < 1
        ? fill(strings.timer_presetsec, Math.round(minutes * 60))
        : fill(strings.timer_presetmin, minutes);

    const refresh = () => {
        const cards = list.querySelectorAll('[data-region="timer-card"]');
        cards.forEach((card) => {
            card.querySelector('[data-action="timer-remove"]').hidden = cards.length < 2;
        });
        addButton.disabled = cards.length >= MAX_TIMERS;
    };

    const addTimer = () => {
        created++;
        const number = created;
        const uid = 'timer-' + number + '-' + Math.random().toString(36).slice(2, 8);
        const title = fill(strings.timer_title, number);
        // Button text plus the timer's name, so that several timers do not all say just "Start timer".
        const named = (el, text) => {
            el.textContent = text;
            el.setAttribute('aria-label', text + ' (' + title + ')');
        };
        let total = Math.round(presets[0] * 60000);
        let remaining = total;
        let endAt = 0;
        let interval = null;

        const col = document.createElement('div');
        col.className = 'col-md-6';
        col.dataset.region = 'timer-card';
        const card = document.createElement('div');
        card.className = 'card h-100 text-center mod_classroomtoolbox-timer-card';
        const body = document.createElement('div');
        body.className = 'card-body';

        const heading = document.createElement('h2');
        heading.className = 'h5';
        heading.textContent = title;

        const display = document.createElement('div');
        display.className = 'mod_classroomtoolbox-display';
        display.setAttribute('role', 'timer');
        display.setAttribute('aria-live', 'off');
        display.setAttribute('aria-label', title);

        const progress = document.createElement('div');
        progress.className = 'progress mb-3';
        const bar = document.createElement('div');
        bar.className = 'progress-bar';
        bar.style.width = '100%';
        progress.append(bar);

        const presetRow = document.createElement('div');
        presetRow.className = 'd-flex justify-content-center flex-wrap gap-2 mb-3';
        presetRow.setAttribute('role', 'group');
        presetRow.setAttribute('aria-label', fill(strings.timer_presetsfor, title));

        const custom = document.createElement('div');
        custom.className = 'd-flex justify-content-center align-items-end gap-2 mb-3';
        const makeInput = (suffix, label, spoken, max) => {
            const wrap = document.createElement('div');
            const lab = document.createElement('label');
            lab.className = 'form-label small mb-0';
            lab.htmlFor = uid + '-' + suffix;
            lab.textContent = label;
            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control text-center mod_classroomtoolbox-narrow-input';
            input.id = uid + '-' + suffix;
            input.setAttribute('aria-label', spoken);
            input.min = '0';
            input.max = String(max);
            wrap.append(lab, input);
            custom.append(wrap);
            return input;
        };
        const minInput = makeInput('min', strings.timer_minutes, fill(strings.timer_minutesfor, title), 999);
        const secInput = makeInput('sec', strings.timer_seconds, fill(strings.timer_secondsfor, title), 59);

        const buttons = document.createElement('div');
        buttons.className = 'd-flex justify-content-center flex-wrap gap-2';
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'btn btn-primary';
        toggle.dataset.action = 'timer-toggle';
        const reset = document.createElement('button');
        reset.type = 'button';
        reset.className = 'btn btn-secondary';
        reset.dataset.action = 'timer-reset';
        named(reset, strings.timer_reset);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn btn-outline-danger';
        remove.dataset.action = 'timer-remove';
        named(remove, strings.timer_remove);
        buttons.append(toggle, reset, remove);

        const isRunning = () => interval !== null;
        const paint = () => {
            display.textContent = format(remaining);
            bar.style.width = (total > 0 ? Math.max(0, Math.min(100, remaining / total * 100)) : 0) + '%';
        };
        const showInputs = () => {
            minInput.value = String(Math.floor(total / 60000));
            secInput.value = String(Math.round((total % 60000) / 1000));
        };
        const stopTicking = () => {
            if (isRunning()) {
                window.clearInterval(interval);
                interval = null;
                running--;
                updateWakeLock();
            }
            named(toggle, strings.timer_start);
        };
        const finish = () => {
            stopTicking();
            remaining = 0;
            paint();
            card.classList.add('mod_classroomtoolbox-timer-done');
            if (reduceMotion) {
                card.classList.add('mod_classroomtoolbox-timer-done-static');
            }
            liveEl.textContent = '';
            window.setTimeout(() => {
                liveEl.textContent = fill(strings.timer_doneannounce, title);
            }, 50);
            sound.alarm();
        };
        const tick = () => {
            remaining = Math.max(0, endAt - Date.now());
            paint();
            if (remaining <= 0) {
                finish();
            }
        };
        const setTotal = (ms) => {
            stopTicking();
            card.classList.remove('mod_classroomtoolbox-timer-done', 'mod_classroomtoolbox-timer-done-static');
            total = Math.max(1000, ms);
            remaining = total;
            showInputs();
            paint();
        };

        presets.forEach((minutes) => {
            const preset = document.createElement('button');
            preset.type = 'button';
            preset.className = 'btn btn-outline-secondary btn-sm';
            preset.textContent = presetLabel(minutes);
            preset.addEventListener('click', () => setTotal(Math.round(minutes * 60000)));
            presetRow.append(preset);
        });

        const fromInputs = () => {
            const minutes = Math.max(0, parseInt(minInput.value, 10) || 0);
            const seconds = Math.max(0, parseInt(secInput.value, 10) || 0);
            setTotal((minutes * 60 + seconds) * 1000);
        };
        minInput.addEventListener('change', fromInputs);
        secInput.addEventListener('change', fromInputs);

        toggle.addEventListener('click', () => {
            if (isRunning()) {
                remaining = Math.max(0, endAt - Date.now());
                stopTicking();
                paint();
                return;
            }
            sound.prepare();
            if (remaining <= 0) {
                remaining = total;
            }
            card.classList.remove('mod_classroomtoolbox-timer-done', 'mod_classroomtoolbox-timer-done-static');
            endAt = Date.now() + remaining;
            interval = window.setInterval(tick, 200);
            running++;
            updateWakeLock();
            named(toggle, strings.timer_pause);
            tick();
        });
        reset.addEventListener('click', () => setTotal(total));
        remove.addEventListener('click', () => {
            stopTicking();
            col.remove();
            refresh();
            addButton.focus();
        });

        body.append(heading, display, progress, presetRow, custom, buttons);
        card.append(body);
        col.append(card);
        list.append(col);
        named(toggle, strings.timer_start);
        showInputs();
        paint();
        refresh();
    };

    addButton.addEventListener('click', addTimer);
    addTimer();
};
