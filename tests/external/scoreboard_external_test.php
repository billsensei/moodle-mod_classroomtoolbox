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
 * Tests for the scoreboard web services.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\external;

use core_external\external_api;
use mod_classroomtoolbox\event\scoreboard_changed;
use mod_classroomtoolbox\local\scores;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(scores_base::class)]
#[CoversClass(get_scores::class)]
#[CoversClass(add_scores::class)]
#[CoversClass(change_score::class)]
#[CoversClass(update_score::class)]
#[CoversClass(delete_score::class)]
#[CoversClass(reset_scores::class)]
#[CoversClass(scoreboard_changed::class)]
/**
 * Tests for the scoreboard web services: who may call them, what they return and what they log.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\external\scores_base
 * @covers \mod_classroomtoolbox\external\get_scores
 * @covers \mod_classroomtoolbox\external\add_scores
 * @covers \mod_classroomtoolbox\external\change_score
 * @covers \mod_classroomtoolbox\external\update_score
 * @covers \mod_classroomtoolbox\external\delete_score
 * @covers \mod_classroomtoolbox\external\reset_scores
 * @covers \mod_classroomtoolbox\event\scoreboard_changed
 */
final class scoreboard_external_test extends \advanced_testcase {
    /** @var \stdClass Course. */
    private $course;
    /** @var \stdClass Teacher. */
    private $teacher;
    /** @var \stdClass Student. */
    private $student;
    /** @var \stdClass Toolbox with the scoreboard on. */
    private $toolbox;

    /**
     * Course, teacher, student and a toolbox with the scoreboard on.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $gen = self::getDataGenerator();
        $this->course = $gen->create_course();
        $this->teacher = $gen->create_and_enrol($this->course, 'editingteacher');
        $this->student = $gen->create_and_enrol($this->course, 'student');
        $this->toolbox = $gen->create_module('classroomtoolbox', ['course' => $this->course->id, 'tools' => 'wheel,scoreboard']);
    }

    /**
     * Run a service and check its result against the declared return structure.
     *
     * @param string $class Service class.
     * @param array $args Arguments.
     * @return array Cleaned result.
     */
    private function call(string $class, array $args): array {
        $result = $class::execute(...$args);
        return external_api::clean_returnvalue($class::execute_returns(), $result);
    }

    /**
     * A teacher can run the whole life of a scoreboard and every call returns the fresh list.
     */
    public function test_teacher_flow(): void {
        $this->setUser($this->teacher);
        $id = $this->toolbox->id;

        $list = $this->call(add_scores::class, [$id, ['Red', 'Blue', 'red']]);
        $this->assertSame(['Red', 'Blue'], array_column($list, 'label'));

        $red = $list[0]['id'];
        $list = $this->call(change_score::class, [$id, $red, 3]);
        $this->assertSame(3, $list[0]['score']);

        $list = $this->call(update_score::class, [$id, $red, 'Crimson', 10]);
        $this->assertSame('Crimson', $list[0]['label']);
        $this->assertSame(10, $list[0]['score']);

        $list = $this->call(delete_score::class, [$id, $red]);
        $this->assertSame(['Blue'], array_column($list, 'label'));

        $this->call(change_score::class, [$id, $list[0]['id'], 5]);
        $list = $this->call(reset_scores::class, [$id]);
        $this->assertSame([0], array_column($list, 'score'));
        $this->assertSame($list, $this->call(get_scores::class, [$id]));
    }

    /**
     * Labels are plain text: tags are removed.
     */
    public function test_labels_are_plain_text(): void {
        $this->setUser($this->teacher);
        $list = $this->call(add_scores::class, [$this->toolbox->id, ['<script>alert(1)</script>Red', 'Team <b>Blue</b>']]);
        foreach ($list as $entry) {
            $this->assertStringNotContainsString('<', $entry['label']);
            $this->assertStringNotContainsString('>', $entry['label']);
        }
        $this->assertContains('Team Blue', array_column($list, 'label'));
        $list = $this->call(update_score::class, [$this->toolbox->id, $list[0]['id'], '<img src=x onerror=alert(1)>Crimson', 1]);
        $this->assertStringNotContainsString('<', implode('', array_column($list, 'label')));
    }

