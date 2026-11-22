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
 * Library of interface functions for mod_namewheel.
 *
 * @package    mod_namewheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Which features the module supports.
 *
 * @param string $feature FEATURE_* constant.
 * @return mixed True/false/null.
 */
function namewheel_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GROUPS:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_GROUPINGS:
            return false;
        default:
            return null;
    }
}

/**
 * Add a new instance.
 *
 * @param stdClass $data Form data.
 * @param mod_namewheel_mod_form|null $mform The form.
 * @return int New instance id.
 */
function namewheel_add_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->usenrolled = empty($data->usenrolled) ? 0 : 1;
    $data->names = $data->names ?? '';
    $data->id = $DB->insert_record('namewheel', $data);
    return $data->id;
}

/**
 * Update an instance.
 *
 * @param stdClass $data Form data.
 * @param mod_namewheel_mod_form|null $mform The form.
 * @return bool
 */
function namewheel_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->usenrolled = empty($data->usenrolled) ? 0 : 1;
    $data->names = $data->names ?? '';
    return $DB->update_record('namewheel', $data);
}

/**
 * Delete an instance.
 *
 * @param int $id Instance id.
 * @return bool
 */
function namewheel_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('namewheel', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('namewheel', ['id' => $id]);
    return true;
}

/**
 * Split the manually typed names into a clean list.
 *
 * @param string $text One name per line.
 * @return string[]
 */
function namewheel_parse_names(string $text): array {
    $names = [];
    foreach (preg_split('/\R/u', $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $names[] = $line;
        }
    }
    return $names;
}

/**
 * Mark the activity as viewed for completion and log the view.
 *
 * @param stdClass $namewheel Instance record.
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context_module $context Module context.
 */
function namewheel_view($namewheel, $course, $cm, $context) {
    $event = \mod_namewheel\event\course_module_viewed::create([
        'objectid' => $namewheel->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('namewheel', $namewheel);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}
