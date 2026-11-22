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
 * Web services (AJAX only) for the Classroom Toolbox scoreboard.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_classroomtoolbox_get_scores' => [
        'classname' => 'mod_classroomtoolbox\external\get_scores',
        'description' => 'List the scoreboard entries of a Classroom Toolbox.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/classroomtoolbox:view',
    ],
    'mod_classroomtoolbox_add_scores' => [
        'classname' => 'mod_classroomtoolbox\external\add_scores',
        'description' => 'Add entries (teams or names) to the scoreboard.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classroomtoolbox:managescores',
    ],
    'mod_classroomtoolbox_change_score' => [
        'classname' => 'mod_classroomtoolbox\external\change_score',
        'description' => 'Add or subtract points for one scoreboard entry.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classroomtoolbox:managescores',
    ],
    'mod_classroomtoolbox_update_score' => [
        'classname' => 'mod_classroomtoolbox\external\update_score',
        'description' => 'Rename a scoreboard entry and set its points.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classroomtoolbox:managescores',
    ],
    'mod_classroomtoolbox_delete_score' => [
        'classname' => 'mod_classroomtoolbox\external\delete_score',
        'description' => 'Remove one scoreboard entry.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classroomtoolbox:managescores',
    ],
    'mod_classroomtoolbox_reset_scores' => [
        'classname' => 'mod_classroomtoolbox\external\reset_scores',
        'description' => 'Set every scoreboard entry back to zero points.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/classroomtoolbox:managescores',
    ],
];