    /**
     * A student can read the board but none of the services that change it.
     */
    public function test_student_is_read_only(): void {
        $id = $this->toolbox->id;
        $this->setUser($this->teacher);
        $list = $this->call(add_scores::class, [$id, ['Red']]);
        $entry = $list[0]['id'];

        $this->setUser($this->student);
        $this->assertSame($list, $this->call(get_scores::class, [$id]));
        $writes = [
            [add_scores::class, [$id, ['Sneaky']]],
            [change_score::class, [$id, $entry, 5]],
            [update_score::class, [$id, $entry, 'Hacked', 99]],
            [delete_score::class, [$id, $entry]],
            [reset_scores::class, [$id]],
        ];
        foreach ($writes as [$class, $args]) {
            try {
                $class::execute(...$args);
                $this->fail($class . ' must require mod/classroomtoolbox:managescores');
            } catch (\required_capability_exception $e) {
                $this->assertSame('nopermissions', $e->errorcode);
            }
        }
        $this->assertSame([['id' => $entry, 'label' => 'Red', 'score' => 0]], scores::get_all($id));
    }

    /**
     * People who are not in the course cannot read the board either.
     */
    public function test_outsider_and_guest_cannot_read(): void {
        $id = $this->toolbox->id;
        $this->setUser(self::getDataGenerator()->create_user());
        try {
            get_scores::execute($id);
            $this->fail('A user outside the course must not read the board');
        } catch (\moodle_exception $e) {
            $this->assertNotEmpty($e->errorcode);
        }
        $this->setGuestUser();
        $this->expectException(\moodle_exception::class);
        get_scores::execute($id);
    }

    /**
     * An entry id from another toolbox cannot be reached through this toolbox's id.
     */
    public function test_other_toolbox_entry_is_not_reachable(): void {
        $this->setUser($this->teacher);
        $other = self::getDataGenerator()->create_module('classroomtoolbox', [
            'course' => $this->course->id, 'tools' => 'scoreboard',
        ]);
        $otherlist = $this->call(add_scores::class, [$other->id, ['Theirs']]);
        $this->call(add_scores::class, [$this->toolbox->id, ['Ours']]);

        foreach ([change_score::class => [5], update_score::class => ['x', 5], delete_score::class => []] as $class => $extra) {
            try {
                $class::execute($this->toolbox->id, $otherlist[0]['id'], ...$extra);
                $this->fail($class . ' reached an entry of another toolbox');
            } catch (\dml_missing_record_exception $e) {
                $this->assertSame([['id' => $otherlist[0]['id'], 'label' => 'Theirs', 'score' => 0]], scores::get_all($other->id));
            }
        }
    }

