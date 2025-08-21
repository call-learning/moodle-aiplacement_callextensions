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
 * Upgrade code.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later/
 **/
function xmldb_aiplacement_callextensions_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025081005) {

        // Define table aiplacement_callextensions to be created.
        $table = new xmldb_table('aiplacement_callextensions');

        // Adding fields to table aiplacement_callextensions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);

        // Adding keys to table aiplacement_callextensions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for aiplacement_callextensions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table aiplacement_callextensions_aiaction to be created.
        $table = new xmldb_table('aiplacement_callextensions_aiaction');

        // Adding fields to table aiplacement_callextensions_aiaction.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('actionname', XMLDB_TYPE_CHAR, '1024', null, null, null, null);
        $table->add_field('actiondata', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('statustext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table aiplacement_callextensions_aiaction.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('useractionidfk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('contextactionid', XMLDB_KEY_FOREIGN, ['contextid'], 'context', ['id']);

        // Conditionally launch create table for aiplacement_callextensions_aiaction.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // AI savepoint reached.
        upgrade_plugin_savepoint(true, 2025081005, 'aiplacement', 'callextensions');
    }
    if ($oldversion < 2025081007) {

        // Define field progress to be added to aiplacement_callextensions_aiaction.
        $table = new xmldb_table('aiplacement_callextensions_aiaction');
        $field = new xmldb_field('progress', XMLDB_TYPE_INTEGER, '2', null, null, null, '0', 'statustext');

        // Conditionally launch add field progress.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Callextensions savepoint reached.
        upgrade_plugin_savepoint(true, 2025081007, 'aiplacement', 'callextensions');
    }

    return true;
}