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

namespace mod_classroomtoolbox\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_value;
use mod_classroomtoolbox\local\scores;

/**
 * List the scoreboard entries.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_scores extends scores_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'toolboxid' => new external_value(PARAM_INT, 'Classroom Toolbox instance id'),
        ]);
    }

    /**
     * List the scoreboard entries.
     *
     * @param int $toolboxid Classroom Toolbox instance id.
     * @return array The entries, highest points first.
     */
    public static function execute(int $toolboxid): array {
        ['toolboxid' => $toolboxid] = self::validate_parameters(self::execute_parameters(), ['toolboxid' => $toolboxid]);
        [$record] = self::check_access($toolboxid, 'mod/classroomtoolbox:view');
        return scores::get_all($record->id);
    }

    /**
     * Return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return self::entries_returns();
    }
}
