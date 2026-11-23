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
 * Remembers each tool's settings in this browser, so they are still there after a reload or a later visit.
 *
 * Only settings are kept (amounts, modes, timer lengths, ticked groups, counter names): never results, and never the
 * names of students. Everything is stored per activity, in the browser's own storage; nothing is sent to the server.
 * Whatever is read back must be checked by the caller, since stored text can be edited or can come from an old version.
 *
 * @module     mod_classroomtoolbox/store
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The storage key of this activity, or null when the page has none.
 *
 * @returns {string|null}
 */
const storageKey = () => {
    const root = document.querySelector('[data-region="toolbox"]');
    return root && root.dataset.storekey ? 'mod_classroomtoolbox_settings_' + root.dataset.storekey : null;
};

/**
 * Read everything stored for this activity.
 *
 * @returns {Object} Settings by tool; empty when nothing usable is stored.
 */
const readAll = () => {
    try {
        const key = storageKey();
        const parsed = key ? JSON.parse(window.localStorage.getItem(key) || '{}') : {};
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (e) {
        return {};
    }
};

/**
 * The saved settings of one tool.
 *
 * @param {string} tool Tool id, such as 'timer'.
 * @returns {Object} The settings, or an empty object. The values still need checking.
 */
export const load = (tool) => {
    const value = readAll()[tool];
    return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
};

/**
 * Save the settings of one tool.
 *
 * @param {string} tool Tool id, such as 'timer'.
 * @param {Object} settings Plain values that can be written as JSON.
 */
export const save = (tool, settings) => {
    try {
        const key = storageKey();
        if (key) {
            const all = readAll();
            all[tool] = settings;
            window.localStorage.setItem(key, JSON.stringify(all));
        }
    } catch (e) {
        // Storage can be blocked or full; the settings then last only for this page.
    }
};
