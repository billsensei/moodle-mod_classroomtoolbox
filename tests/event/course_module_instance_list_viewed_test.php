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
 * Tests for the event fired when the list of toolboxes in a course is viewed.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\event;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(course_module_instance_list_viewed::class)]
/**
 * Tests for the list-viewed event.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\event\course_module_instance_list_viewed
 */
final class course_module_instance_list_viewed_test extends \advanced_testcase {
    /**
     * The event is a course-level "list viewed" event and can be triggered.
     */
    public function test_event(): void {
        $this->resetAfterTest();
        $course = self::getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $sink = $this->redirectEvents();
        $event = course_module_instance_list_viewed::create(['context' => $context]);
        $event->add_record_snapshot('course', $course);
        $event->trigger();
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(course_module_instance_list_viewed::class, $events[0]);
        $this->assertEquals($context, $events[0]->get_context());
        $this->assertNotEmpty($events[0]->get_name());
    }
}
