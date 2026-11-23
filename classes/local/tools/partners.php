<?php
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

namespace mod_classroomtoolbox\local\tools;

defined('MOODLE_INTERNAL') || die();

/**
 * The partner wheel: the name wheel, run in a mode that spins twice to pick a pair.
 *
 * It shows the same names as the name wheel (the course participants a viewer may see, plus the typed names) and is
 * rendered by the same code; the script keeps the pairs made so far and joins an odd one out to the last pair.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class partners extends wheel {
    #[\Override]
    public static function get_id(): string {
        return 'partners';
    }
}
