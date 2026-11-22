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
 * Show the Classroom Toolbox.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);
[$course, $cm] = get_course_and_cm_from_cmid($id, 'classroomtoolbox');
$classroomtoolbox = $DB->get_record('classroomtoolbox', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/classroomtoolbox:view', $context);

classroomtoolbox_view($classroomtoolbox, $course, $cm, $context);

$PAGE->set_url('/mod/classroomtoolbox/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($classroomtoolbox->name, true, ['context' => $context]));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => context_course::instance($course->id)]));

$tabs = [];
foreach (\mod_classroomtoolbox\local\toolbox::enabled($classroomtoolbox) as $id => $tool) {
    $tabs[] = [
        'id' => $id,
        'label' => get_string('tool_' . $id, 'mod_classroomtoolbox'),
        'html' => $tool->render($cm, $course, $classroomtoolbox, $context),
        'active' => !$tabs,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_classroomtoolbox/toolbox', [
    'uniqid' => uniqid(),
    'tabs' => $tabs,
    'hastabs' => count($tabs) > 1,
]);
if (count($tabs) > 1) {
    $PAGE->requires->js_call_amd('mod_classroomtoolbox/toolbox', 'init');
}

echo $OUTPUT->footer();
