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

namespace mod_classroomtoolbox\local\tools;

use mod_classroomtoolbox\local\participants;
use mod_classroomtoolbox\local\tool_interface;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/classroomtoolbox/lib.php');

/**
 * The name wheel tool.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wheel implements tool_interface {
    #[\Override]
    public static function get_id(): string {
        return 'wheel';
    }

    #[\Override]
    public function render(\cm_info $cm, \stdClass $course, \stdClass $record, \context_module $context): string {
        global $OUTPUT, $PAGE;

        [$entries, $groups] = participants::get($cm, $record, $context);

        // The partner wheel is this same wheel, run in a mode that picks two people in a row.
        $tool = static::get_id();
        $partners = $tool === 'partners';

        $html = $OUTPUT->render_from_template('mod_classroomtoolbox/wheel', [
            'uniqid' => uniqid(),
            'region' => $tool,
            'partners' => $partners,
            'hasnames' => !empty($entries),
            'hasgroups' => !empty($groups),
        ]);

        $scheme = classroomtoolbox_color_schemes()[classroomtoolbox_clean_color_scheme($record->colorscheme ?? null)];
        $strings = [
            'winner' => get_string('winner', 'mod_classroomtoolbox'),
            'empty' => get_string('emptywheel', 'mod_classroomtoolbox'),
            'spinning' => get_string('spinning', 'mod_classroomtoolbox'),
            'clickhere' => get_string('clickhere', 'mod_classroomtoolbox'),
            'entriescount' => get_string('entriescount', 'mod_classroomtoolbox'),
            'removed' => get_string('removedannounce', 'mod_classroomtoolbox'),
            'groupchanged' => get_string('groupchanged', 'mod_classroomtoolbox'),
        ];
        if ($partners) {
            foreach (['first', 'pair', 'spinpartner', 'another', 'next', 'hint', 'notenough', 'made'] as $key) {
                $strings['partner_' . $key] = get_string('partner_' . $key, 'mod_classroomtoolbox');
            }
        }
        $PAGE->requires->js_call_amd('mod_classroomtoolbox/wheel', 'init', [[
            'tool' => $tool,
            'partners' => $partners,
            'names' => $entries,
            'groups' => $groups,
            'scheme' => $scheme,
            'strings' => $strings,
        ]]);
        return $html;
    }
}
