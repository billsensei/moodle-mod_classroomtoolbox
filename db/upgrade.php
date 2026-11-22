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
 * Upgrade steps for mod_classroomtoolbox.
 *
 * @package    mod_classroomtoolbox
 * @copyright  2026 Bill <wrwjpn@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Run the upgrade steps.
 *
 * @param int $oldversion Version being upgraded from.
 * @return bool
 */
function xmldb_classroomtoolbox_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026112400) {
        // Colour scheme chosen per wheel.
        $table = new xmldb_table('classroomtoolbox');
        $field = new xmldb_field('colorscheme', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'classic', 'usenrolled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026112400, 'classroomtoolbox');
    }

    if ($oldversion < 2026120100) {
        // Timer presets, set per activity.
        $table = new xmldb_table('classroomtoolbox');
        $field = new xmldb_field('timerpresets', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '1,3,5,10', 'tools');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2026120100, 'classroomtoolbox');
    }

    if ($oldversion < 2026120800) {
        // Scoreboard entries.
        $table = new xmldb_table('classroomtoolbox_scores');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('toolboxid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('label', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('score', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('toolboxid', XMLDB_KEY_FOREIGN, ['toolboxid'], 'classroomtoolbox', ['id']);
        $table->add_index('toolboxid-sortorder', XMLDB_INDEX_NOTUNIQUE, ['toolboxid', 'sortorder']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, 2026120800, 'classroomtoolbox');
    }

    if ($oldversion < 2026122002) {
        // No schema change: this version only refreshes the capability risk flags, the web service definitions
        // (readonlysession, update_score parameters) and the privacy provider, which Moodle applies when the version
        // number changes.
        upgrade_mod_savepoint(true, 2026122002, 'classroomtoolbox');
    }

    return true;
}
