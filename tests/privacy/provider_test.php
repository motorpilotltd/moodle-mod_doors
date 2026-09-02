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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/doors/locallib.php');

/**
 * Tests for the mod_doors privacy provider.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_doors
 * @covers     \mod_doors\privacy\provider
 */
final class provider_test extends provider_testcase {
    /**
     * Create two doors instances with openings for two users.
     *
     * @return array [instance a, instance b, user1, user2, context a, context b]
     */
    protected function create_environment(): array {
        global $DB;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');

        $doorsa = $generator->create_module('doors', ['course' => $course->id, 'numdoors' => 3]);
        $doorsb = $generator->create_module('doors', ['course' => $course->id, 'numdoors' => 3]);

        foreach ([[$doorsa, $user1], [$doorsa, $user2], [$doorsb, $user1]] as [$instance, $user]) {
            $door = $DB->get_record('doors_door', ['doorsid' => $instance->id, 'doornumber' => 1]);
            $DB->insert_record('doors_opened', (object)[
                'doorsid' => $instance->id,
                'doorid' => $door->id,
                'userid' => $user->id,
                'timeopened' => time(),
            ]);
        }

        return [
            $doorsa,
            $doorsb,
            $user1,
            $user2,
            \context_module::instance($doorsa->cmid),
            \context_module::instance($doorsb->cmid),
        ];
    }

    /**
     * The metadata declares the openings table.
     */
    public function test_get_metadata(): void {
        $collection = new \core_privacy\local\metadata\collection('mod_doors');
        $collection = provider::get_metadata($collection);
        $this->assertCount(1, $collection->get_collection());
    }

    /**
     * Contexts are found for users with openings only.
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        [, , $user1, $user2, $contexta, $contextb] = $this->create_environment();

        $contextids = provider::get_contexts_for_userid($user1->id)->get_contextids();
        $this->assertEqualsCanonicalizing([$contexta->id, $contextb->id], $contextids);

        $contextids = provider::get_contexts_for_userid($user2->id)->get_contextids();
        $this->assertEquals([$contexta->id], $contextids);
    }

    /**
     * Users in a context are found.
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();
        [, , $user1, $user2, $contexta, $contextb] = $this->create_environment();

        $userlist = new userlist($contexta, 'mod_doors');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$user1->id, $user2->id], $userlist->get_userids());

        $userlist = new userlist($contextb, 'mod_doors');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$user1->id], $userlist->get_userids());
    }

    /**
     * Export produces the user's openings for the requested context.
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        [, , $user1, , $contexta] = $this->create_environment();

        $contextlist = new approved_contextlist($user1, 'mod_doors', [$contexta->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($contexta);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([get_string('privacy:openeddoors', 'mod_doors')]);
        $this->assertCount(1, $data->doors);
        $this->assertEquals(1, $data->doors[0]['doornumber']);
    }

    /**
     * Deleting for a whole context clears every user's openings there.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        [$doorsa, $doorsb, , , $contexta] = $this->create_environment();

        provider::delete_data_for_all_users_in_context($contexta);
        $this->assertEquals(0, $DB->count_records('doors_opened', ['doorsid' => $doorsa->id]));
        $this->assertEquals(1, $DB->count_records('doors_opened', ['doorsid' => $doorsb->id]));
    }

    /**
     * Deleting for one user leaves the other user's data alone.
     */
    public function test_delete_data_for_user(): void {
        global $DB;
        $this->resetAfterTest();
        [$doorsa, $doorsb, $user1, $user2, $contexta] = $this->create_environment();

        $contextlist = new approved_contextlist($user1, 'mod_doors', [$contexta->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertEquals(0, $DB->count_records('doors_opened', [
            'doorsid' => $doorsa->id, 'userid' => $user1->id,
        ]));
        $this->assertEquals(1, $DB->count_records('doors_opened', [
            'doorsid' => $doorsa->id, 'userid' => $user2->id,
        ]));
        // The other instance was not in the approved list.
        $this->assertEquals(1, $DB->count_records('doors_opened', ['doorsid' => $doorsb->id]));
    }

    /**
     * Deleting for a list of users in a context removes just those users.
     */
    public function test_delete_data_for_users(): void {
        global $DB;
        $this->resetAfterTest();
        [$doorsa, , $user1, $user2, $contexta] = $this->create_environment();

        $userlist = new approved_userlist($contexta, 'mod_doors', [$user1->id]);
        provider::delete_data_for_users($userlist);

        $this->assertEquals(0, $DB->count_records('doors_opened', [
            'doorsid' => $doorsa->id, 'userid' => $user1->id,
        ]));
        $this->assertEquals(1, $DB->count_records('doors_opened', [
            'doorsid' => $doorsa->id, 'userid' => $user2->id,
        ]));
    }
}
