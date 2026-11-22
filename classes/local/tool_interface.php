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

namespace mod_classroomtoolbox\local;

/**
 * A tool shown as a tab in the Classroom Toolbox.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface tool_interface {
    /**
     * Tool id, used in the database, in template ids and in lang strings (tool_<id>).
     *
     * @return string
     */
    public static function get_id(): string;

    /**
     * Render the tool's HTML and queue its JavaScript.
     *
     * @param \cm_info $cm Course module.
     * @param \stdClass $course Course.
     * @param \stdClass $record Activity record.
     * @param \context_module $context Module context.
     * @return string HTML produced by Moodle templates.
     */
    public function render(\cm_info $cm, \stdClass $course, \stdClass $record, \context_module $context): string;
}
