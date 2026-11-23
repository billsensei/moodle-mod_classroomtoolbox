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

namespace mod_classroomtoolbox\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as request_provider;
use core_privacy\local\request\userlist;
use mod_classroomtoolbox\local\scores;

/**
 * Privacy provider for the Classroom Toolbox.
 *
 * The plugin keeps no data linked to a user account: there is no user id in any of its tables. It does store free
 * text that teachers type or copy in, which can be the name of a real person: the optional list of extra names, and
 * the labels on the scoreboard (the "Add participants" button copies enrolled users' names there as plain text).
 * These are declared here so they show in the privacy registry. Because nothing ties them to an account, a request
 * or an erasure for one user cannot find them; deleting a scoreboard entry or the whole activity removes them, and
 * deleting all data in an activity context clears that activity's scoreboard and its list of extra names.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, request_provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('classroomtoolbox', [
            'names' => 'privacy:metadata:classroomtoolbox:names',
        ], 'privacy:metadata:classroomtoolbox');
        $collection->add_database_table('classroomtoolbox_scores', [
            'label' => 'privacy:metadata:classroomtoolbox_scores:label',
            'score' => 'privacy:metadata:classroomtoolbox_scores:score',
        ], 'privacy:metadata:classroomtoolbox_scores');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        // Nothing is linked to a user account.
        return new contextlist();
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        // Nothing is linked to a user account.
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist) {
        // Nothing is linked to a user account.
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('classroomtoolbox', $context->instanceid);
        if ($cm) {
            scores::delete_all($cm->instance);
            // The extra names can be real people's names too, so they go with the scoreboard.
            $DB->set_field('classroomtoolbox', 'names', '', ['id' => $cm->instance]);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        // Nothing is linked to a user account.
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist) {
        // Nothing is linked to a user account.
    }
}
