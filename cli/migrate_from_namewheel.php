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
 * Move existing Name wheel (mod_namewheel) activities into Classroom Toolbox.
 *
 * Each old activity keeps its course module (so its place in the course, visibility, restrictions and completion stay),
 * and its settings are copied into a new Classroom Toolbox row. The old plugin's own rows are then removed.
 *
 * Usage: php mod/classroomtoolbox/cli/migrate_from_namewheel.php [--dry-run]
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/classroomtoolbox/lib.php');

[$options, $unrecognised] = cli_get_params(['dry-run' => false, 'help' => false], ['h' => 'help', 'n' => 'dry-run']);
if ($unrecognised || $options['help']) {
    cli_writeln("Move Name wheel activities into Classroom Toolbox.\n\n"
        . "Options:\n  --dry-run, -n  Show what would happen without changing anything\n  --help, -h     This help");
    exit($unrecognised ? 1 : 0);
}
$dry = !empty($options['dry-run']);

$dbman = $DB->get_manager();
if (!$dbman->table_exists('namewheel')) {
    cli_writeln('No Name wheel table found: nothing to migrate.');
    exit(0);
}
$oldmodule = $DB->get_field('modules', 'id', ['name' => 'namewheel']);
$newmodule = $DB->get_field('modules', 'id', ['name' => 'classroomtoolbox']);
if (!$newmodule) {
    cli_error('Classroom Toolbox is not installed yet. Run the Moodle upgrade first.');
}
if (!$oldmodule) {
    cli_writeln('The Name wheel plugin is not registered: nothing to migrate.');
    exit(0);
}

$cms = $DB->get_records('course_modules', ['module' => $oldmodule], 'id');
cli_writeln(($dry ? '[dry run] ' : '') . count($cms) . ' Name wheel activit' . (count($cms) == 1 ? 'y' : 'ies') . ' found.');

$courses = [];
foreach ($cms as $cm) {
    $old = $DB->get_record('namewheel', ['id' => $cm->instance]);
    if (!$old) {
        cli_writeln("  cmid {$cm->id}: old record missing, skipped.");
        continue;
    }
    cli_writeln("  cmid {$cm->id} (course {$cm->course}): \"{$old->name}\"");
    if ($dry) {
        continue;
    }
    $transaction = $DB->start_delegated_transaction();
    $new = (object) [
        'course' => $old->course,
        'name' => $old->name,
        'intro' => $old->intro,
        'introformat' => $old->introformat,
        'tools' => 'wheel',
        'usenrolled' => $old->usenrolled,
        'colorscheme' => classroomtoolbox_clean_color_scheme($old->colorscheme ?? null),
        'names' => $old->names,
        'timemodified' => time(),
    ];
    $newid = $DB->insert_record('classroomtoolbox', $new);
    $DB->update_record('course_modules', (object) ['id' => $cm->id, 'module' => $newmodule, 'instance' => $newid]);
    $DB->delete_records('namewheel', ['id' => $old->id]);
    $transaction->allow_commit();
    $courses[$cm->course] = true;

    // The activity's intro files belong to the old component; move them across.
    $context = context_module::instance($cm->id);
    $DB->set_field('files', 'component', 'mod_classroomtoolbox', [
        'contextid' => $context->id,
        'component' => 'mod_namewheel',
    ]);
}
foreach (array_keys($courses) as $courseid) {
    rebuild_course_cache($courseid, true);
}
cli_writeln($dry ? 'Dry run finished: nothing was changed.' : 'Done. Check the activities, then uninstall the old Name wheel plugin.');
