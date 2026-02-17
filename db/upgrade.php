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

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion The old version number.
 * @return bool True on success.
 */
function xmldb_local_integrationhub_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026021604) {

        // Define table local_integrationhub_rules to be created.
        $table = new xmldb_table('local_integrationhub_rules');

        // Adding fields to table local_integrationhub_rules.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('serviceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('endpoint', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('payload_template', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_integrationhub_rules.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_serviceid', XMLDB_KEY_FOREIGN, ['serviceid'], 'local_integrationhub_svc', ['id']);

        // Adding indexes to table local_integrationhub_rules.
        $table->add_index('ix_eventname', XMLDB_INDEX_NOTUNIQUE, ['eventname']);
        $table->add_index('ix_enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);

        // Conditionally launch create table for local_integrationhub_rules.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Integration Hub savepoint reached.
        upgrade_plugin_savepoint(true, 2026021604, 'local', 'integrationhub');
    }

    if ($oldversion < 2026021609) {
        // Define field type to be added to local_integrationhub_svc.
        $table = new xmldb_table('local_integrationhub_svc');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'rest', 'name');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Integration Hub savepoint reached.
        upgrade_plugin_savepoint(true, 2026021609, 'local', 'integrationhub');
    }

    if ($oldversion < 2026021701) {

        // Define table local_integrationhub_dlq to be created.
        $table = new xmldb_table('local_integrationhub_dlq');

        // Adding fields to table local_integrationhub_dlq.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('serviceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table local_integrationhub_dlq.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_serviceid_dlq', XMLDB_KEY_FOREIGN, ['serviceid'], 'local_integrationhub_svc', ['id']);

        // Adding indexes to table local_integrationhub_dlq.
        $table->add_index('ix_eventname_dlq', XMLDB_INDEX_NOTUNIQUE, ['eventname']);
        $table->add_index('ix_timecreated_dlq', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for local_integrationhub_dlq.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Integration Hub savepoint reached.
        upgrade_plugin_savepoint(true, 2026021701, 'local', 'integrationhub');
    }

    if ($oldversion < 2026021704) {
        // Add 'direction' field to local_integrationhub_log.
        $table = new xmldb_table('local_integrationhub_log');
        $field = new xmldb_field('direction', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'outbound', 'timecreated');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add 'response_queue' field to local_integrationhub_svc (for AMQP consumer).
        $table2 = new xmldb_table('local_integrationhub_svc');
        $field2 = new xmldb_field('response_queue', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'cb_cooldown');

        if (!$dbman->field_exists($table2, $field2)) {
            $dbman->add_field($table2, $field2);
        }

        // Integration Hub savepoint reached.
        upgrade_plugin_savepoint(true, 2026021704, 'local', 'integrationhub');
    }

    return true;
}
