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
 * Library of interface functions for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Which features the module supports.
 *
 * @param string $feature FEATURE_* constant.
 * @return mixed True/false/null.
 */
function classroomtoolbox_supports($feature) {
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
 * The colour schemes a teacher can pick: wedge colours plus the text colour that stays readable on them.
 *
 * @return array[] Keyed by scheme id.
 */
function classroomtoolbox_color_schemes(): array {
    return [
        'classic' => [
            'colors' => ['#3369e8', '#d50f25', '#eeb211', '#009925', '#8e44ad', '#e67e22', '#16a0a0', '#c2185b'],
            'text' => '#ffffff',
        ],
        'highcontrast' => [
            // Every colour gives white text at least 7:1 contrast (WCAG AAA).
            'colors' => ['#000000', '#0033a0', '#8b0000', '#006400', '#4b0082', '#5a3500', '#00424d', '#7a0050'],
            'text' => '#ffffff',
        ],
        'ocean' => [
            'colors' => ['#0b3c5d', '#1d70a2', '#328cc1', '#4fb3bf', '#1b9aaa', '#16697a', '#489fb5', '#145da0'],
            'text' => '#ffffff',
        ],
        'sunset' => [
            'colors' => ['#b5179e', '#d62828', '#e85d04', '#c77d00', '#9d0208', '#7209b7', '#c9184a', '#a4161a'],
            'text' => '#ffffff',
        ],
        'pastel' => [
            'colors' => ['#ffadad', '#ffd6a5', '#fdffb6', '#caffbf', '#9bf6ff', '#a0c4ff', '#bdb2ff', '#ffc6ff'],
            'text' => '#212529',
        ],
    ];
}

/**
 * Return a known scheme id, falling back to the classic one.
 *
 * @param string|null $id Stored value.
 * @return string
 */
function classroomtoolbox_clean_color_scheme(?string $id): string {
    return array_key_exists((string) $id, classroomtoolbox_color_schemes()) ? $id : 'classic';
}

/**
 * Build the stored tools list from the tool_<id> checkboxes of the settings form.
 *
 * @param stdClass $data Form data.
 * @return string Comma-separated tool ids.
 */
function classroomtoolbox_tools_from_data(stdClass $data): string {
    $ids = [];
    foreach (\mod_classroomtoolbox\local\toolbox::all_ids() as $id) {
        $field = 'tool_' . $id;
        if (!empty($data->$field)) {
            $ids[] = $id;
        }
    }
    return \mod_classroomtoolbox\local\toolbox::serialise($ids);
}

/**
 * Add a new instance.
 *
 * @param stdClass $data Form data.
 * @param mod_classroomtoolbox_mod_form|null $mform The form.
 * @return int New instance id.
 */
function classroomtoolbox_add_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->usenrolled = empty($data->usenrolled) ? 0 : 1;
    $data->colorscheme = classroomtoolbox_clean_color_scheme($data->colorscheme ?? null);
    $data->tools = classroomtoolbox_tools_from_data($data);
    $data->names = $data->names ?? '';
    $data->id = $DB->insert_record('classroomtoolbox', $data);
    return $data->id;
}

/**
 * Update an instance.
 *
 * @param stdClass $data Form data.
 * @param mod_classroomtoolbox_mod_form|null $mform The form.
 * @return bool
 */
function classroomtoolbox_update_instance($data, $mform = null) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->usenrolled = empty($data->usenrolled) ? 0 : 1;
    $data->colorscheme = classroomtoolbox_clean_color_scheme($data->colorscheme ?? null);
    $data->tools = classroomtoolbox_tools_from_data($data);
    $data->names = $data->names ?? '';
    return $DB->update_record('classroomtoolbox', $data);
}

/**
 * Delete an instance.
 *
 * @param int $id Instance id.
 * @return bool
 */
function classroomtoolbox_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('classroomtoolbox', ['id' => $id])) {
        return false;
    }
    $DB->delete_records('classroomtoolbox', ['id' => $id]);
    return true;
}

/**
 * Split the manually typed names into a clean list.
 *
 * @param string $text One name per line.
 * @return string[]
 */
function classroomtoolbox_parse_names(string $text): array {
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
 * @param stdClass $classroomtoolbox Instance record.
 * @param stdClass $course Course record.
 * @param stdClass $cm Course module record.
 * @param context_module $context Module context.
 */
function classroomtoolbox_view($classroomtoolbox, $course, $cm, $context) {
    $event = \mod_classroomtoolbox\event\course_module_viewed::create([
        'objectid' => $classroomtoolbox->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('classroomtoolbox', $classroomtoolbox);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}
