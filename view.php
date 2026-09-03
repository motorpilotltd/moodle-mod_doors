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
 * Display a door calendar.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$d = optional_param('d', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('doors', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $doors = $DB->get_record('doors', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $doors = $DB->get_record('doors', ['id' => $d], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $doors->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('doors', $doors->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doors:view', $context);

$event = \mod_doors\event\course_module_viewed::create([
    'objectid' => $doors->id,
    'context' => $context,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('doors', $doors);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/doors/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($doors->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod-doors-view');

$doorrecords = doors_get_doors($doors);
$opened = doors_get_opened($doors, $USER->id);
$doors->alldoors = $doorrecords;

// The activity header shows the description by default, which can push the
// doors below the fold on a long one.
if (empty($doors->printintro)) {
    $PAGE->activityheader->set_attrs(['description' => '']);
}

$output = $PAGE->get_renderer('mod_doors');

$strings = [
    'close' => get_string('closebuttontitle', 'moodle'),
    'loading' => get_string('loading', 'mod_doors'),
    'errorloading' => get_string('errorloading', 'mod_doors'),
    'locked' => get_string('locked', 'mod_doors'),
    'alreadyopened' => get_string('alreadyopened', 'mod_doors'),
];

$config = [
    'openurl' => (new moodle_url('/mod/doors/open.php'))->out(false),
    'cmid' => (int)$cm->id,
    'sesskey' => sesskey(),
    'strings' => $strings,
    'containerid' => 'doors-cal-' . $cm->id,
];

$PAGE->requires->js(new moodle_url('/mod/doors/js/doors.js'), true);
$PAGE->requires->js_amd_inline('
require([], function() {
    window.ModDoors.init(' . json_encode($config) . ');
});
');

echo $OUTPUT->header();

// A maximum width on the calendar looks odd next to a full width description,
// so bring the activity header in to match.
if (!empty($doors->maxwidth)) {
    $width = (int)$doors->maxwidth;
    // The theme sets its own margins on the header, so these have to insist.
    echo html_writer::tag(
        'style',
        "body.mod-doors-view .activity-header {" .
        " max-width: {$width}px;" .
        " margin-left: auto !important;" .
        " margin-right: auto !important; }",
        ['data-doors-width' => $cm->id]
    );
}

if (!empty($doors->customcss)) {
    // Scope author supplied CSS to this activity only.
    $scoped = preg_replace('/[<>]/', '', $doors->customcss);
    echo html_writer::tag('style', $scoped, ['data-doors-custom' => $cm->id]);
}

if ($doorrecords) {
    echo $output->calendar($doors, $doorrecords, $opened, $cm, $context, false);
} else {
    echo $OUTPUT->notification(get_string('nodoors', 'mod_doors'), 'info');
}

echo $OUTPUT->footer();
