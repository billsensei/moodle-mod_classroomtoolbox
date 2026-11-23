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
 * Tests for the tool registry and the tab shell template.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_classroomtoolbox\local;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(toolbox::class)]
/**
 * Tests for the tool registry.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \mod_classroomtoolbox\local\toolbox
 */
final class toolbox_test extends \advanced_testcase {
    /**
     * Every tool has an id, a tab label string and a class that implements the interface.
     */
    public function test_all_tools_are_well_formed(): void {
        $ids = toolbox::all_ids();
        $this->assertSame(['wheel', 'groups', 'stopwatch', 'timer', 'counter', 'scoreboard'], $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        foreach (toolbox::enabled((object) ['tools' => implode(',', $ids)]) as $id => $tool) {
            $this->assertInstanceOf(tool_interface::class, $tool);
            $this->assertSame($id, $tool::get_id());
            $this->assertTrue(get_string_manager()->string_exists('tool_' . $id, 'mod_classroomtoolbox'), $id);
        }
    }

    /**
     * Stored lists are cleaned: unknown ids dropped, tab order kept, never empty.
     */
    public function test_parse_and_serialise(): void {
        $this->assertSame(['wheel'], toolbox::parse(null));
        $this->assertSame(['wheel'], toolbox::parse(''));
        $this->assertSame(['wheel'], toolbox::parse('bogus'));
        $this->assertSame(['wheel'], toolbox::parse(' bogus , wheel '));
        $this->assertSame(['groups', 'counter'], toolbox::parse('counter,groups'));
        $this->assertSame('wheel', toolbox::serialise(['bogus', 'wheel']));
        $this->assertSame('wheel', toolbox::serialise([]));
    }

    /**
     * Enabled tools are keyed by id.
     */
    public function test_enabled(): void {
        $tools = toolbox::enabled((object) ['tools' => 'wheel']);
        $this->assertSame(['wheel'], array_keys($tools));
        $this->assertSame(['wheel'], array_keys(toolbox::enabled(new \stdClass())));
    }

    /**
     * The shell shows a tab bar only with several tools, and only the first panel is visible.
     */
    public function test_shell_template(): void {
        global $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/');
        $renderer = $PAGE->get_renderer('core');

        $tabs = [
            ['id' => 'wheel', 'label' => 'Name wheel', 'html' => '<p>WHEELBODY</p>', 'active' => true],
            ['id' => 'other', 'label' => 'Other tool', 'html' => '<p>OTHERBODY</p>', 'active' => false],
        ];
        $html = $renderer->render_from_template('mod_classroomtoolbox/toolbox', [
            'uniqid' => 'x1', 'tabs' => $tabs, 'hastabs' => true,
        ]);
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertSame(2, substr_count($html, 'role="tab"'));
        $this->assertSame(2, substr_count($html, 'data-region="tool-panel"'));
        $this->assertSame(1, preg_match_all('/<div id="toolbox-panel-[^>]*\shidden>/s', $html));
        $this->assertStringContainsString('WHEELBODY', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);

        $single = $renderer->render_from_template('mod_classroomtoolbox/toolbox', [
            'uniqid' => 'x2', 'tabs' => [$tabs[0]], 'hastabs' => false,
        ]);
        $this->assertStringNotContainsString('tablist', $single);
        $this->assertStringNotContainsString('role="tabpanel"', $single);
        $this->assertDoesNotMatchRegularExpression('/<div id="toolbox-panel-[^>]*\shidden/s', $single);
        $this->assertStringContainsString('WHEELBODY', $single);
        $this->assertStringContainsString('data-action="fullscreen"', $single);
    }
}
