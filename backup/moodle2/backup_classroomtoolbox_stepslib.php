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
 * Backup structure step for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the backup structure.
 *
 * @package    mod_classroomtoolbox
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_classroomtoolbox_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $classroomtoolbox = new backup_nested_element('classroomtoolbox', ['id'], [
            'name', 'intro', 'introformat', 'tools', 'timerpresets', 'usenrolled', 'colorscheme', 'names', 'timemodified']);
        $scores = new backup_nested_element('scores');
        $score = new backup_nested_element('score', ['id'], ['label', 'score', 'sortorder', 'timecreated', 'timemodified']);
        $classroomtoolbox->add_child($scores);
        $scores->add_child($score);

        $classroomtoolbox->set_source_table('classroomtoolbox', ['id' => backup::VAR_ACTIVITYID]);
        $score->set_source_table('classroomtoolbox_scores', ['toolboxid' => backup::VAR_PARENTID], 'sortorder ASC');
        $classroomtoolbox->annotate_files('mod_classroomtoolbox', 'intro', null);
        return $this->prepare_activity_structure($classroomtoolbox);
    }
}
