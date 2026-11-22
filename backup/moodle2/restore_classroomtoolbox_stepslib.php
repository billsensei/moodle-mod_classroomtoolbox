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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Restore structure step for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/classroomtoolbox/lib.php');

/**
 * Defines the restore structure.
 *
 * @package    mod_classroomtoolbox
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_classroomtoolbox_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the paths.
     *
     * @return array
     */
    protected function define_structure() {
        return $this->prepare_activity_structure([
            new restore_path_element('classroomtoolbox', '/activity/classroomtoolbox'),
            new restore_path_element('classroomtoolbox_score', '/activity/classroomtoolbox/scores/score'),
        ]);
    }

    /**
     * Restore the instance row.
     *
     * @param array $data Row data.
     */
    protected function process_classroomtoolbox($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->colorscheme = classroomtoolbox_clean_color_scheme($data->colorscheme ?? null);
        $data->timerpresets = classroomtoolbox_clean_timer_presets($data->timerpresets ?? null);
        $data->tools = \mod_classroomtoolbox\local\toolbox::serialise(explode(',', $data->tools ?? ''));
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $newitemid = $DB->insert_record('classroomtoolbox', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore one scoreboard entry.
     *
     * @param array $data Row data.
     */
    protected function process_classroomtoolbox_score($data) {
        global $DB;

        $data = (object) $data;
        $data->toolboxid = $this->get_new_parentid('classroomtoolbox');
        $data->label = \mod_classroomtoolbox\local\scores::clean_label((string) $data->label);
        $limit = \mod_classroomtoolbox\local\scores::LIMIT;
        $data->score = max(-$limit, min($limit, (int) $data->score));
        if ($data->label !== '') {
            $DB->insert_record('classroomtoolbox_scores', $data);
        }
    }

    /**
     * Restore the intro files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_classroomtoolbox', 'intro', null);
    }
}
