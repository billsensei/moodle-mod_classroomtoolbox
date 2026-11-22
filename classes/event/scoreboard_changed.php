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

namespace mod_classroomtoolbox\event;

/**
 * Event: someone changed the scoreboard (added, removed or scored an entry, or reset the board).
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scoreboard_changed extends \core\event\base {
    #[\Override]
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'classroomtoolbox';
    }

    #[\Override]
    public static function get_name() {
        return get_string('eventscoreboardchanged', 'mod_classroomtoolbox');
    }

    #[\Override]
    public function get_description() {
        return "The user with id '$this->userid' changed the scoreboard ('{$this->other['action']}') of the Classroom " .
            "Toolbox with course module id '$this->contextinstanceid'.";
    }

    #[\Override]
    public function get_url() {
        return new \moodle_url('/mod/classroomtoolbox/view.php', ['id' => $this->contextinstanceid]);
    }

    #[\Override]
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->other['action'])) {
            throw new \coding_exception('The \'action\' value must be set in other.');
        }
    }

    #[\Override]
    public static function get_objectid_mapping() {
        return ['db' => 'classroomtoolbox', 'restore' => 'classroomtoolbox'];
    }

    #[\Override]
    public static function get_other_mapping() {
        return false;
    }
}
