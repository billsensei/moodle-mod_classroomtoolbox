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
 * Tests for the mod_classroomtoolbox library functions.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox;

use PHPUnit\Framework\Attributes\CoversFunction;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

#[CoversFunction('classroomtoolbox_parse_names')]
#[CoversFunction('classroomtoolbox_clean_color_scheme')]
#[CoversFunction('classroomtoolbox_color_schemes')]
#[CoversFunction('classroomtoolbox_add_instance')]
#[CoversFunction('classroomtoolbox_update_instance')]
#[CoversFunction('classroomtoolbox_delete_instance')]
#[CoversFunction('classroomtoolbox_supports')]
#[CoversFunction('classroomtoolbox_tools_from_data')]
/**
 * Tests for the module library functions.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers ::classroomtoolbox_parse_names
 * @covers ::classroomtoolbox_clean_color_scheme
 * @covers ::classroomtoolbox_color_schemes
 * @covers ::classroomtoolbox_add_instance
 * @covers ::classroomtoolbox_update_instance
 * @covers ::classroomtoolbox_delete_instance
 * @covers ::classroomtoolbox_supports
 * @covers ::classroomtoolbox_tools_from_data
 */
final class lib_test extends \advanced_testcase {
    /**
     * Names are split on any line ending, trimmed, and blanks are dropped.
     */
    public function test_parse_names(): void {
        $this->assertSame(['Ann', 'Bob Smith', 'Cy'], classroomtoolbox_parse_names("Ann\r\n  Bob Smith \n\n\nCy\r"));
        $this->assertSame([], classroomtoolbox_parse_names(" \n \r\n"));
        $this->assertSame(['$& $1'], classroomtoolbox_parse_names('$& $1'));
    }

    /**
     * Unknown, empty and null scheme ids fall back to classic.
     */
    public function test_clean_color_scheme(): void {
        foreach (array_keys(classroomtoolbox_color_schemes()) as $id) {
            $this->assertSame($id, classroomtoolbox_clean_color_scheme($id));
        }
        $this->assertSame('classic', classroomtoolbox_clean_color_scheme('nope'));
        $this->assertSame('classic', classroomtoolbox_clean_color_scheme(''));
        $this->assertSame('classic', classroomtoolbox_clean_color_scheme(null));
    }

    /**
     * Every scheme has valid hex colours, a text colour and a distinct neighbour for each wedge.
     */
    public function test_color_schemes_are_well_formed(): void {
        $schemes = classroomtoolbox_color_schemes();
        $this->assertEqualsCanonicalizing(['classic', 'ocean', 'sunset', 'pastel', 'highcontrast'], array_keys($schemes));
        foreach ($schemes as $id => $scheme) {
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $scheme['text'], $id);
            $this->assertGreaterThanOrEqual(6, count($scheme['colors']), $id);
            foreach ($scheme['colors'] as $i => $colour) {
                $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $colour, "$id colour $i");
                $next = $scheme['colors'][($i + 1) % count($scheme['colors'])];
                $this->assertNotSame($colour, $next, "$id has equal neighbours at $i");
            }
        }
    }

    /**
     * The high contrast scheme keeps white text at 7:1 or better on every colour.
     */
    public function test_highcontrast_meets_wcag_aaa(): void {
        $luminance = function (string $hex): float {
            $channels = array_map(function ($part) {
                $c = hexdec($part) / 255;
                return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
            }, str_split(substr($hex, 1), 2));
            return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        };
        $scheme = classroomtoolbox_color_schemes()['highcontrast'];
        $this->assertSame('#ffffff', $scheme['text']);
        foreach ($scheme['colors'] as $colour) {
            $ratio = 1.05 / ($luminance($colour) + 0.05);
            $this->assertGreaterThanOrEqual(7.0, $ratio, $colour);
        }
    }

    /**
     * Add, update and delete work and clean the scheme.
     */
    public function test_instance_crud(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $wheel = $this->getDataGenerator()->create_module('classroomtoolbox', [
            'course' => $course->id,
            'names' => "A\nB",
            'colorscheme' => 'bogus',
        ]);
        $record = $DB->get_record('classroomtoolbox', ['id' => $wheel->id], '*', MUST_EXIST);
        $this->assertSame('classic', $record->colorscheme);
        $this->assertSame("A\nB", $record->names);
        $this->assertSame('wheel', $record->tools);
        $this->assertEquals(1, $record->usenrolled);

        $data = (object) ['instance' => $wheel->id, 'name' => 'Renamed', 'intro' => '', 'introformat' => FORMAT_HTML,
            'usenrolled' => 0, 'names' => 'Z', 'colorscheme' => 'ocean', 'tool_wheel' => 1];
        $this->assertTrue(classroomtoolbox_update_instance($data));
        $record = $DB->get_record('classroomtoolbox', ['id' => $wheel->id], '*', MUST_EXIST);
        $this->assertSame('ocean', $record->colorscheme);
        $this->assertEquals(0, $record->usenrolled);
        $this->assertSame('Renamed', $record->name);
        $this->assertSame('wheel', $record->tools);
        $this->assertSame('wheel', classroomtoolbox_tools_from_data((object) ['tool_wheel' => 1, 'tool_bogus' => 1]));
        $this->assertSame('wheel', classroomtoolbox_tools_from_data((object) []));

        $this->assertTrue(classroomtoolbox_delete_instance($wheel->id));
        $this->assertFalse($DB->record_exists('classroomtoolbox', ['id' => $wheel->id]));
        $this->assertFalse(classroomtoolbox_delete_instance($wheel->id));
    }

    /**
     * Supported features.
     */
    public function test_supports(): void {
        $this->assertTrue(classroomtoolbox_supports(FEATURE_GROUPS));
        $this->assertTrue(classroomtoolbox_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertFalse(classroomtoolbox_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(classroomtoolbox_supports('something_unknown'));
    }

    /**
     * Guests must not get the view capability by default; students can view and appear, teachers cannot appear.
     */
    public function test_default_capabilities(): void {
        $guest = get_default_capabilities('guest');
        $this->assertArrayNotHasKey('mod/classroomtoolbox:view', $guest);

        $student = get_default_capabilities('student');
        $this->assertSame(CAP_ALLOW, $student['mod/classroomtoolbox:view']);
        $this->assertSame(CAP_ALLOW, $student['mod/classroomtoolbox:appear']);

        $teacher = get_default_capabilities('editingteacher');
        $this->assertSame(CAP_ALLOW, $teacher['mod/classroomtoolbox:addinstance']);
        $this->assertArrayNotHasKey('mod/classroomtoolbox:appear', $teacher);
    }
}
