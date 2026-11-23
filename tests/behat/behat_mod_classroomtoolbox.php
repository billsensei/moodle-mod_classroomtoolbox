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

    /**
     * Press a key while an element has the focus (the page scripts listen for the keydown event).
     *
     * @When /^I press the "(?P<key>Tab|Shift\+Tab|Escape)" key in "(?P<selector>(?:[^"]|\\")*)"$/
     * @param string $key Tab, Shift+Tab or Escape.
     * @param string $selector CSS selector of the element receiving the key.
     */
    public function i_press_the_key_in(string $key, string $selector): void {
        $shift = $key === 'Shift+Tab';
        $name = $shift ? 'Tab' : $key;
        $this->evaluate_script(
            '(function () { var el = document.querySelector(' . json_encode($this->escape($selector)) . ');' .
            ' el.dispatchEvent(new KeyboardEvent(\'keydown\', {key: ' . json_encode($name) . ', shiftKey: ' .
            ($shift ? 'true' : 'false') . ', bubbles: true, cancelable: true})); })()'
        );
    }

    /**
     * Check which element has the keyboard focus.
     *
     * @Then /^the focus should be on "(?P<selector>(?:[^"]|\\")*)"$/
     * @param string $selector CSS selector.
     */
    public function the_focus_should_be_on(string $selector): void {
        $this->wait_for_pending_js();
        $has = $this->evaluate_script(
            'document.activeElement === document.querySelector(' . json_encode($this->escape($selector)) . ')'
        );
        if (!$has) {
            throw new ExpectationException('The focus is not on "' . $selector . '"', $this->getSession());
        }
    }

    /**
     * Check whether an element is inert (not focusable and hidden from assistive technology).
     *
     * @Then /^"(?P<selector>(?:[^"]|\\")*)" should (?P<not>not )?be inert$/
     * @param string $selector CSS selector.
     * @param string $not "not " when it must not be inert.
     */
    public function should_be_inert(string $selector, string $not = ''): void {
        $inert = $this->evaluate_script(
            '!!document.querySelector(' . json_encode($this->escape($selector)) . ').closest(\'[inert]\')'
        );
        if ((bool) $inert === ($not !== '')) {
            throw new ExpectationException(
                '"' . $selector . '" is ' . ($inert ? '' : 'not ') . 'inert',
                $this->getSession()
            );
        }
    }

    /**
     * Replace the browser's audio with a counter, since a test browser cannot hear anything. Every note the page
     * tries to play is counted, whether or not sound is switched on in the page (the page decides that).
     *
     * @Given /^I install an audio spy$/
     */
    public function i_install_an_audio_spy(): void {
        $this->evaluate_script(
            '(function () { window.__sounds = 0; var Spy = function () { this.currentTime = 0; this.state = \'running\';' .
            ' this.destination = {}; };' .
            ' Spy.prototype.resume = function () {};' .
            ' Spy.prototype.createBiquadFilter = function () { return { type: \'\', frequency: { setValueAtTime: function () {},' .
            ' linearRampToValueAtTime: function () {} }, Q: {}, connect: function () {} }; };' .
            ' Spy.prototype.createWaveShaper = function () { return { curve: null, connect: function () {} }; };' .
            ' Spy.prototype.createBuffer = function () { return { getChannelData: function () { return new Float32Array(8); } }; };' .
            ' Spy.prototype.createBufferSource = function () { return { connect: function () {}, start: function () {} }; };' .
            ' Spy.prototype.createDynamicsCompressor = function () { return { threshold: {}, knee: {}, ratio: {}, attack: {},' .
            ' release: {}, connect: function () {} }; };' .
            ' Spy.prototype.createGain = function () { return { connect: function () {}, gain: { value: 1,' .
            ' setValueAtTime: function () {}, exponentialRampToValueAtTime: function () {} } }; };' .
            ' Spy.prototype.createOscillator = function () { window.__sounds++; return { frequency: { setValueAtTime: function () {},' .
            ' linearRampToValueAtTime: function () {} }, connect: function () {},' .
            ' start: function () {}, stop: function () {} }; };' .
            ' window.AudioContext = Spy; window.webkitAudioContext = Spy; })()'
        );
    }

    /**
     * Check how many notes the page played.
     *
     * @Then /^at least "(?P<count>\d+)" sounds should have been played$/
     * @param string $count Minimum number of notes.
     */
    public function at_least_sounds_should_have_been_played(string $count): void {
        $this->wait_for_pending_js();
        $heard = (int) $this->evaluate_script('window.__sounds || 0');
        if ($heard < (int) $count) {
            throw new ExpectationException(
                'Only ' . $heard . ' sounds were played, expected at least ' . $count,
                $this->getSession()
            );
        }
    }

    /**
     * Check that the page played nothing.
     *
     * @Then /^no sounds should have been played$/
     */
    public function no_sounds_should_have_been_played(): void {
        $this->wait_for_pending_js();
        $heard = (int) $this->evaluate_script('window.__sounds || 0');
        if ($heard !== 0) {
            throw new ExpectationException($heard . ' sounds were played, expected none', $this->getSession());
        }
    }

    /**
     * Check that the generated groups are coloured, each differently.
     *
     * @Then /^the generated groups should have different background colours$/
     */
    public function the_generated_groups_should_have_different_colours(): void {
        $this->wait_for_pending_js();
        $colours = json_decode($this->evaluate_script(
            'JSON.stringify([...document.querySelectorAll(\'.mod_classroomtoolbox-groupgen-card\')].map(function (card) {' .
            ' return getComputedStyle(card).backgroundColor; }))'
        ));
        $plain = ['rgba(0, 0, 0, 0)', 'transparent', 'rgb(255, 255, 255)'];
        if (count($colours) < 2 || count(array_unique($colours)) !== count($colours) || array_intersect($colours, $plain)) {
            throw new ExpectationException(
                'The groups are not each coloured differently: ' . implode(' | ', (array) $colours),
                $this->getSession()
            );
        }
    }
}
