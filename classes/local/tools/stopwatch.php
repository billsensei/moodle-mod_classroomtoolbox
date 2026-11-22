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

use mod_classroomtoolbox\local\tool_interface;

/**
 * The stopwatch tool.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stopwatch implements tool_interface {
    #[\Override]
    public static function get_id(): string {
        return 'stopwatch';
    }

    #[\Override]
    public function render(\cm_info $cm, \stdClass $course, \stdClass $record, \context_module $context): string {
        global $OUTPUT, $PAGE;

        $html = $OUTPUT->render_from_template('mod_classroomtoolbox/stopwatch', ['uniqid' => uniqid()]);
        $PAGE->requires->js_call_amd('mod_classroomtoolbox/stopwatch', 'init', [[
            'strings' => [
                'sw_start' => get_string('sw_start', 'mod_classroomtoolbox'),
                'sw_stop' => get_string('sw_stop', 'mod_classroomtoolbox'),
                'sw_lapno' => get_string('sw_lapno', 'mod_classroomtoolbox'),
            ],
        ]]);
        return $html;
    }
}
