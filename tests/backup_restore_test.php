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
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Backup and restore round-trip tests for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_doors
 * @covers     \backup_doors_activity_structure_step
 * @covers     \restore_doors_activity_structure_step
 */
final class backup_restore_test extends \advanced_testcase {
    /**
     * Duplicating within a course copies the doors and keeps a same-site
     * activity link that was not part of the backup.
     */
    public function test_duplicate_copies_doors_and_keeps_samesite_link(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $page = $generator->create_module('page', ['course' => $course->id]);
        $instance = $generator->create_module('doors', [
            'course' => $course->id,
            'numdoors' => 3,
            'openmode' => 'sequential',
            'transparentdoors' => 1,
            'doorgap' => 0,
            'backgroundfit' => 'fit',
        ]);

        $door = $DB->get_record('doors_door', ['doorsid' => $instance->id, 'doornumber' => 2]);
        $DB->update_record('doors_door', (object)[
            'id' => $door->id,
            'title' => 'Second door',
            'content' => '<p>Behind door two</p>',
            'cmid' => $page->cmid,
        ]);

        $cm = get_fast_modinfo($course)->get_cm($instance->cmid);
        if (
            class_exists('\core_courseformat\formatactions')
                && method_exists(\core_courseformat\formatactions::cm($course->id), 'duplicate')
        ) {
            // Moodle 5.2 deprecated duplicate_module() in favour of this.
            $newcm = \core_courseformat\formatactions::cm($course->id)->duplicate($cm->id);
        } else {
            $newcm = duplicate_module($course, $cm);
        }

        $this->assertSame('sequential', $DB->get_field('doors', 'openmode', ['id' => $newcm->instance]));

        // The appearance round's fields come across with the duplicate.
        $copy = $DB->get_record('doors', ['id' => $newcm->instance]);
        $this->assertEquals(1, $copy->transparentdoors);
        $this->assertEquals(0, $copy->doorgap);
        $this->assertSame('fit', $copy->backgroundfit);

        $newdoors = array_values($DB->get_records('doors_door', ['doorsid' => $newcm->instance], 'doornumber ASC'));
        $this->assertCount(3, $newdoors);
        $this->assertSame('Second door', $newdoors[1]->title);
        $this->assertSame('<p>Behind door two</p>', $newdoors[1]->content);

        // The page was not in the backup, but this is the same site and the
        // course module still exists, so the link survives.
        $this->assertEquals($page->cmid, $newdoors[1]->cmid);

        // Openings were not requested, and none exist anyway.
        $this->assertEquals(0, $DB->count_records('doors_opened', ['doorsid' => $newcm->instance]));
    }

    /**
     * A course restore remaps an activity link to the restored copy of its
     * target and carries user openings across.
     */
    public function test_course_restore_remaps_link_and_openings(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Keep the unpacked backup directory, as the restore controller
        // consumes it directly rather than the packaged .mbz file.
        $CFG->keeptempdirectoriesonbackup = true;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'student');
        $page = $generator->create_module('page', ['course' => $course->id, 'name' => 'Target page']);
        $instance = $generator->create_module('doors', ['course' => $course->id, 'numdoors' => 2]);

        $door = $DB->get_record('doors_door', ['doorsid' => $instance->id, 'doornumber' => 1]);
        $DB->set_field('doors_door', 'cmid', $page->cmid, ['id' => $door->id]);
        $DB->insert_record('doors_opened', (object)[
            'doorsid' => $instance->id,
            'doorid' => $door->id,
            'userid' => $user->id,
            'timeopened' => time(),
        ]);

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course('Restored', 'RST1', $course->category);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $newinstance = $DB->get_record('doors', ['course' => $newcourseid], '*', MUST_EXIST);
        $newdoors = array_values($DB->get_records('doors_door', ['doorsid' => $newinstance->id], 'doornumber ASC'));
        $this->assertCount(2, $newdoors);

        // The page was in the backup, so the link points at the new copy.
        $newpagecm = $DB->get_record('page', ['course' => $newcourseid], '*', MUST_EXIST);
        $newpagecmid = get_coursemodule_from_instance('page', $newpagecm->id, $newcourseid)->id;
        $this->assertEquals($newpagecmid, $newdoors[0]->cmid);
        $this->assertNotEquals($page->cmid, $newdoors[0]->cmid);

        // The opening came across and points at the new door.
        $opening = $DB->get_record('doors_opened', [
            'doorsid' => $newinstance->id, 'userid' => $user->id,
        ], '*', MUST_EXIST);
        $this->assertEquals($newdoors[0]->id, $opening->doorid);
    }
}
