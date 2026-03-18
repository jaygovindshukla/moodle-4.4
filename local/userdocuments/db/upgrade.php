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
 * Upgrade steps for local_userdocuments plugin.
 *
 * @package    local_userdocuments
 * @copyright  2026 Your Organisation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_userdocuments plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool True on success.
 */
function xmldb_local_userdocuments_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026031201) {
        $table = new xmldb_table('local_userdocuments_guardian');
        $field = new xmldb_field('guardian_relationship', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'guardian_contact');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026031201, 'local', 'userdocuments');
    }

    return true;
}
