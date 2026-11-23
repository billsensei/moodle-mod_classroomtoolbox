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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\userlist;
use core_privacy\tests\provider_testcase;
use mod_classroomtoolbox\local\scores;
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
final class provider_test extends provider_testcase {
    /**
     * The stored free text is declared, and every declared string exists.
     */
    public function test_metadata_declares_the_stored_text(): void {
        $collection = provider::get_metadata(new collection('mod_classroomtoolbox'));
        $items = [];
        foreach ($collection->get_collection() as $item) {
            $items[$item->get_name()] = $item;
        }
        $this->assertSame(['classroomtoolbox', 'classroomtoolbox_scores'], array_keys($items));
        $this->assertSame(['names'], array_keys($items['classroomtoolbox']->get_privacy_fields()));
        $this->assertSame(['label', 'score'], array_keys($items['classroomtoolbox_scores']->get_privacy_fields()));
        foreach ($items as $item) {
            $this->assertTrue(get_string_manager()->string_exists($item->get_summary(), 'mod_classroomtoolbox'));
            foreach ($item->get_privacy_fields() as $key) {
                $this->assertTrue(get_string_manager()->string_exists($key, 'mod_classroomtoolbox'), $key);
            }
        }
    }

    /**
     * The tables really have no user columns, which is why a request about a user finds nothing.
     */
    public function test_tables_have_no_user_columns(): void {
        global $DB;
        foreach (['classroomtoolbox', 'classroomtoolbox_scores'] as $table) {
            $columns = array_keys($DB->get_columns($table));
            $this->assertNotContains('userid', $columns, $table);
            $this->assertNotContains('usermodified', $columns, $table);
            $this->assertNotContains('createdby', $columns, $table);
        }
    }

    /**
     * No contexts and no users are reported for anyone, even a teacher who typed the entries.
     */
    public function test_nothing_is_linked_to_a_user(): void {
        $this->resetAfterTest();
        $gen = self::getDataGenerator();
        $course = $gen->create_course();
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $toolbox = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'scoreboard']);
        scores::add($toolbox->id, ['Red']);
        $context = \context_module::instance($toolbox->cmid);

        $this->assertCount(0, provider::get_contexts_for_userid($teacher->id));
        $userlist = new userlist($context, 'mod_classroomtoolbox');
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);
    }

    /**
     * Deleting all data in an activity context clears that activity's scoreboard and extra names, and nothing else.
     */
    public function test_delete_all_in_context_clears_only_that_scoreboard(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = self::getDataGenerator();
        $course = $gen->create_course();
        $a = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'scoreboard', 'names' => 'Visitor']);
        $b = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'scoreboard', 'names' => 'Guest']);
        scores::add($a->id, ['Red', 'Blue']);
        scores::add($b->id, ['Green']);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));
        $this->assertCount(2, scores::get_all($a->id), 'A course context is not an activity context');

        provider::delete_data_for_all_users_in_context(\context_module::instance($a->cmid));
        $this->assertCount(0, scores::get_all($a->id));
        $this->assertCount(1, scores::get_all($b->id));
        $this->assertSame('', $DB->get_field('classroomtoolbox', 'names', ['id' => $a->id]));
        $this->assertSame('Guest', $DB->get_field('classroomtoolbox', 'names', ['id' => $b->id]));
    }
}
