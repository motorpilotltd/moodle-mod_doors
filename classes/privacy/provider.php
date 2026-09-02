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

namespace mod_doors\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the data stored by this plugin.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('doors_opened', [
            'doorsid' => 'privacy:metadata:doors_opened:doorsid',
            'doorid' => 'privacy:metadata:doors_opened:doorid',
            'userid' => 'privacy:metadata:doors_opened:userid',
            'timeopened' => 'privacy:metadata:doors_opened:timeopened',
        ], 'privacy:metadata:doors_opened');

        return $collection;
    }

    /**
     * Get the contexts containing data for a user.
     *
     * @param int $userid The user id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {doors_opened} o
                  JOIN {doors} d ON d.id = o.doorsid
                  JOIN {course_modules} cm ON cm.instance = d.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE o.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'modname' => 'doors',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Get the users within a context.
     *
     * @param userlist $userlist The userlist to add to.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT o.userid
                  FROM {doors_opened} o
                  JOIN {doors} d ON d.id = o.doorsid
                  JOIN {course_modules} cm ON cm.instance = d.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['modname' => 'doors', 'cmid' => $context->instanceid]);
    }

    /**
     * Export the user's data.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('doors', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $sql = "SELECT o.timeopened, dd.doornumber, dd.title
                      FROM {doors_opened} o
                      JOIN {doors_door} dd ON dd.id = o.doorid
                     WHERE o.doorsid = :doorsid AND o.userid = :userid
                  ORDER BY dd.doornumber ASC";

            $records = $DB->get_records_sql($sql, ['doorsid' => $cm->instance, 'userid' => $user->id]);

            if (!$records) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    'doornumber' => $record->doornumber,
                    'title' => $record->title,
                    'timeopened' => \core_privacy\local\request\transform::datetime($record->timeopened),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('privacy:openeddoors', 'mod_doors')],
                (object)['doors' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in a context.
     *
     * @param \context $context The context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('doors', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('doors_opened', ['doorsid' => $cm->instance]);
    }

    /**
     * Delete all data for one user.
     *
     * @param approved_contextlist $contextlist The approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('doors', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('doors_opened', ['doorsid' => $cm->instance, 'userid' => $userid]);
        }
    }

    /**
     * Delete data for multiple users in one context.
     *
     * @param approved_userlist $userlist The approved users.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('doors', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['doorsid'] = $cm->instance;

        $DB->delete_records_select('doors_opened', "doorsid = :doorsid AND userid $insql", $params);
    }
}
