<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

function xmldb_local_categoryfields_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025092400) {
        $table = new xmldb_table('local_categoryfields_data');
        $field = new xmldb_field('image', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, false, '0', 'summary');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $fieldtodrop = new xmldb_field('imageurl');

        if ($dbman->field_exists($table, $fieldtodrop)) {
            $dbman->drop_field($table, $fieldtodrop);
        }

        upgrade_plugin_savepoint(true, 2025092400, 'local', 'categoryfields');
    }

    if ($oldversion < 2025092401) {
        $table = new xmldb_table('local_categoryfields_data');
        $field = new xmldb_field('summary');

        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025092401, 'local', 'categoryfields');
    }

    if ($oldversion < 2025092402) {
        $table = new xmldb_table('local_categoryfields_data');
        $field = new xmldb_field('related_categories', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, false, '', 'image');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025092402, 'local', 'categoryfields');
    }

    if ($oldversion < 2025093006) {
        upgrade_plugin_savepoint(true, 2025093006, 'local', 'categoryfields');
    }

    return true;
}
