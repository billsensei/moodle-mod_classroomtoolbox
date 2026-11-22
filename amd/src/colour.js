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
 * Colour helpers shared by the tools: pick readable text for a background colour.
 *
 * @module     mod_classroomtoolbox/colour
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const LIGHT_TEXT = '#ffffff';
export const DARK_TEXT = '#212529';

/**
 * Relative luminance of a #rrggbb colour (WCAG).
 *
 * @param {string} hex Colour.
 * @returns {number}
 */
export const luminance = (hex) => {
    const [r, g, b] = [1, 3, 5].map((i) => {
        const c = parseInt(hex.slice(i, i + 2), 16) / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};

/**
 * White or dark text, whichever has the better contrast on a background colour.
 *
 * @param {string} hex Background colour.
 * @returns {string} Text colour.
 */
export const readableText = (hex) => {
    const bg = luminance(hex) + 0.05;
    const withLight = 1.05 / bg;
    const withDark = bg / (luminance(DARK_TEXT) + 0.05);
    return withLight >= withDark ? LIGHT_TEXT : DARK_TEXT;
};
