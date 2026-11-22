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

use mod_classroomtoolbox\local\participants;
use mod_classroomtoolbox\local\scores;
use mod_classroomtoolbox\local\tool_interface;

/**
 * The scoreboard tool.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scoreboard implements tool_interface {
    #[\Override]
    public static function get_id(): string {
        return 'scoreboard';
    }

    #[\Override]
    public function render(\cm_info $cm, \stdClass $course, \stdClass $record, \context_module $context): string {
        global $OUTPUT, $PAGE;

        $canmanage = has_capability('mod/classroomtoolbox:managescores', $context);
        $suggestions = ['participants' => [], 'groups' => []];
        if ($canmanage) {
            // Only people who may change the scoreboard get the lists they can copy onto it.
            [$entries] = participants::get($cm, $record, $context);
            $suggestions['participants'] = array_column($entries, 'name');
            $groups = groups_get_all_groups($cm->course, 0, $cm->groupingid, 'g.id, g.name', false, true);
            foreach ($groups as $group) {
                $suggestions['groups'][] = format_string($group->name, true, ['context' => $context]);
            }
        }

        $html = $OUTPUT->render_from_template('mod_classroomtoolbox/scoreboard', [
            'uniqid' => uniqid(),
            'canmanage' => $canmanage,
            'hasparticipants' => !empty($suggestions['participants']),
            'hasgroups' => !empty($suggestions['groups']),
        ]);
        $PAGE->requires->js_call_amd('mod_classroomtoolbox/scoreboard', 'init', [[
            'toolboxid' => (int) $record->id,
            'canmanage' => $canmanage,
            'entries' => scores::get_all($record->id),
            'participants' => $suggestions['participants'],
            'groups' => $suggestions['groups'],
            'maxentries' => scores::MAX_ENTRIES,
            'maxlabel' => scores::MAX_LABEL,
            'strings' => [
                'score_rank' => get_string('score_rank', 'mod_classroomtoolbox'),
                'score_empty' => get_string('score_empty', 'mod_classroomtoolbox'),
                'score_emptyviewer' => get_string('score_emptyviewer', 'mod_classroomtoolbox'),
                'score_up' => get_string('score_up', 'mod_classroomtoolbox'),
                'score_down' => get_string('score_down', 'mod_classroomtoolbox'),
                'score_name' => get_string('score_name', 'mod_classroomtoolbox'),
                'score_points' => get_string('score_points', 'mod_classroomtoolbox'),
                'score_delete' => get_string('score_delete', 'mod_classroomtoolbox'),
                'score_announce' => get_string('score_announce', 'mod_classroomtoolbox'),
                'score_resettitle' => get_string('score_resettitle', 'mod_classroomtoolbox'),
                'score_resetquestion' => get_string('score_resetquestion', 'mod_classroomtoolbox'),
                'score_resetconfirm' => get_string('score_resetconfirm', 'mod_classroomtoolbox'),
            ],
        ]]);
        return $html;
    }
}
