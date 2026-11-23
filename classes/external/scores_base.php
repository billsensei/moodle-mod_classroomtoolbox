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

use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_classroomtoolbox\event\scoreboard_changed;
use mod_classroomtoolbox\local\scores;
use mod_classroomtoolbox\local\toolbox;

/**
 * Shared checks and return structure for the scoreboard web services.
 *
 * Every service resolves the toolbox, derives the context from it (never from a caller-supplied context) and checks
 * the capability there, and every entry id is looked up inside that toolbox only.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class scores_base extends external_api {
    /**
     * The list every service returns.
     *
     * @return external_multiple_structure
     */
    public static function entries_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Entry id'),
            'label' => new external_value(PARAM_TEXT, 'Team or person name'),
            'score' => new external_value(PARAM_INT, 'Points'),
        ]), 'Entries, highest points first');
    }

    /**
     * Find the toolbox, validate the context and require a capability.
     *
     * @param int $toolboxid Toolbox instance id.
     * @param string $capability Capability to require in the activity context.
     * @return array [record, context]
     */
    protected static function check_access(int $toolboxid, string $capability): array {
        global $DB;

        $record = $DB->get_record('classroomtoolbox', ['id' => $toolboxid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('classroomtoolbox', $record->id, $record->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        // Everyone must be able to open the toolbox at all; the specific capability is on top of that.
        require_capability('mod/classroomtoolbox:view', $context);
        if ($capability !== 'mod/classroomtoolbox:view') {
            require_capability($capability, $context);
        }
        if (!in_array('scoreboard', toolbox::parse($record->tools), true)) {
            throw new \moodle_exception('toolnotenabled', 'mod_classroomtoolbox');
        }
        return [$record, $context];
    }

    /**
     * Log that the scoreboard was changed.
     *
     * @param \stdClass $record Toolbox record.
     * @param \context_module $context Activity context.
     * @param string $action What happened.
     */
    protected static function log_change(\stdClass $record, \context_module $context, string $action): void {
        scoreboard_changed::create([
            'objectid' => $record->id,
            'context' => $context,
            'other' => ['action' => $action],
        ])->trigger();
    }

    /**
     * Log a change and return the fresh list.
     *
     * @param \stdClass $record Toolbox record.
     * @param \context_module $context Activity context.
     * @param string $action What happened.
     * @return array The entries.
     */
    protected static function finish(\stdClass $record, \context_module $context, string $action): array {
        self::log_change($record, $context, $action);
        return scores::get_all($record->id);
    }
}
