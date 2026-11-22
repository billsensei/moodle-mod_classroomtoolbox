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
use mod_classroomtoolbox\local\participants;

/**
 * The groups tool.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class groups implements tool_interface {
    #[\Override]
    public static function get_id(): string {
        return 'groups';
    }

    #[\Override]
    public function render(\cm_info $cm, \stdClass $course, \stdClass $record, \context_module $context): string {
        global $OUTPUT, $PAGE;

        [$entries, $groups] = participants::get($cm, $record, $context);

        $html = $OUTPUT->render_from_template('mod_classroomtoolbox/groups', [
            'uniqid' => uniqid(),
            'hasgroups' => !empty($groups),
        ]);
        $scheme = classroomtoolbox_color_schemes()[classroomtoolbox_clean_color_scheme($record->colorscheme ?? null)];
        $PAGE->requires->js_call_amd('mod_classroomtoolbox/groups', 'init', [[
            'names' => $entries,
            'groups' => $groups,
            'scheme' => $scheme,
            'strings' => [
                'groupgen_peoplecount' => get_string('groupgen_peoplecount', 'mod_classroomtoolbox'),
                'groupgen_group' => get_string('groupgen_group', 'mod_classroomtoolbox'),
                'groupgen_nonames' => get_string('groupgen_nonames', 'mod_classroomtoolbox'),
                'groupgen_generated' => get_string('groupgen_generated', 'mod_classroomtoolbox'),
                'groupgen_copied' => get_string('groupgen_copied', 'mod_classroomtoolbox'),
                'groupgen_copyfailed' => get_string('groupgen_copyfailed', 'mod_classroomtoolbox'),
            ],
        ]]);
        return $html;
    }
}
