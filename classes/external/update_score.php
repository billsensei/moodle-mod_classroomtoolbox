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
 * Rename an entry and set its points.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_score extends scores_base {
    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'toolboxid' => new external_value(PARAM_INT, 'Classroom Toolbox instance id'),
            'id' => new external_value(PARAM_INT, 'Entry id'),
            'label' => new external_value(PARAM_RAW, 'New name (plain text; tags are removed)'),
            'score' => new external_value(PARAM_INT, 'New points'),
        ]);
    }

    /**
     * Rename an entry and set its points.
     *
     * @param int $toolboxid Classroom Toolbox instance id.
     * @param int $id Entry id.
     * @param string $label New name.
     * @param int $score New points.
     * @return array The entries, highest points first.
     */
    public static function execute(int $toolboxid, int $id, string $label, int $score): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['toolboxid' => $toolboxid, 'id' => $id, 'label' => $label, 'score' => $score]
        );
        [$record, $context] = self::check_access($params['toolboxid'], 'mod/classroomtoolbox:managescores');
        scores::update($record->id, $params['id'], $params['label'], $params['score']);
        return self::finish($record, $context, 'edit');
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
