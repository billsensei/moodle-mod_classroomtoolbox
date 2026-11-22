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
 * Behaviour of the Classroom Toolbox shell: tabs (click or arrow keys) and a full-screen toggle.
 *
 * @module     mod_classroomtoolbox/toolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise the shell.
 */
export const init = () => {
    const root = document.querySelector('[data-region="toolbox"]');
    if (!root) {
        return;
    }
    const tabs = [...root.querySelectorAll('[data-action="select-tool"]')];
    const panels = [...root.querySelectorAll('[data-region="tool-panel"]')];

    const select = (tab, focus) => {
        tabs.forEach((t) => {
            const on = t === tab;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
        });
        panels.forEach((p) => {
            p.hidden = p.dataset.tool !== tab.dataset.tool;
        });
        if (focus) {
            tab.focus();
        }
        // Tools that size themselves (such as the canvas wheel) redraw on resize.
        window.dispatchEvent(new Event('resize'));
    };

    // Full screen, for projecting a tool onto a classroom screen.
    const fullscreen = root.querySelector('[data-action="fullscreen"]');
    if (fullscreen && document.fullscreenEnabled) {
        const enterLabel = fullscreen.textContent.trim();
        const exitLabel = fullscreen.dataset.exitLabel;
        fullscreen.hidden = false;
        fullscreen.addEventListener('click', () => {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                root.requestFullscreen();
            }
        });
        document.addEventListener('fullscreenchange', () => {
            const on = document.fullscreenElement === root;
            fullscreen.textContent = on ? exitLabel : enterLabel;
            window.dispatchEvent(new Event('resize'));
        });
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => select(tab, false));
        tab.addEventListener('keydown', (e) => {
            let target = null;
            if (e.key === 'ArrowRight') {
                target = tabs[(index + 1) % tabs.length];
            } else if (e.key === 'ArrowLeft') {
                target = tabs[(index - 1 + tabs.length) % tabs.length];
            } else if (e.key === 'Home') {
                target = tabs[0];
            } else if (e.key === 'End') {
                target = tabs[tabs.length - 1];
            }
            if (target) {
                e.preventDefault();
                select(target, true);
            }
        });
    });
};
