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
 * Behat steps for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;

/**
 * Behat steps for the Classroom Toolbox scoreboard.
 *
 * @package    mod_classroomtoolbox
 * @category   test
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_classroomtoolbox extends behat_base {
    /**
     * Put entries on a toolbox's scoreboard.
     *
     * @Given /^the scoreboard of "(?P<name>(?:[^"]|\\")*)" has these entries:$/
     * @param string $name Activity name.
     * @param TableNode $table Columns label and score.
     */
    public function the_scoreboard_has_these_entries(string $name, TableNode $table): void {
        global $DB;

        $toolbox = $DB->get_record('classroomtoolbox', ['name' => $this->escape($name)], '*', MUST_EXIST);
        $generator = \testing_util::get_data_generator()->get_plugin_generator('mod_classroomtoolbox');
        foreach ($table->getHash() as $row) {
            $generator->create_score($toolbox->id, $row['label'], (int) $row['score']);
        }
    }

    /**
     * Check the order of the entries on the board, top first.
     *
     * @Then /^the scoreboard order should be "(?P<order>(?:[^"]|\\")*)"$/
     * @param string $order Comma-separated labels.
     */
    public function the_scoreboard_order_should_be(string $order): void {
        $this->wait_for_pending_js();
        $actual = json_decode($this->evaluate_script(
            'JSON.stringify([...document.querySelectorAll(\'[data-region="score-list"] li\')].map(function (li) {' .
            ' var input = li.querySelector(\'input[data-field="label"]\');' .
            ' return input ? input.value : li.querySelector(\'.mod_classroomtoolbox-score-label\').textContent; }))'
        ));
        $expected = array_map('trim', explode(',', $this->escape($order)));
        if ($actual !== $expected) {
            throw new ExpectationException(
                'The scoreboard order is "' . implode(', ', (array) $actual) . '", not "' . implode(', ', $expected) . '"',
                $this->getSession()
            );
        }
    }

    /**
     * Check the points of one entry.
     *
     * @Then /^the scoreboard score of "(?P<label>(?:[^"]|\\")*)" should be "(?P<score>-?\d+)"$/
     * @param string $label Entry label.
     * @param string $score Expected points.
     */
    public function the_scoreboard_score_should_be(string $label, string $score): void {
        $this->wait_for_pending_js();
        $actual = $this->evaluate_script(
            '(function () { var found = null;' .
            ' document.querySelectorAll(\'[data-region="score-list"] li\').forEach(function (li) {' .
            ' var input = li.querySelector(\'input[data-field="label"]\');' .
            ' var name = input ? input.value : li.querySelector(\'.mod_classroomtoolbox-score-label\').textContent;' .
            ' if (name === ' . json_encode($this->escape($label)) . ') {' .
            ' var points = li.querySelector(\'input[data-field="points"]\');' .
            ' found = points ? points.value : li.querySelector(\'.mod_classroomtoolbox-score-points\').textContent; } });' .
            ' return found; })()'
        );
        if ((string) $actual !== $score) {
            throw new ExpectationException(
                'The score of "' . $label . '" is "' . var_export($actual, true) . '", not "' . $score . '"',
                $this->getSession()
            );
        }
    }
}
