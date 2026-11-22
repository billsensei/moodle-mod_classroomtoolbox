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
 * Data generator for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Data generator for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_classroomtoolbox_generator extends testing_module_generator {
    /**
     * Create an instance with sensible defaults.
     *
     * @param array|stdClass|null $record Field values.
     * @param array|null $options Course module options.
     * @return stdClass Activity record with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;
        $defaults = ['usenrolled' => 1, 'colorscheme' => 'classic', 'names' => '', 'tools' => 'wheel'];
        foreach ($defaults as $name => $value) {
            if (!isset($record->$name)) {
                $record->$name = $value;
            }
        }
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Add a scoreboard entry.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param string $label Entry label.
     * @param int $score Points.
     * @return \stdClass The entry row.
     */
    public function create_score(int $toolboxid, string $label, int $score = 0): \stdClass {
        global $DB;

        \mod_classroomtoolbox\local\scores::add($toolboxid, [$label]);
        $row = $DB->get_record('classroomtoolbox_scores', ['toolboxid' => $toolboxid, 'label' => $label], '*', MUST_EXIST);
        if ($score) {
            \mod_classroomtoolbox\local\scores::update($toolboxid, $row->id, $label, $score);
            $row->score = $score;
        }
        return $row;
    }
}
