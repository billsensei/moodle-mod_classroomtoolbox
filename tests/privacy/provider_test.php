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
 * Tests for the mod_classroomtoolbox privacy provider.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\privacy;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(provider::class)]
/**
 * Tests for the privacy provider.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The plugin is a null provider and its reason string exists.
     */
    public function test_null_provider(): void {
        $this->assertInstanceOf(\core_privacy\local\metadata\null_provider::class, new provider());
        $reason = provider::get_reason();
        $this->assertSame('privacy:metadata', $reason);
        $this->assertTrue(get_string_manager()->string_exists($reason, 'mod_classroomtoolbox'));
    }

    /**
     * The plugin stores no user ids, which is what justifies the null provider.
     */
    public function test_table_has_no_user_columns(): void {
        global $DB;
        $columns = array_keys($DB->get_columns('classroomtoolbox'));
        $this->assertNotContains('userid', $columns);
        $this->assertNotContains('user', $columns);
    }
}
