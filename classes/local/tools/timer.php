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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/classroomtoolbox/lib.php');

/**
 * The timer tool.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class timer implements tool_interface {
    #[\Override]
    public static function get_id(): string {
        return 'timer';
    }

    #[\Override]
    public function render(\cm_info $cm, \stdClass $course, \stdClass $record, \context_module $context): string {
        global $OUTPUT, $PAGE;

        $html = $OUTPUT->render_from_template('mod_classroomtoolbox/timer', ['uniqid' => uniqid()]);
        $PAGE->requires->js_call_amd('mod_classroomtoolbox/timer', 'init', [[
            'presets' => classroomtoolbox_timer_presets($record->timerpresets ?? null),
            'strings' => [
                'timer_title' => get_string('timer_title', 'mod_classroomtoolbox'),
                'timer_start' => get_string('timer_start', 'mod_classroomtoolbox'),
                'timer_pause' => get_string('timer_pause', 'mod_classroomtoolbox'),
                'timer_reset' => get_string('timer_reset', 'mod_classroomtoolbox'),
                'timer_remove' => get_string('timer_remove', 'mod_classroomtoolbox'),
                'timer_minutes' => get_string('timer_minutes', 'mod_classroomtoolbox'),
                'timer_seconds' => get_string('timer_seconds', 'mod_classroomtoolbox'),
                'timer_done' => get_string('timer_done', 'mod_classroomtoolbox'),
                'timer_doneannounce' => get_string('timer_doneannounce', 'mod_classroomtoolbox'),
                'timer_presetmin' => get_string('timer_presetmin', 'mod_classroomtoolbox'),
                'timer_presetsec' => get_string('timer_presetsec', 'mod_classroomtoolbox'),
                'timer_presets' => get_string('timer_presets', 'mod_classroomtoolbox'),
                'timer_set' => get_string('timer_set', 'mod_classroomtoolbox'),
            ],
        ]]);
        return $html;
    }
}
