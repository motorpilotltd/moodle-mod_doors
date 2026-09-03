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
 * Upgrade steps for mod_doors.
 *
 * The pre-release development steps were folded into install.xml for the
 * 1.0.0-alpha.1 baseline, so history starts at the first post-baseline
 * change.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Run the upgrade steps.
 *
 * @param int $oldversion The currently installed version.
 * @return bool
 */
function xmldb_doors_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026090300) {
        // The advent-calendar appearance round: transparent doors, door gap,
        // background fit/strength, face layout and last-row centring.
        $table = new xmldb_table('doors');

        $fields = [
            new xmldb_field('transparentdoors', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'doorshape'),
            new xmldb_field('doorgap', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '10', 'transparentdoors'),
            new xmldb_field('backgroundfit', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'cover', 'doorgap'),
            new xmldb_field('bgopacity', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '100', 'backgroundfit'),
            new xmldb_field('facelayout', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'overlay', 'bgopacity'),
            new xmldb_field('centredoors', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'facelayout'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026090300, 'doors');
    }

    return true;
}
