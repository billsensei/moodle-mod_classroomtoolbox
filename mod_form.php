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
 * Settings form for mod_namewheel.
 *
 * @package    mod_namewheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/lib.php');

/**
 * Settings form.
 *
 * @package    mod_namewheel
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_namewheel_mod_form extends moodleform_mod {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'wheelhdr', get_string('wheelsettings', 'mod_namewheel'));
        $mform->addElement('advcheckbox', 'usenrolled', get_string('usenrolled', 'mod_namewheel'));
        $mform->addHelpButton('usenrolled', 'usenrolled', 'mod_namewheel');
        $mform->setDefault('usenrolled', 1);

        $options = [];
        foreach (array_keys(namewheel_color_schemes()) as $id) {
            $options[$id] = get_string('scheme_' . $id, 'mod_namewheel');
        }
        $mform->addElement('select', 'colorscheme', get_string('colorscheme', 'mod_namewheel'), $options);
        $mform->addHelpButton('colorscheme', 'colorscheme', 'mod_namewheel');
        $mform->setDefault('colorscheme', 'classic');

        $mform->addElement('textarea', 'names', get_string('names', 'mod_namewheel'), ['rows' => 10, 'cols' => 40]);
        $mform->setType('names', PARAM_TEXT);
        $mform->addHelpButton('names', 'names', 'mod_namewheel');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
