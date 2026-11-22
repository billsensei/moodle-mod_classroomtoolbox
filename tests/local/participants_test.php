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
 * Tests for the participants helper: who may be put on the wheel or into groups.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\local;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(participants::class)]
/**
 * Tests for the participants helper.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\local\participants
 */
final class participants_test extends \advanced_testcase {
    /**
     * Create a course with three students and one teacher and a toolbox.
     *
     * @param array $record Extra toolbox fields.
     * @return array [course, cm, record, context, students]
     */
    private function setup_course(array $record = []): array {
        global $DB;
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $students = [];
        foreach (['Alice' => 'Anderson', 'Bob' => 'Brown', 'Carla' => 'Chen'] as $first => $last) {
            $user = $gen->create_user(['firstname' => $first, 'lastname' => $last]);
            $gen->enrol_user($user->id, $course->id, 'student');
            $students[] = $user;
        }
        $teacher = $gen->create_user(['firstname' => 'Tina', 'lastname' => 'Teacher']);
        $gen->enrol_user($teacher->id, $course->id, 'editingteacher');
        $toolbox = $gen->create_module('classroomtoolbox', ['course' => $course->id] + $record);
        $cm = get_fast_modinfo($course)->get_cm($toolbox->cmid);
        $context = \context_module::instance($cm->id);
        $row = $DB->get_record('classroomtoolbox', ['id' => $toolbox->id], '*', MUST_EXIST);
        return [$course, $cm, $row, $context, $students, $teacher];
    }

    /**
     * Call the name builder.
     *
     * @param \cm_info $cm Course module.
     * @param \stdClass $row Activity record.
     * @param \context_module $context Context.
     * @return array [entries, groups]
     */
    private function entries(\cm_info $cm, \stdClass $row, \context_module $context): array {
        return participants::get($cm, $row, $context);
    }

    /**
     * Students are listed by name, teachers are not, and typed names are always added.
     */
    public function test_students_and_typed_names(): void {
        $this->resetAfterTest();
        [$course, $cm, $row, $context, $students] = $this->setup_course(['names' => "Visitor\nalice anderson"]);
        $this->setUser($students[0]);

        [$entries, $groups] = $this->entries($cm, $row, $context);
        $names = array_column($entries, 'name');
        $this->assertSame(['Alice Anderson', 'Bob Brown', 'Carla Chen', 'Visitor'], $names);
        $this->assertSame([], $groups);
        $this->assertNull($entries[3]['groups']);
    }

    /**
     * Turning off "use participants" leaves only the typed names.
     */
    public function test_participants_switched_off(): void {
        $this->resetAfterTest();
        [$course, $cm, $row, $context, $students] = $this->setup_course(['usenrolled' => 0, 'names' => 'Visitor']);
        $this->setUser($students[0]);
        [$entries] = $this->entries($cm, $row, $context);
        $this->assertSame(['Visitor'], array_column($entries, 'name'));
    }

    /**
     * Guests never get participant names.
     */
    public function test_guest_gets_no_participants(): void {
        $this->resetAfterTest();
        [$course, $cm, $row, $context] = $this->setup_course(['names' => 'Visitor']);
        $this->setGuestUser();
        [$entries] = $this->entries($cm, $row, $context);
        $this->assertSame(['Visitor'], array_column($entries, 'name'));
    }

    /**
     * Without the right to see the participants list, no participant names are sent.
     */
    public function test_viewparticipants_required(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cm, $row, $context, $students] = $this->setup_course(['names' => 'Visitor']);
        $studentrole = $DB->get_field('role', 'id', ['shortname' => 'student']);
        assign_capability('moodle/course:viewparticipants', CAP_PROHIBIT, $studentrole, $context->id, true);
        $this->setUser($students[0]);
        [$entries] = $this->entries($cm, $row, $context);
        $this->assertSame(['Visitor'], array_column($entries, 'name'));
    }

    /**
     * In separate groups a student only gets their own group; the teacher gets all groups plus "not in any group".
     */
    public function test_separate_groups(): void {
        $this->resetAfterTest();
        [$course, $cm, $row, $context, $students, $teacher] = $this->setup_course(['groupmode' => SEPARATEGROUPS]);
        $gen = $this->getDataGenerator();
        $ga = $gen->create_group(['courseid' => $course->id, 'name' => 'Group A']);
        $gb = $gen->create_group(['courseid' => $course->id, 'name' => 'Group B']);
        $gen->create_group_member(['groupid' => $ga->id, 'userid' => $students[0]->id]);
        $gen->create_group_member(['groupid' => $ga->id, 'userid' => $students[1]->id]);
        $gen->create_group_member(['groupid' => $gb->id, 'userid' => $students[2]->id]);
        rebuild_course_cache($course->id, true);
        $cm = get_fast_modinfo($course)->get_cm($cm->id);

        $this->setUser($students[0]);
        [$entries, $groups] = $this->entries($cm, $row, $context);
        $this->assertSame(['Alice Anderson', 'Bob Brown'], array_column($entries, 'name'));
        $this->assertSame(['Group A'], array_column($groups, 'name'));

        $this->setAdminUser();
        [$entries, $groups] = $this->entries($cm, $row, $context);
        $this->assertSame(['Group A', 'Group B'], array_column($groups, 'name'));
        $this->assertCount(3, $entries);
    }
}
