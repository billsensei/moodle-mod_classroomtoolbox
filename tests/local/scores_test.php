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
 * Tests for the scoreboard storage class.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\local;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(scores::class)]
/**
 * Tests for the scoreboard storage class.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\local\scores
 */
final class scores_test extends \advanced_testcase {
    /**
     * Make a toolbox with the scoreboard on.
     *
     * @return int Toolbox id.
     */
    private function toolbox(): int {
        $course = self::getDataGenerator()->create_course();
        return self::getDataGenerator()->create_module('classroomtoolbox', [
            'course' => $course->id, 'tools' => 'scoreboard',
        ])->id;
    }

    /**
     * Labels are cleaned: tags stripped, white space collapsed, length limited.
     */
    public function test_clean_label(): void {
        $this->assertSame('Team Red', scores::clean_label("  Team \n  <b>Red</b>  "));
        $this->assertSame('', scores::clean_label(' <i></i> '));
        $this->assertSame(scores::MAX_LABEL, \core_text::strlen(scores::clean_label(str_repeat('é', 300))));
    }

    /**
     * Adding skips blanks and duplicates (ignoring case), and entries come back highest points first.
     */
    public function test_add_and_order(): void {
        $this->resetAfterTest();
        $id = $this->toolbox();
        $this->assertSame(3, scores::add($id, ['Red', ' ', 'blue', 'RED', 'Green', '<b>Blue</b>']));
        $this->assertSame(0, scores::add($id, ['red', 'Blue']));
        $entries = scores::get_all($id);
        $this->assertSame(['Red', 'blue', 'Green'], array_column($entries, 'label'));

        scores::change($id, $entries[2]['id'], 5);
        scores::change($id, $entries[0]['id'], 2);
        $this->assertSame(['Green', 'Red', 'blue'], array_column(scores::get_all($id), 'label'));
        $this->assertSame([5, 2, 0], array_column(scores::get_all($id), 'score'));
    }

    /**
     * A board holds at most MAX_ENTRIES entries.
     */
    public function test_entry_limit(): void {
        $this->resetAfterTest();
        $id = $this->toolbox();
        $labels = array_map(fn($i) => "Team $i", range(1, scores::MAX_ENTRIES + 20));
        $this->assertSame(scores::MAX_ENTRIES, scores::add($id, $labels));
        $this->assertCount(scores::MAX_ENTRIES, scores::get_all($id));
        $this->assertSame(0, scores::add($id, ['One more']));
    }

    /**
     * Points can go up and down, change is limited per call, and the total stays within the limit.
     */
    public function test_change(): void {
        $this->resetAfterTest();
        $id = $this->toolbox();
        scores::add($id, ['Red']);
        $red = scores::get_all($id)[0]['id'];

        scores::change($id, $red, 3);
        scores::change($id, $red, -5);
        $this->assertSame(-2, scores::get_all($id)[0]['score']);

        scores::change($id, $red, 100000);
        $this->assertSame(-2 + scores::MAX_DELTA, scores::get_all($id)[0]['score']);

        scores::update($id, $red, 'Red', scores::LIMIT - 1);
        scores::change($id, $red, 500);
        $this->assertSame(scores::LIMIT, scores::get_all($id)[0]['score']);

        scores::update($id, $red, 'Red', -scores::LIMIT + 1);
        scores::change($id, $red, -500);
        $this->assertSame(-scores::LIMIT, scores::get_all($id)[0]['score']);
    }

    /**
     * Renaming keeps labels unique, allows a change of capitalisation, and clamps the points.
     */
    public function test_update(): void {
        $this->resetAfterTest();
        $id = $this->toolbox();
        scores::add($id, ['Red', 'Blue']);
        [$red, $blue] = array_column(scores::get_all($id), 'id');

        scores::update($id, $red, 'Crimson', 7);
        scores::update($id, $blue, 'crimson', 3);
        $byid = array_column(scores::get_all($id), null, 'id');
        $this->assertSame('Crimson', $byid[$red]['label']);
        $this->assertSame('Blue', $byid[$blue]['label'], 'A name already on the board is not taken twice');
        $this->assertSame(3, $byid[$blue]['score']);

        scores::update($id, $red, 'CRIMSON', 8);
        $this->assertSame('CRIMSON', scores::get_entry($id, $red)->label);
        scores::update($id, $red, '  ', 9);
        $this->assertSame('CRIMSON', scores::get_entry($id, $red)->label);
        scores::update($id, $red, 'x', 99999999);
        $this->assertEquals(scores::LIMIT, scores::get_entry($id, $red)->score);
    }

    /**
     * Delete, reset and delete_all, and an entry of another toolbox is never touched.
     */
    public function test_delete_reset_and_isolation(): void {
        $this->resetAfterTest();
        $a = $this->toolbox();
        $b = $this->toolbox();
        scores::add($a, ['Red', 'Blue']);
        scores::add($b, ['Other']);
        $redid = scores::get_all($a)[0]['id'];
        $otherid = scores::get_all($b)[0]['id'];
        scores::change($a, $redid, 4);
        scores::change($b, $otherid, 9);

        try {
            scores::change($a, $otherid, 1);
            $this->fail('An entry of another toolbox must not be reachable');
        } catch (\dml_missing_record_exception $e) {
            $this->assertSame(9, scores::get_all($b)[0]['score']);
        }
        $this->expectException(\dml_missing_record_exception::class);
        try {
            scores::delete($a, $otherid);
        } finally {
            $this->assertCount(1, scores::get_all($b));
            scores::reset($a);
            $this->assertSame([0, 0], array_column(scores::get_all($a), 'score'));
            $this->assertSame(9, scores::get_all($b)[0]['score']);
            scores::delete($a, $redid);
            $this->assertCount(1, scores::get_all($a));
            scores::delete_all($a);
            $this->assertCount(0, scores::get_all($a));
            $this->assertCount(1, scores::get_all($b));
        }
    }

    /**
     * Adding takes a per-toolbox lock and always lets go of it, so a second add straight afterwards works.
     */
    public function test_add_releases_its_lock(): void {
        $this->resetAfterTest();
        $id = $this->toolbox();
        scores::add($id, ['Red']);
        $factory = \core\lock\lock_config::get_lock_factory('mod_classroomtoolbox');
        $lock = $factory->get_lock('scores_' . $id, 0);
        $this->assertNotFalse($lock, 'The lock must be free after add()');
        $lock->release();
        $this->assertSame(1, scores::add($id, ['Blue']));
    }

    /**
     * Renaming does not touch the points and setting points does not touch the name, so one teacher's rename cannot
     * undo another teacher's points.
     */
    public function test_update_keeps_what_is_not_given(): void {
        $this->resetAfterTest();
        $id = $this->toolbox();
        scores::add($id, ['Red']);
        $red = scores::get_all($id)[0]['id'];
        scores::change($id, $red, 5);

        scores::update($id, $red, 'Crimson', null);
        $entry = scores::get_entry($id, $red);
        $this->assertSame('Crimson', $entry->label);
        $this->assertEquals(5, $entry->score);

        scores::update($id, $red, null, 9);
        $entry = scores::get_entry($id, $red);
        $this->assertSame('Crimson', $entry->label);
        $this->assertEquals(9, $entry->score);
    }
}
