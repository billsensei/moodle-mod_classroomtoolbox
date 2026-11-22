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
 * Tests for backing up and restoring a mod_classroomtoolbox activity (via duplication).
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox;

use PHPUnit\Framework\Attributes\CoversNothing;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

#[CoversNothing]
/**
 * Backup and restore tests.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_test extends \advanced_testcase {
    /**
     * Duplicating an activity keeps its settings, including the colour scheme and typed names.
     */
    public function test_duplicate_keeps_settings(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $wheel = $this->getDataGenerator()->create_module('classroomtoolbox', [
            'course' => $course->id,
            'name' => 'Original',
            'names' => "Guest A\nGuest B",
            'colorscheme' => 'highcontrast',
            'usenrolled' => 0,
            'tools' => 'wheel,timer',
            'timerpresets' => '0.5,2',
        ]);
        \mod_classroomtoolbox\local\scores::add($wheel->id, ['Red', 'Blue']);
        $first = \mod_classroomtoolbox\local\scores::get_all($wheel->id)[0];
        \mod_classroomtoolbox\local\scores::update($wheel->id, $first['id'], $first['label'], 7);
        $cm = get_coursemodule_from_instance('classroomtoolbox', $wheel->id, $course->id);

        $newcm = duplicate_module($course, get_fast_modinfo($course)->get_cm($cm->id));
        $this->assertNotEmpty($newcm);

        $records = $DB->get_records('classroomtoolbox', ['course' => $course->id], 'id');
        $this->assertCount(2, $records);
        $copy = end($records);
        $this->assertNotEquals($wheel->id, $copy->id);
        $this->assertSame('highcontrast', $copy->colorscheme);
        $this->assertSame("Guest A\nGuest B", $copy->names);
        $this->assertEquals(0, $copy->usenrolled);
        $this->assertSame('wheel,timer', $copy->tools);
        $this->assertSame('0.5,2', $copy->timerpresets);
        $copied = \mod_classroomtoolbox\local\scores::get_all($copy->id);
        $this->assertSame(['Red', 'Blue'], array_column($copied, 'label'));
        $this->assertSame([7, 0], array_column($copied, 'score'));
        $this->assertCount(2, \mod_classroomtoolbox\local\scores::get_all($wheel->id), 'The original keeps its own entries');
    }

    /**
     * A backup whose names list is longer than the form allows is trimmed when restored.
     */
    public function test_restore_limits_the_names_list(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $wheel = $this->getDataGenerator()->create_module('classroomtoolbox', ['course' => $course->id]);
        // Written straight to the table, as a hand-edited backup would be.
        $names = implode("\n", range(1, CLASSROOMTOOLBOX_MAX_NAMES + 300));
        $DB->set_field('classroomtoolbox', 'names', $names, ['id' => $wheel->id]);
        $cm = get_coursemodule_from_instance('classroomtoolbox', $wheel->id, $course->id);

        duplicate_module($course, get_fast_modinfo($course)->get_cm($cm->id));
        $records = $DB->get_records('classroomtoolbox', ['course' => $course->id], 'id');
        $copy = end($records);
        $this->assertNotEquals($wheel->id, $copy->id);
        $this->assertCount(CLASSROOMTOOLBOX_MAX_NAMES, explode("\n", $copy->names));
    }
}
