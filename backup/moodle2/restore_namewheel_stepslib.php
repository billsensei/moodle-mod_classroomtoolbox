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
 * Restore structure step for mod_namewheel.
 *
 * @package    mod_namewheel
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the restore structure.
 *
 * @package    mod_namewheel
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_namewheel_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the paths.
     *
     * @return array
     */
    protected function define_structure() {
        return $this->prepare_activity_structure([new restore_path_element('namewheel', '/activity/namewheel')]);
    }

    /**
     * Restore the instance row.
     *
     * @param array $data Row data.
     */
    protected function process_namewheel($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $newitemid = $DB->insert_record('namewheel', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Restore the intro files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_namewheel', 'intro', null);
    }
}
