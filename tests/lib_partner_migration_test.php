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

namespace mod_classroomtoolbox;

use PHPUnit\Framework\Attributes\CoversNothing;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/classroomtoolbox/lib.php');

/**
 * Tests for the data change that retires the separate partner wheel tool.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
#[CoversNothing]
final class lib_partner_migration_test extends \advanced_testcase {
    /**
     * The separate partner wheel tool became an option on the name wheel, so activities that had it keep the feature.
     */
    public function test_partner_tool_becomes_the_name_wheel(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = self::getDataGenerator();
        $course = $gen->create_course();
        $both = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'timer']);
        $only = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'timer']);
        $other = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'counter']);
        // Rows as the previous version stored them: "partners" is no longer a known tool, so write it directly.
        $DB->set_field('classroomtoolbox', 'tools', 'wheel,partners,timer', ['id' => $both->id]);
        $DB->set_field('classroomtoolbox', 'tools', 'partners,timer', ['id' => $only->id]);

        classroomtoolbox_replace_partner_tool();
        classroomtoolbox_replace_partner_tool(); // Safe to repeat.

        $this->assertSame('wheel,timer', $DB->get_field('classroomtoolbox', 'tools', ['id' => $both->id]));
        $this->assertSame('wheel,timer', $DB->get_field('classroomtoolbox', 'tools', ['id' => $only->id]));
        $this->assertSame('counter', $DB->get_field('classroomtoolbox', 'tools', ['id' => $other->id]));
    }
}
