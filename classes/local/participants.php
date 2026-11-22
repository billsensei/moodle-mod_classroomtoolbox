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
        [$entries, $groups] = self::enrolled($cm, $record, $context);
        foreach (classroomtoolbox_parse_names($record->names) as $extra) {
            $entries[] = ['name' => $extra, 'groups' => null]; // Typed names are always offered.
        }
        return [self::without_duplicates($entries), $groups];
    }

    /**
     * The enrolled participants the viewer may see, with the groups they belong to.
     *
     * @param \cm_info $cm Course module.
     * @param \stdClass $record Activity record.
     * @param \context_module $context Module context.
     * @return array [entries, groups]
     */
    private static function enrolled(\cm_info $cm, \stdClass $record, \context_module $context): array {
        // Also require the right to see the participants list, so sites that hide classmates from students stay private.
        if (empty($record->usenrolled) || isguestuser() || !has_capability('moodle/course:viewparticipants', $context)) {
            return [[], []];
        }
        // Only the columns the display name needs: never load e-mail addresses or password hashes.
        $namefields = \core_user\fields::for_name()->get_required_fields();
        $select = 'u.id, ' . implode(', ', array_map(fn($field) => 'u.' . $field, $namefields));
        $users = get_enrolled_users($context, 'mod/classroomtoolbox:appear', 0, $select, 'u.lastname, u.firstname', 0, 0, true);
        $membership = [];
        $groups = [];
        if ($cm->groupmode) {
            [$users, $membership, $groups] = self::with_groups($cm, $context, $users);
        }
        $entries = [];
        foreach ($users as $user) {
            $entries[] = ['name' => fullname($user), 'groups' => $membership[$user->id] ?? [0]];
        }
        return [$entries, $groups];
    }

    /**
     * Work out the groups of the users, and in separate groups mode keep only the viewer's own groups.
     *
     * @param \cm_info $cm Course module.
     * @param \context_module $context Module context.
     * @param \stdClass[] $users The enrolled users.
     * @return array [users, membership (user id => group ids), groups (id, name, count)]
     */
    private static function with_groups(\cm_info $cm, \context_module $context, array $users): array {
        global $USER;

        $restricted = $cm->groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context);
        // Same groups as groups_get_activity_allowed_groups(), but with members, so that core applies each group's
        // visibility setting (hidden groups and members the viewer may not see are left out).
        $userid = $restricted ? $USER->id : 0;
        $membership = [];
        $groups = [];
        foreach (groups_get_all_groups($cm->course, $userid, $cm->groupingid, 'g.*', true, true) as $group) {
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
        $ungrouped = array_diff_key($users, $membership);
        if ($restricted) {
            $users = array_intersect_key($users, $membership);
        } else if ($ungrouped) {
            // Users in no group can be included or left out as a pseudo-group with id 0.
            $groups[] = ['id' => 0, 'name' => get_string('ungrouped', 'mod_classroomtoolbox'), 'count' => count($ungrouped)];
        }
        return [$users, $membership, $groups];
    }

    /**
     * Drop repeated names (ignoring case), keeping the first.
     *
     * @param array[] $entries Entries with a 'name'.
     * @return array[]
     */
    private static function without_duplicates(array $entries): array {
        $seen = [];
        return array_values(array_filter($entries, function ($entry) use (&$seen) {
            $key = \core_text::strtolower($entry['name']);
            return isset($seen[$key]) ? false : ($seen[$key] = true);
        }));
    }
}