    /**
     * The services refuse to work when the scoreboard is not switched on for the toolbox.
     */
    public function test_scoreboard_must_be_on(): void {
        $off = self::getDataGenerator()->create_module('classroomtoolbox', [
            'course' => $this->course->id, 'tools' => 'wheel',
        ]);
        $this->setUser($this->teacher);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('toolnotenabled', 'mod_classroomtoolbox'));
        add_scores::execute($off->id, ['Red']);
    }

    /**
     * Every change leaves an event so there is a trail of who changed the board.
     */
    public function test_changes_are_logged(): void {
        $this->setUser($this->teacher);
        $id = $this->toolbox->id;
        $sink = $this->redirectEvents();
        $list = $this->call(add_scores::class, [$id, ['Red']]);
        $this->call(change_score::class, [$id, $list[0]['id'], 1]);
        $this->call(update_score::class, [$id, $list[0]['id'], 'Red', 4]);
        $this->call(delete_score::class, [$id, $list[0]['id']]);
        $this->call(reset_scores::class, [$id]);
        $this->call(get_scores::class, [$id]);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(5, $events, 'Reading is not logged, the five writes are');
        $this->assertSame(['add', 'points', 'edit', 'delete', 'reset'], array_map(fn($e) => $e->other['action'], $events));
        $this->assertInstanceOf(scoreboard_changed::class, $events[0]);
        $cm = get_coursemodule_from_instance('classroomtoolbox', $id);
        $this->assertEquals(\context_module::instance($cm->id), $events[0]->get_context());
        $this->assertEquals($this->teacher->id, $events[0]->userid);
        $this->assertNotEmpty($events[0]->get_description());
        $this->assertNotEmpty(scoreboard_changed::get_name());
    }

    /**
     * Teachers get the change capability by default; students do not.
     */
    public function test_capability_defaults(): void {
        foreach (['teacher', 'editingteacher', 'manager'] as $archetype) {
            $this->assertSame(CAP_ALLOW, get_default_capabilities($archetype)['mod/classroomtoolbox:managescores'], $archetype);
        }
        $this->assertArrayNotHasKey('mod/classroomtoolbox:managescores', get_default_capabilities('student'));
    }

    /**
     * Renaming through the service leaves the points alone, even if they changed since the browser last looked.
     */
    public function test_rename_does_not_overwrite_points(): void {
        $this->setUser($this->teacher);
        $id = $this->toolbox->id;
        $list = $this->call(add_scores::class, [$id, ['Red']]);
        $entry = $list[0]['id'];
        $this->call(change_score::class, [$id, $entry, 3]);

        $list = $this->call(update_score::class, [$id, $entry, 'Crimson']);
        $this->assertSame('Crimson', $list[0]['label']);
        $this->assertSame(3, $list[0]['score']);

        $list = $this->call(update_score::class, [$id, $entry, null, 8]);
        $this->assertSame('Crimson', $list[0]['label']);
        $this->assertSame(8, $list[0]['score']);
    }

    /**
     * An add that adds nothing (only duplicates or blanks) leaves no event in the log.
     */
    public function test_add_that_changes_nothing_is_not_logged(): void {
        $this->setUser($this->teacher);
        $id = $this->toolbox->id;
        $this->call(add_scores::class, [$id, ['Red']]);

        $sink = $this->redirectEvents();
        $this->call(add_scores::class, [$id, ['red', ' ']]);
        $this->assertCount(0, $sink->get_events());
        $this->call(add_scores::class, [$id, ['Blue']]);
        $this->assertCount(1, $sink->get_events());
        $sink->close();
    }

    /**
     * The board is capped at 100 entries and labels at 100 characters, through the service as well.
     */
    public function test_limits_through_the_service(): void {
        $this->setUser($this->teacher);
        $id = $this->toolbox->id;
        $labels = array_map(fn($i) => "Team $i", range(1, scores::MAX_ENTRIES + 25));
        $list = $this->call(add_scores::class, [$id, $labels]);
        $this->assertCount(scores::MAX_ENTRIES, $list);

        $other = self::getDataGenerator()->create_module('classroomtoolbox', [
            'course' => $this->course->id, 'tools' => 'scoreboard',
        ]);
        $list = $this->call(add_scores::class, [$other->id, [str_repeat('x', 300)]]);
        $this->assertSame(scores::MAX_LABEL, \core_text::strlen($list[0]['label']));
    }

    /**
     * A student cannot read the board of an activity that is hidden from them.
     */
    public function test_hidden_activity_is_not_readable_by_a_student(): void {
        $cm = get_coursemodule_from_instance('classroomtoolbox', $this->toolbox->id);
        set_coursemodule_visible($cm->id, 0);
        $this->setUser($this->student);
        $this->expectException(\moodle_exception::class);
        get_scores::execute($this->toolbox->id);
    }
}
