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
 * Tests for the mod_namewheel view event and completion on view.
 *
 * @package    mod_namewheel
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_namewheel\event;

use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/namewheel/lib.php');

#[CoversClass(course_module_viewed::class)]
/**
 * Tests for the view event.
 *
 * @package    mod_namewheel
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_namewheel\event\course_module_viewed
 */
final class course_module_viewed_test extends \advanced_testcase {
    /**
     * Viewing the wheel triggers the standard event for this activity.
     */
    public function test_view_event(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $wheel = $this->getDataGenerator()->create_module('namewheel', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('namewheel', $wheel->id);
        $context = \context_module::instance($cm->id);
        $record = $DB->get_record('namewheel', ['id' => $wheel->id], '*', MUST_EXIST);

        $sink = $this->redirectEvents();
        namewheel_view($record, $course, $cm, $context);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(course_module_viewed::class, $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertEquals($wheel->id, $event->objectid);
        $this->assertSame('namewheel', $event->objecttable);
    }
}
