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
 * Who has opened which doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');
require_once($CFG->libdir . '/tablelib.php');

$id = required_param('id', PARAM_INT);
$search = trim(optional_param('search', '', PARAM_TEXT));
$perpage = optional_param('perpage', 25, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('doors', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$doors = $DB->get_record('doors', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doors:viewreport', $context);

$perpage = max(5, min(200, $perpage));

$pageurl = new moodle_url('/mod/doors/report.php', ['id' => $cm->id]);
if ($search !== '') {
    $pageurl->param('search', $search);
}
if ($perpage != 25) {
    $pageurl->param('perpage', $perpage);
}

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($doors->name));
$PAGE->set_heading(format_string($course->fullname));

$doorrecords = doors_get_doors($doors);

$completion = new completion_info($course);
$showcompletion = $completion->is_enabled($cm) != COMPLETION_TRACKING_NONE;

$table = new \mod_doors\output\report_table(
    'mod-doors-report-' . $cm->id,
    $doors,
    $cm,
    $course,
    $context,
    $doorrecords,
    $showcompletion
);
$table->define_baseurl($pageurl);
$table->build_sql($search);

$table->is_downloadable(true);
$table->show_download_buttons_at([TABLE_P_BOTTOM]);

$filename = clean_filename(format_string($doors->name) . '-' . userdate(time(), '%Y%m%d'));
$table->is_downloading($download, $filename, get_string('report', 'mod_doors'));

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('report', 'mod_doors'));

    $navlinks = html_writer::link(
        new moodle_url('/mod/doors/view.php', ['id' => $cm->id]),
        get_string('backtocalendar', 'mod_doors'),
        ['class' => 'btn btn-secondary mr-2 me-2']
    );
    if (has_capability('mod/doors:manage', $context)) {
        $navlinks .= html_writer::link(
            new moodle_url('/mod/doors/edit.php', ['id' => $cm->id]),
            get_string('managedoors', 'mod_doors'),
            ['class' => 'btn btn-secondary']
        );
    }
    echo html_writer::div($navlinks, 'mb-3');

    // Name filter.
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => (new moodle_url('/mod/doors/report.php'))->out(false),
        'class' => 'doors-report-filter form-inline d-flex flex-wrap align-items-center mb-3',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    echo html_writer::tag('label', get_string('reportsearch', 'mod_doors'), [
        'for' => 'doors-report-search',
        'class' => 'mr-2 me-2',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'id' => 'doors-report-search',
        'name' => 'search',
        'value' => $search,
        'class' => 'form-control mr-2 me-2',
        'placeholder' => get_string('reportsearchplaceholder', 'mod_doors'),
    ]);
    echo html_writer::tag('button', get_string('reportsearchgo', 'mod_doors'), [
        'type' => 'submit',
        'class' => 'btn btn-primary mr-2 me-2',
    ]);
    if ($search !== '') {
        echo html_writer::link(
            new moodle_url('/mod/doors/report.php', ['id' => $cm->id]),
            get_string('reportsearchclear', 'mod_doors'),
            ['class' => 'btn btn-secondary']
        );
    }
    echo html_writer::end_tag('form');

    if (!$doorrecords) {
        echo $OUTPUT->notification(get_string('nodoors', 'mod_doors'), 'info');
        echo $OUTPUT->footer();
        die();
    }
}

$table->out($perpage, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
