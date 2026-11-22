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

namespace mod_classroomtoolbox\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/classroomtoolbox/lib.php');

/**
 * Who can be put on the wheel or into groups: the course participants a viewer is allowed to see, plus typed names.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participants {
    /**
     * Names and group choices for the tools that pick or split people.
     *
     * Names are personal data: never shown to guests, and only members of groups the viewer is allowed to see are
     * sent to the browser.
     *
     * @param \cm_info $cm Course module.
     * @param \stdClass $record Activity record.
     * @param \context_module $context Module context.
     * @return array [entries, groups]
     */
    public static function get(\cm_info $cm, \stdClass $record, \context_module $context): array {
        global $USER;

        $entries = [];
        $groups = [];
        // Also require the right to see the participants list, so sites that hide classmates from students stay private.
        if (!empty($record->usenrolled) && !isguestuser() && has_capability('moodle/course:viewparticipants', $context)) {
            $users = get_enrolled_users($context, 'mod/classroomtoolbox:appear', 0, 'u.*', 'u.lastname, u.firstname', 0, 0, true);
            $membership = [];
            $restricted = false;
            if ($cm->groupmode) {
                $restricted = $cm->groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context);
                // Same groups as groups_get_activity_allowed_groups(), but with members, so that core applies each group's
                // visibility setting (hidden groups and members the viewer may not see are left out).
                $userid = $restricted ? $USER->id : 0;
                $allowedgroups = groups_get_all_groups($cm->course, $userid, $cm->groupingid, 'g.*', true, true);
                foreach ($allowedgroups as $group) {
                    $count = 0;
                    foreach ($group->members as $memberid) {
                        if (isset($users[$memberid])) {
                            $membership[$memberid][] = (int) $group->id;
                            $count++;
                        }
                    }
                    $groups[] = ['id' => (int) $group->id,
                        'name' => format_string($group->name, true, ['context' => $context]), 'count' => $count];
                }
                if ($restricted) {
                    $users = array_intersect_key($users, $membership);
                } else if (array_diff_key($users, $membership)) {
                    // Users in no group can be included or left out as a pseudo-group with id 0.
                    $groups[] = ['id' => 0, 'name' => get_string('ungrouped', 'mod_classroomtoolbox'),
                        'count' => count(array_diff_key($users, $membership))];
                }
            }
            foreach ($users as $user) {
                $entries[] = ['name' => fullname($user), 'groups' => $membership[$user->id] ?? [0]];
            }
        }
        foreach (classroomtoolbox_parse_names($record->names) as $extra) {
            $entries[] = ['name' => $extra, 'groups' => null]; // Typed names are always on the wheel.
        }
        $seen = [];
        $entries = array_values(array_filter($entries, function ($entry) use (&$seen) {
            $key = \core_text::strtolower($entry['name']);
            return isset($seen[$key]) ? false : ($seen[$key] = true);
        }));
        return [$entries, $groups];
    }
}
