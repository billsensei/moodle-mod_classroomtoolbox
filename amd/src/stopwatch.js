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
 * Stopwatch with laps.
 *
 * @module     mod_classroomtoolbox/stopwatch
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Format milliseconds as mm:ss.cc (or h:mm:ss.cc from one hour).
 *
 * @param {number} ms Milliseconds.
 * @returns {string}
 */
export const format = (ms) => {
    const pad = (n) => String(n).padStart(2, '0');
    const total = Math.floor(ms / 10);
    const centis = total % 100;
    const seconds = Math.floor(total / 100) % 60;
    const minutes = Math.floor(total / 6000) % 60;
    const hours = Math.floor(total / 360000);
    return (hours > 0 ? hours + ':' + pad(minutes) : pad(minutes)) + ':' + pad(seconds) + '.' + pad(centis);
};

/**
 * Initialise the stopwatch.
 *
 * @param {Object} config Language strings from PHP.
 */
export const init = (config) => {
    const root = document.querySelector('[data-region="stopwatch"]');
    if (!root) {
        return;
    }
    const strings = config.strings;
    const display = root.querySelector('[data-region="sw-display"]');
    const toggle = root.querySelector('[data-action="sw-toggle"]');
    const lapButton = root.querySelector('[data-action="sw-lap"]');
    const resetButton = root.querySelector('[data-action="sw-reset"]');
    const lapsWrap = root.querySelector('[data-region="sw-laps-wrap"]');
    const lapsList = root.querySelector('[data-region="sw-laps"]');
    const liveEl = root.querySelector('[data-region="sw-live"]');

    // Time is taken from timestamps, not counted ticks, so it stays right in a background tab.
    let running = false;
    let startedAt = 0;
    let elapsedBefore = 0;
    let lastLap = 0;
    let lapCount = 0;
    let frame = null;

    const elapsed = () => elapsedBefore + (running ? performance.now() - startedAt : 0);
    const paint = () => {
        display.textContent = format(elapsed());
    };
    const loop = () => {
        paint();
        if (running) {
            frame = window.requestAnimationFrame(loop);
        }
    };

    toggle.addEventListener('click', () => {
        if (running) {
            elapsedBefore += performance.now() - startedAt;
            running = false;
            window.cancelAnimationFrame(frame);
            toggle.textContent = strings.sw_start;
            lapButton.disabled = true;
            paint();
        } else {
            startedAt = performance.now();
            running = true;
            toggle.textContent = strings.sw_stop;
            lapButton.disabled = false;
            loop();
        }
    });

    lapButton.addEventListener('click', () => {
        if (!running) {
            return;
        }
        const now = elapsed();
        lapCount++;
        const label = strings.sw_lapno.replace('{$a}', () => lapCount);
        const item = document.createElement('li');
        item.className = 'list-group-item d-flex justify-content-between';
        const name = document.createElement('span');
        name.textContent = label;
        const times = document.createElement('span');
        times.className = 'text-nowrap';
        times.textContent = format(now) + '  (+' + format(now - lastLap) + ')';
        item.append(name, times);
        lapsList.prepend(item);
        lapsWrap.hidden = false;
        lastLap = now;
        liveEl.textContent = label + ': ' + format(now);
    });

    resetButton.addEventListener('click', () => {
        running = false;
        window.cancelAnimationFrame(frame);
        elapsedBefore = 0;
        lastLap = 0;
        lapCount = 0;
        lapsList.textContent = '';
        lapsWrap.hidden = true;
        toggle.textContent = strings.sw_start;
        lapButton.disabled = true;
        liveEl.textContent = '';
        paint();
    });
};
