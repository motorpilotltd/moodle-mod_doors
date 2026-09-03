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
require_once($CFG->dirroot . '/mod/doors/locallib.php');

/**
 * Tests for the mod_doors internal helpers.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group      mod_doors
 * @covers     \mod_doors
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Create a course, a doors instance and an enrolled student.
     *
     * @param array $fields Extra instance fields.
     * @return array [course, instance record, cm, context, student]
     */
    protected function create_environment(array $fields = []): array {
        global $DB;

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');

        $doors = $generator->create_module('doors', $fields + ['course' => $course->id, 'numdoors' => 4]);
        $instance = $DB->get_record('doors', ['id' => $doors->id]);
        $cm = get_coursemodule_from_instance('doors', $doors->id);
        $context = \context_module::instance($cm->id);

        return [$course, $instance, $cm, $context, $student];
    }

    /**
     * Doors come back in number order, and the shuffle is stable.
     */
    public function test_get_doors_order_and_stable_shuffle(): void {
        global $DB;
        $this->resetAfterTest();

        [, $instance] = $this->create_environment();

        $plain = array_map(static function ($door) {
            return (int)$door->doornumber;
        }, array_values(doors_get_doors($instance)));
        $this->assertSame([1, 2, 3, 4], $plain);

        $instance->randomise = 1;
        $shuffled = array_map(static function ($door) {
            return (int)$door->doornumber;
        }, array_values(doors_get_doors($instance)));
        $this->assertEqualsCanonicalizing([1, 2, 3, 4], $shuffled);
        // The same calendar shuffles the same way every time.
        $again = array_map(static function ($door) {
            return (int)$door->doornumber;
        }, array_values(doors_get_doors($instance)));
        $this->assertSame($shuffled, $again);
    }

    /**
     * A future release date locks a door for students but not for teachers.
     */
    public function test_door_available_release_date(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $instance, $cm, $context, $student] = $this->create_environment();
        $teacher = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $alldoors = doors_get_doors($instance);
        $door = reset($alldoors);
        $DB->set_field('doors_door', 'availablefrom', time() + DAYSECS, ['id' => $door->id]);
        $door->availablefrom = time() + DAYSECS;

        self::setUser($student);
        [$available, $reason] = doors_door_available($door, $instance, [], $alldoors, $context);
        $this->assertFalse($available);
        $this->assertNotSame('', $reason);

        self::setUser($teacher);
        [$available] = doors_door_available($door, $instance, [], $alldoors, $context);
        $this->assertTrue($available);
    }

    /**
     * Sequential mode locks a door until its predecessor is opened.
     */
    public function test_door_available_sequential(): void {
        $this->resetAfterTest();

        [, $instance, , $context, $student] = $this->create_environment(['openmode' => 'sequential']);
        self::setUser($student);

        $alldoors = doors_get_doors($instance);
        $doorlist = array_values($alldoors);
        [$door1, $door2] = $doorlist;

        [$available] = doors_door_available($door2, $instance, [], $alldoors, $context);
        $this->assertFalse($available);

        [$available] = doors_door_available($door1, $instance, [], $alldoors, $context);
        $this->assertTrue($available);

        $opened = [$door1->id => time()];
        [$available] = doors_door_available($door2, $instance, $opened, $alldoors, $context);
        $this->assertTrue($available);
    }

    /**
     * Opening a door records it once, counts it and fires the event.
     */
    public function test_mark_opened(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $instance, $cm, $context, $student] = $this->create_environment();
        self::setUser($student);

        $alldoors = doors_get_doors($instance);
        $door = reset($alldoors);

        $sink = $this->redirectEvents();
        doors_mark_opened($door, $instance, $cm, $course, $context);
        doors_mark_opened($door, $instance, $cm, $course, $context);
        $events = $sink->get_events();
        $sink->close();

        $this->assertEquals(1, $DB->count_records('doors_opened', [
            'doorid' => $door->id, 'userid' => $student->id,
        ]));
        $this->assertEquals(1, doors_count_opened($instance, $student->id));

        $opened = array_filter($events, static function ($event) {
            return $event instanceof \mod_doors\event\door_opened;
        });
        $this->assertCount(2, $opened);
        $event = reset($opened);
        $this->assertEquals($door->id, $event->objectid);
        $this->assertEquals(1, $event->other['doornumber']);
    }

    /**
     * Guests never get an opening record.
     */
    public function test_mark_opened_ignores_guest(): void {
        global $DB;
        $this->resetAfterTest();

        [$course, $instance, $cm, $context] = $this->create_environment();
        self::setGuestUser();

        $alldoors = doors_get_doors($instance);
        $door = reset($alldoors);
        doors_mark_opened($door, $instance, $cm, $course, $context);

        $this->assertEquals(0, $DB->count_records('doors_opened', ['doorsid' => $instance->id]));
    }

    /**
     * Openings above the current door count are not counted or listed.
     */
    public function test_opened_respects_door_count(): void {
        global $DB;
        $this->resetAfterTest();

        [, $instance, , , $student] = $this->create_environment();

        foreach ([1, 4] as $number) {
            $door = $DB->get_record('doors_door', ['doorsid' => $instance->id, 'doornumber' => $number]);
            $DB->insert_record('doors_opened', (object)[
                'doorsid' => $instance->id,
                'doorid' => $door->id,
                'userid' => $student->id,
                'timeopened' => time(),
            ]);
        }

        $this->assertEquals(2, doors_count_opened($instance, $student->id));

        $instance->numdoors = 2;
        $this->assertEquals(1, doors_count_opened($instance, $student->id));
        $this->assertCount(1, doors_get_opened($instance, $student->id));
    }

    /**
     * Colour cleaning accepts hex and simple names and rejects everything else.
     */
    public function test_clean_colour(): void {
        $this->assertSame('#a1B2c3', doors_clean_colour('#a1B2c3'));
        $this->assertSame('#fff', doors_clean_colour(' #fff '));
        $this->assertSame('rebeccapurple', doors_clean_colour('rebeccapurple'));
        $this->assertSame('', doors_clean_colour('#12345'));
        $this->assertSame('', doors_clean_colour('red;background:url(x)'));
        $this->assertSame('', doors_clean_colour('url(javascript:alert(1))'));
        $this->assertSame('', doors_clean_colour(null));
    }

    /**
     * Palette parsing splits on commas, spaces and newlines and drops rubbish.
     */
    public function test_parse_palette(): void {
        $this->assertSame(
            ['#111111', '#222222', 'gold'],
            doors_parse_palette("#111111, #222222\n gold junk!")
        );
        $this->assertSame([], doors_parse_palette(''));
    }

    /**
     * Contrast picks dark text on light colours and vice versa, hex only.
     */
    public function test_contrast_colour(): void {
        $this->assertSame('#1d2125', doors_contrast_colour('#ffffff'));
        $this->assertSame('#ffffff', doors_contrast_colour('#000000'));
        $this->assertSame('#ffffff', doors_contrast_colour('#1f5c8b'));
        $this->assertSame('', doors_contrast_colour('tomato'));
    }

    /**
     * The colour map covers every door and no two neighbours match.
     */
    public function test_build_colour_map(): void {
        $this->resetAfterTest();

        [, $instance] = $this->create_environment(['colourmode' => 'varied', 'palette' => '#111111 #222222 #333333']);
        $doorrecords = doors_get_doors($instance);

        $map = doors_build_colour_map($instance, $doorrecords);
        $this->assertCount(4, $map);

        $previous = null;
        foreach ($doorrecords as $door) {
            $this->assertContains($map[$door->id], ['#111111', '#222222', '#333333']);
            $this->assertNotSame($previous, $map[$door->id]);
            $previous = $map[$door->id];
        }

        // Fixed mode gets no map at all.
        $instance->colourmode = 'fixed';
        $this->assertSame([], doors_build_colour_map($instance, $doorrecords));

        // Transparent doors paint nothing behind the text, so varied colours
        // are skipped too.
        $instance->colourmode = 'varied';
        $instance->transparentdoors = 1;
        $this->assertSame([], doors_build_colour_map($instance, $doorrecords));
    }

    /**
     * The effective colour prefers the activity, then the site default.
     */
    public function test_effective_colour(): void {
        $this->resetAfterTest();

        [, $instance] = $this->create_environment();

        set_config('doorcolour', '#abcdef', 'mod_doors');
        $instance->doorcolour = '';
        $this->assertSame('#abcdef', doors_effective_colour($instance, 'doorcolour'));

        $instance->doorcolour = '#123456';
        $this->assertSame('#123456', doors_effective_colour($instance, 'doorcolour'));
    }
}
