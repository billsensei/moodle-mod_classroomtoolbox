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
 * Settings form for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once(__DIR__ . '/lib.php');

/**
 * Settings form.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_classroomtoolbox_mod_form extends moodleform_mod {
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

        $mform->addElement('header', 'toolshdr', get_string('tools', 'mod_classroomtoolbox'));
        foreach (\mod_classroomtoolbox\local\toolbox::all_ids() as $id) {
            $mform->addElement('advcheckbox', 'tool_' . $id, get_string('tool_' . $id, 'mod_classroomtoolbox'));
            $mform->setDefault('tool_' . $id, 1);
        }
        $mform->addElement('static', 'toolshelp', '', get_string('tools_help', 'mod_classroomtoolbox'));

        // Names are used by the name wheel and the group generator.
        $mform->addElement('header', 'nameshdr', get_string('namesettings', 'mod_classroomtoolbox'));
        $mform->addElement('advcheckbox', 'usenrolled', get_string('usenrolled', 'mod_classroomtoolbox'));
        $mform->addHelpButton('usenrolled', 'usenrolled', 'mod_classroomtoolbox');
        $mform->setDefault('usenrolled', 1);

        $mform->addElement('textarea', 'names', get_string('names', 'mod_classroomtoolbox'), ['rows' => 8, 'cols' => 40]);
        $mform->setType('names', PARAM_TEXT);
        $mform->addHelpButton('names', 'names', 'mod_classroomtoolbox');

        $mform->addElement('header', 'wheelhdr', get_string('wheelsettings', 'mod_classroomtoolbox'));
        $options = [];
        foreach (array_keys(classroomtoolbox_color_schemes()) as $id) {
            $options[$id] = get_string('scheme_' . $id, 'mod_classroomtoolbox');
        }
        $mform->addElement('select', 'colorscheme', get_string('colorscheme', 'mod_classroomtoolbox'), $options);
        $mform->addHelpButton('colorscheme', 'colorscheme', 'mod_classroomtoolbox');
        $mform->setDefault('colorscheme', 'classic');

        $mform->addElement('header', 'timerhdr', get_string('timersettings', 'mod_classroomtoolbox'));
        $mform->addElement('text', 'timerpresets', get_string('timerpresets', 'mod_classroomtoolbox'), ['size' => 30]);
        $mform->setType('timerpresets', PARAM_RAW_TRIMMED);
        $mform->setDefault('timerpresets', '1,3,5,10');
        $mform->addHelpButton('timerpresets', 'timerpresets', 'mod_classroomtoolbox');
        $mform->hideIf('timerpresets', 'tool_timer', 'notchecked');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Tick the tool checkboxes from the stored list.
     *
     * @param array $defaultvalues Values loaded from the database (modified in place).
     */
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        if (!empty($this->current->instance)) {
            $enabled = \mod_classroomtoolbox\local\toolbox::parse($defaultvalues['tools'] ?? null);
            foreach (\mod_classroomtoolbox\local\toolbox::all_ids() as $id) {
                $defaultvalues['tool_' . $id] = in_array($id, $enabled, true) ? 1 : 0;
            }
        }
    }

    /**
     * Keep the typed names list to a size the wheel can handle.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors keyed by field name.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $anytool = false;
        foreach (\mod_classroomtoolbox\local\toolbox::all_ids() as $id) {
            $anytool = $anytool || !empty($data['tool_' . $id]);
        }
        if (!$anytool) {
            $errors['toolshelp'] = get_string('toolrequired', 'mod_classroomtoolbox');
        }
        if (!empty($data['tool_timer'])) {
            $parts = array_filter(array_map('trim', explode(',', (string) ($data['timerpresets'] ?? ''))), 'strlen');
            $valid = $parts && count($parts) <= 8;
            foreach ($parts as $part) {
                $valid = $valid && is_numeric($part) && $part >= 0.1 && $part <= 180;
            }
            if (!$valid) {
                $errors['timerpresets'] = get_string('timerpresets_invalid', 'mod_classroomtoolbox');
            }
        }
        $names = $data['names'] ?? '';
        if (
            core_text::strlen($names) > CLASSROOMTOOLBOX_MAX_NAMES_CHARS
            || count(classroomtoolbox_parse_names($names)) > CLASSROOMTOOLBOX_MAX_NAMES
        ) {
            $errors['names'] = get_string('toomanynames', 'mod_classroomtoolbox');
        }
        return $errors;
    }
}
