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
 * Add or subtract points for one entry.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class change_score extends scores_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'toolboxid' => new external_value(PARAM_INT, 'Classroom Toolbox instance id'),
            'id' => new external_value(PARAM_INT, 'Entry id'),
            'delta' => new external_value(PARAM_INT, 'Points to add (negative to subtract)'),
        ]);
    }

    /**
     * Add or subtract points for one entry.
     *
     * @param int $toolboxid Classroom Toolbox instance id.
     * @param int $id Entry id.
     * @param int $delta Points to add (negative to subtract).
     * @return array The entries, highest points first.
     */
    public static function execute(int $toolboxid, int $id, int $delta): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['toolboxid' => $toolboxid, 'id' => $id, 'delta' => $delta]
        );
        [$record, $context] = self::check_access($params['toolboxid'], 'mod/classroomtoolbox:managescores');
        scores::change($record->id, $params['id'], $params['delta']);
        return self::finish($record, $context, 'points');
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
