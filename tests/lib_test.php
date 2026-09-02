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

namespace mod_doors;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/doors/lib.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');

/**
 * Tests for the mod_doors library callbacks.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_doors
 * @covers     \mod_doors
 */
final class lib_test extends \advanced_testcase {
    /**
     * Creating an instance creates exactly the requested door records.
     */
    public function test_add_instance_creates_doors(): void {
        global $DB;
        $this->resetAfterTest();

        $course = self::getDataGenerator()->create_course();
        $doors = self::getDataGenerator()->create_module('doors', ['course' => $course->id, 'numdoors' => 5]);

        $records = $DB->get_records('doors_door', ['doorsid' => $doors->id], 'doornumber ASC');
        $this->assertCount(5, $records);
        $this->assertSame(range(1, 5), array_values(array_map(static function ($door) {
            return (int)$door->doornumber;
        }, $records)));
    }

    /**
     * The door count is clamped to the allowed range.
     */
    public function test_add_instance_clamps_numdoors(): void {
        global $DB;
        $this->resetAfterTest();

        $course = self::getDataGenerator()->create_course();
        $doors = self::getDataGenerator()->create_module('doors', ['course' => $course->id, 'numdoors' => 99]);

        $this->assertEquals(DOORS_MAX_DOORS, $DB->get_field('doors', 'numdoors', ['id' => $doors->id]));
        $this->assertEquals(DOORS_MAX_DOORS, $DB->count_records('doors_door', ['doorsid' => $doors->id]));
    }

    /**
     * Lowering the door count keeps the stored doors; raising it brings them back.
     */
    public function test_update_instance_hides_rather_than_deletes(): void {
        global $DB;
        $this->resetAfterTest();

        $course = self::getDataGenerator()->create_course();
        $doors = self::getDataGenerator()->create_module('doors', ['course' => $course->id, 'numdoors' => 6]);
        $instance = $DB->get_record('doors', ['id' => $doors->id]);

        $DB->set_field('doors_door', 'title', 'Kept content', [
            'doorsid' => $doors->id, 'doornumber' => 6,
        ]);

        $update = clone $instance;
        $update->instance = $instance->id;
        $update->coursemodule = $doors->cmid;
        $update->numdoors = 3;
        doors_update_instance($update);

        // All six records survive; only three are active.
        $this->assertEquals(6, $DB->count_records('doors_door', ['doorsid' => $doors->id]));
        $instance = $DB->get_record('doors', ['id' => $doors->id]);
        $this->assertCount(3, doors_get_doors($instance));
        $this->assertCount(3, doors_get_hidden_doors($instance));

        $update->numdoors = 6;
        doors_update_instance($update);
        $instance = $DB->get_record('doors', ['id' => $doors->id]);
        $active = doors_get_doors($instance);
        $this->assertCount(6, $active);
        $titles = array_map(static function ($door) {
            return $door->title;
        }, $active);
        $this->assertContains('Kept content', $titles);
        $this->assertEquals(6, $DB->count_records('doors_door', ['doorsid' => $doors->id]));
    }

    /**
     * Deleting the instance removes the doors and the opening records.
     */
    public function test_delete_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $course = self::getDataGenerator()->create_course();
        $user = self::getDataGenerator()->create_user();
        $doors = self::getDataGenerator()->create_module('doors', ['course' => $course->id, 'numdoors' => 3]);
        $door = $DB->get_record('doors_door', ['doorsid' => $doors->id, 'doornumber' => 1]);

        $DB->insert_record('doors_opened', (object)[
            'doorsid' => $doors->id,
            'doorid' => $door->id,
            'userid' => $user->id,
            'timeopened' => time(),
        ]);

        $this->assertTrue(doors_delete_instance($doors->id));
        $this->assertEquals(0, $DB->count_records('doors', ['id' => $doors->id]));
        $this->assertEquals(0, $DB->count_records('doors_door', ['doorsid' => $doors->id]));
        $this->assertEquals(0, $DB->count_records('doors_opened', ['doorsid' => $doors->id]));
        $this->assertFalse(doors_delete_instance($doors->id));
    }

    /**
     * Deleting one stored door removes its opening records too.
     */
    public function test_delete_door(): void {
        global $DB;
        $this->resetAfterTest();

        $course = self::getDataGenerator()->create_course();
        $user = self::getDataGenerator()->create_user();
        $doors = self::getDataGenerator()->create_module('doors', ['course' => $course->id, 'numdoors' => 3]);
        $context = \context_module::instance($doors->cmid);
        $door = $DB->get_record('doors_door', ['doorsid' => $doors->id, 'doornumber' => 2]);

        $DB->insert_record('doors_opened', (object)[
            'doorsid' => $doors->id,
            'doorid' => $door->id,
            'userid' => $user->id,
            'timeopened' => time(),
        ]);

        doors_delete_door($door, $context);
        $this->assertEquals(0, $DB->count_records('doors_door', ['id' => $door->id]));
        $this->assertEquals(0, $DB->count_records('doors_opened', ['doorid' => $door->id]));
        $this->assertEquals(2, $DB->count_records('doors_door', ['doorsid' => $doors->id]));
    }

    /**
     * Course reset clears openings for that course only.
     */
    public function test_reset_userdata(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = self::getDataGenerator();
        $coursea = $generator->create_course();
        $courseb = $generator->create_course();
        $user = $generator->create_user();
        $doorsa = $generator->create_module('doors', ['course' => $coursea->id, 'numdoors' => 2]);
        $doorsb = $generator->create_module('doors', ['course' => $courseb->id, 'numdoors' => 2]);

        foreach ([$doorsa, $doorsb] as $instance) {
            $door = $DB->get_record('doors_door', ['doorsid' => $instance->id, 'doornumber' => 1]);
            $DB->insert_record('doors_opened', (object)[
                'doorsid' => $instance->id,
                'doorid' => $door->id,
                'userid' => $user->id,
                'timeopened' => time(),
            ]);
        }

        $status = doors_reset_userdata((object)[
            'courseid' => $coursea->id,
            'reset_doors_opened' => 1,
        ]);

        $this->assertCount(1, $status);
        $this->assertFalse($status[0]['error']);
        $this->assertEquals(0, $DB->count_records('doors_opened', ['doorsid' => $doorsa->id]));
        $this->assertEquals(1, $DB->count_records('doors_opened', ['doorsid' => $doorsb->id]));
    }

    /**
     * The course module info carries the custom completion rule.
     */
    public function test_get_coursemodule_info_completion(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('enablecompletion', 1);
        $course = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        $doors = self::getDataGenerator()->create_module('doors', [
            'course' => $course->id,
            'numdoors' => 4,
            'completionopened' => 2,
        ], ['completion' => COMPLETION_TRACKING_AUTOMATIC]);

        $cm = $DB->get_record('course_modules', ['id' => $doors->cmid]);
        $cm->showdescription = 0;
        $info = doors_get_coursemodule_info($cm);

        $this->assertEquals(2, $info->customdata['customcompletionrules']['completionopened']);
    }
}
