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
 * Backup task for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/classroomtoolbox/backup/moodle2/backup_classroomtoolbox_stepslib.php');

/**
 * Backup task.
 *
 * @package    mod_classroomtoolbox
 * @category   backup
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_classroomtoolbox_activity_task extends backup_activity_task {
    /**
     * No settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Add the structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_classroomtoolbox_activity_structure_step('classroomtoolbox_structure', 'classroomtoolbox.xml'));
    }

    /**
     * Encode links to the activity's pages.
     *
     * @param string $content HTML that may contain links.
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $search = '/(' . $base . '\/mod\/classroomtoolbox\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CLASSROOMTOOLBOXINDEX*$2@$', $content);
        $search = '/(' . $base . '\/mod\/classroomtoolbox\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@CLASSROOMTOOLBOXVIEWBYID*$2@$', $content);
        return $content;
    }
}
