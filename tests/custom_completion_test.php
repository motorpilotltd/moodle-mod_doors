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

use mod_doors\completion\custom_completion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/doors/locallib.php');

/**
 * Tests for the mod_doors custom completion rule.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_doors
 * @covers     \mod_doors\completion\custom_completion
 */
final class custom_completion_test extends \advanced_testcase {
    /**
     * The rule completes once the required number of doors is opened.
     */
    public function test_get_state(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('enablecompletion', 1);
        $generator = self::getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');

        $doors = $generator->create_module('doors', [
            'course' => $course->id,
            'numdoors' => 4,
            'completionopened' => 2,
        ], ['completion' => COMPLETION_TRACKING_AUTOMATIC]);
        $instance = $DB->get_record('doors', ['id' => $doors->id]);
        $cm = get_fast_modinfo($course)->get_cm($doors->cmid);
        $coursestd = $DB->get_record('course', ['id' => $course->id]);
        $cmrecord = get_coursemodule_from_id('doors', $doors->cmid);
        $context = \context_module::instance($doors->cmid);

        self::setUser($student);
        $completion = new custom_completion($cm, (int)$student->id);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionopened'));

        $alldoors = array_values(doors_get_doors($instance));
        doors_mark_opened($alldoors[0], $instance, $cmrecord, $coursestd, $context);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionopened'));

        doors_mark_opened($alldoors[1], $instance, $cmrecord, $coursestd, $context);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionopened'));

        // The overall completion state agrees.
        $completioninfo = new \completion_info($coursestd);
        $data = $completioninfo->get_data($cm, false, $student->id);
        $this->assertEquals(COMPLETION_COMPLETE, $data->completionstate);
    }

    /**
     * The defined rules and their descriptions are declared.
     */
    public function test_rule_definitions(): void {
        $this->resetAfterTest();

        set_config('enablecompletion', 1);
        $generator = self::getDataGenerator();
        $course = $generator->create_course(['enablecompletion' => 1]);
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $doors = $generator->create_module('doors', [
            'course' => $course->id,
            'numdoors' => 4,
            'completionopened' => 3,
        ], ['completion' => COMPLETION_TRACKING_AUTOMATIC]);
        $cm = get_fast_modinfo($course)->get_cm($doors->cmid);

        $this->assertSame(['completionopened'], custom_completion::get_defined_custom_rules());

        $completion = new custom_completion($cm, (int)$student->id);
        $descriptions = $completion->get_custom_rule_descriptions();
        $this->assertArrayHasKey('completionopened', $descriptions);
        $this->assertStringContainsString('3', $descriptions['completionopened']);
        $this->assertSame(['completionview', 'completionopened'], $completion->get_sort_order());
    }
}
