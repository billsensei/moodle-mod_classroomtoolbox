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
$PAGE->set_title(format_string($namewheel->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Names: course participants (optionally limited to the active group) plus the typed list.
$names = [];
// Participant names are personal data: never show them to guests, and in separate-groups mode only
// show the viewer's own group unless they can access all groups.
if (!empty($namewheel->usenrolled) && !isguestuser()) {
    $groupid = 0;
    $allowed = true;
    if ($cm->groupmode) {
        $groupid = groups_get_activity_group($cm, true);
        if ($cm->groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
            $allowed = !empty($groupid) && groups_group_visible($groupid, $course, $cm);
        }
    }
    if ($allowed) {
        $users = get_enrolled_users($context, 'mod/namewheel:appear', $groupid, 'u.*', 'u.lastname, u.firstname', 0, 0, true);
        foreach ($users as $user) {
            $names[] = fullname($user);
        }
    }
}
$names = array_merge($names, namewheel_parse_names($namewheel->names));
$names = array_values(array_unique($names));

echo $OUTPUT->header();
if ($cm->groupmode) {
    groups_print_activity_menu($cm, $PAGE->url);
}

echo $OUTPUT->render_from_template('mod_namewheel/wheel', [
    'uniqid' => uniqid(),
    'hasnames' => !empty($names),
]);

$PAGE->requires->js_call_amd('mod_namewheel/wheel', 'init', [[
    'names' => $names,
    'strings' => [
        'winner' => get_string('winner', 'mod_namewheel'),
        'empty' => get_string('emptywheel', 'mod_namewheel'),
        'spinning' => get_string('spinning', 'mod_namewheel'),
        'clickhere' => get_string('clickhere', 'mod_namewheel'),
        'entriescount' => get_string('entriescount', 'mod_namewheel'),
        'removed' => get_string('removedannounce', 'mod_namewheel'),
    ],
]]);

echo $OUTPUT->footer();
