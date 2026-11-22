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
 * List the name wheels in a course.
 *
 * @package    mod_namewheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);
$PAGE->set_url('/mod/namewheel/index.php', ['id' => $id]);
$PAGE->set_context($coursecontext);
$PAGE->set_title(get_string('modulenameplural', 'mod_namewheel'));
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $coursecontext]));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_namewheel'));

// Only list wheels the user may open (same capability as view.php).
$instances = array_filter(
    get_all_instances_in_course('namewheel', $course),
    fn($instance) => has_capability('mod/namewheel:view', context_module::instance($instance->coursemodule))
);
if (!$instances) {
    notice(
        get_string('thereareno', 'moodle', get_string('modulenameplural', 'mod_namewheel')),
        new moodle_url('/course/view.php', ['id' => $course->id])
    );
}

$table = new html_table();
$table->head = [get_string('name')];
foreach ($instances as $instance) {
    $link = html_writer::link(
        new moodle_url('/mod/namewheel/view.php', ['id' => $instance->coursemodule]),
        format_string($instance->name, true, ['context' => context_module::instance($instance->coursemodule)])
    );
    $table->data[] = [$link];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
