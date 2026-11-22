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

/**
 * Show the spinning wheel.
 *
 * @package    mod_namewheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'namewheel');
$namewheel = $DB->get_record('namewheel', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/namewheel:view', $context);

namewheel_view($namewheel, $course, $cm, $context);

$PAGE->set_url('/mod/namewheel/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($namewheel->name, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => context_course::instance($course->id)]));

// Names: course participants plus the typed list. Names are personal data: never shown to guests, and
// only members of groups the viewer is allowed to see are sent to the browser.
$entries = [];
$groups = [];
// Also require the right to see the participants list, so sites that hide classmates from students stay private.
if (!empty($namewheel->usenrolled) && !isguestuser() && has_capability('moodle/course:viewparticipants', $context)) {
    $users = get_enrolled_users($context, 'mod/namewheel:appear', 0, 'u.*', 'u.lastname, u.firstname', 0, 0, true);
    $membership = [];
    $restricted = false;
    if ($cm->groupmode) {
        $restricted = $cm->groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context);
        // Same groups as groups_get_activity_allowed_groups(), but with members, so that core applies each group's
        // visibility setting (hidden groups and members the viewer may not see are left out).
        $allowedgroups = groups_get_all_groups($cm->course, $restricted ? $USER->id : 0, $cm->groupingid, 'g.*', true, true);
        foreach ($allowedgroups as $group) {
            $count = 0;
            foreach ($group->members as $memberid) {
                if (isset($users[$memberid])) {
                    $membership[$memberid][] = (int) $group->id;
                    $count++;
                }
            }
            $groups[] = ['id' => (int) $group->id, 'name' => format_string($group->name, true, ['context' => $context]),
                'count' => $count];
        }
        if ($restricted) {
            $users = array_intersect_key($users, $membership);
        } else if (array_diff_key($users, $membership)) {
            // Users in no group can be included or left out as a pseudo-group with id 0.
            $groups[] = ['id' => 0, 'name' => get_string('ungrouped', 'mod_namewheel'),
                'count' => count(array_diff_key($users, $membership))];
        }
    }
    foreach ($users as $user) {
        $entries[] = ['name' => fullname($user), 'groups' => $membership[$user->id] ?? [0]];
    }
}
foreach (namewheel_parse_names($namewheel->names) as $extra) {
    $entries[] = ['name' => $extra, 'groups' => null]; // Typed names are always on the wheel.
}
$seen = [];
$entries = array_values(array_filter($entries, function ($entry) use (&$seen) {
    $key = \core_text::strtolower($entry['name']);
    return isset($seen[$key]) ? false : ($seen[$key] = true);
}));

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_namewheel/wheel', [
    'uniqid' => uniqid(),
    'hasnames' => !empty($entries),
    'hasgroups' => !empty($groups),
]);

$PAGE->requires->js_call_amd('mod_namewheel/wheel', 'init', [[
    'names' => $entries,
    'groups' => $groups,
    'scheme' => namewheel_color_schemes()[namewheel_clean_color_scheme($namewheel->colorscheme ?? null)],
    'strings' => [
        'winner' => get_string('winner', 'mod_namewheel'),
        'empty' => get_string('emptywheel', 'mod_namewheel'),
        'spinning' => get_string('spinning', 'mod_namewheel'),
        'clickhere' => get_string('clickhere', 'mod_namewheel'),
        'entriescount' => get_string('entriescount', 'mod_namewheel'),
        'removed' => get_string('removedannounce', 'mod_namewheel'),
        'groupchanged' => get_string('groupchanged', 'mod_namewheel'),
    ],
]]);

echo $OUTPUT->footer();
