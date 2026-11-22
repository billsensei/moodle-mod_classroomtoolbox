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
 * Tests that every tool renders.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\local\tools;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(wheel::class)]
#[CoversClass(groups::class)]
#[CoversClass(stopwatch::class)]
#[CoversClass(timer::class)]
#[CoversClass(counter::class)]
#[CoversClass(scoreboard::class)]
/**
 * Tests that every tool renders.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\local\tools\wheel
 * @covers \mod_classroomtoolbox\local\tools\groups
 * @covers \mod_classroomtoolbox\local\tools\stopwatch
 * @covers \mod_classroomtoolbox\local\tools\timer
 * @covers \mod_classroomtoolbox\local\tools\counter
 * @covers \mod_classroomtoolbox\local\tools\scoreboard
 */
final class tools_test extends \advanced_testcase {
    /**
     * Each tool renders its own region, and the ids match what the registry says.
     */
    public function test_every_tool_renders(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_user();
        $gen->enrol_user($student->id, $course->id, 'student');
        $toolbox = $gen->create_module('classroomtoolbox', [
            'course' => $course->id,
            'tools' => implode(',', \mod_classroomtoolbox\local\toolbox::all_ids()),
            'timerpresets' => '0.5,2',
        ]);
        $cm = get_fast_modinfo($course)->get_cm($toolbox->cmid);
        $context = \context_module::instance($cm->id);
        $row = $DB->get_record('classroomtoolbox', ['id' => $toolbox->id], '*', MUST_EXIST);
        $this->setUser($student);
        $PAGE->set_url('/mod/classroomtoolbox/view.php', ['id' => $cm->id]);

        $expected = [
            'wheel' => 'data-region="wheel-canvas"',
            'groups' => 'data-region="groupgen-names"',
            'stopwatch' => 'data-region="sw-display"',
            'timer' => 'data-region="timer-list"',
            'counter' => 'data-region="counter-list"',
            'scoreboard' => 'data-region="score-list"',
        ];
        $tools = \mod_classroomtoolbox\local\toolbox::enabled($row);
        $this->assertSame(array_keys($expected), array_keys($tools));
        foreach ($tools as $id => $tool) {
            $this->assertSame($id, $tool::get_id());
            $html = $tool->render($cm, $course, $row, $context);
            $this->assertStringContainsString($expected[$id], $html, $id);
            // The page title is the h1, so tool headings start at h2 (no skipped level).
            $this->assertStringNotContainsString('<h3', $html, $id);
        }
    }

    /**
     * Only people who may change the scoreboard get the controls for it.
     */
    public function test_scoreboard_controls_are_for_teachers(): void {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $student = $gen->create_and_enrol($course, 'student');
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $toolbox = $gen->create_module('classroomtoolbox', ['course' => $course->id, 'tools' => 'scoreboard']);
        $cm = get_fast_modinfo($course)->get_cm($toolbox->cmid);
        $context = \context_module::instance($cm->id);
        $row = $DB->get_record('classroomtoolbox', ['id' => $toolbox->id], '*', MUST_EXIST);
        $PAGE->set_url('/mod/classroomtoolbox/view.php', ['id' => $cm->id]);

        $this->setUser($student);
        $html = (new scoreboard())->render($cm, $course, $row, $context);
        $this->assertStringContainsString('data-region="score-list"', $html);
        $this->assertStringNotContainsString('score-add-form', $html);
        $this->assertStringNotContainsString('score-reset', $html);

        $this->setUser($teacher);
        $html = (new scoreboard())->render($cm, $course, $row, $context);
        $this->assertStringContainsString('score-add-form', $html);
        $this->assertStringContainsString('score-reset', $html);
    }
}
