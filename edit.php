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
 * Manage the doors in a calendar.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');
require_once($CFG->libdir . '/formslib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$doorid = optional_param('doorid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('doors', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$doors = $DB->get_record('doors', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doors:manage', $context);

$pageurl = new moodle_url('/mod/doors/edit.php', ['id' => $cm->id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($doors->name));
$PAGE->set_heading(format_string($course->fullname));

// Clear the contents of one door.
if ($action === 'clear' && $doorid && confirm_sesskey()) {
    $door = $DB->get_record('doors_door', ['id' => $doorid, 'doorsid' => $doors->id], '*', MUST_EXIST);
    $fs = get_file_storage();
    foreach (['doorimage', 'content', 'media'] as $area) {
        $fs->delete_area_files($context->id, 'mod_doors', $area, $door->id);
    }
    $DB->set_field('doors_door', 'content', '', ['id' => $door->id]);
    $DB->set_field('doors_door', 'title', '', ['id' => $door->id]);
    $DB->set_field('doors_door', 'linkurl', '', ['id' => $door->id]);
    $DB->set_field('doors_door', 'linktext', '', ['id' => $door->id]);
    $DB->set_field('doors_door', 'cmid', 0, ['id' => $door->id]);
    $DB->set_field('doors_door', 'timemodified', time(), ['id' => $door->id]);
    redirect(
        $pageurl,
        get_string('doorcleared', 'mod_doors', $door->doornumber),
        0,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Permanently delete a stored door that is hidden by the current door count.
if ($action === 'delete' && $doorid && confirm_sesskey()) {
    $door = $DB->get_record('doors_door', ['id' => $doorid, 'doorsid' => $doors->id], '*', MUST_EXIST);
    if ($door->doornumber <= $doors->numdoors) {
        throw new moodle_exception('errordeleteactive', 'mod_doors');
    }
    doors_delete_door($door, $context);
    redirect(
        $pageurl,
        get_string('doordeleted', 'mod_doors', $door->doornumber),
        0,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$bulkform = new \mod_doors\form\bulk_form($pageurl, ['cmid' => $cm->id]);
if ($bulkdata = $bulkform->get_data()) {
    $all = $DB->get_records_select(
        'doors_door',
        'doorsid = :doorsid AND doornumber <= :numdoors',
        ['doorsid' => $doors->id, 'numdoors' => (int)$doors->numdoors],
        'doornumber ASC'
    );
    if (!empty($bulkdata->cleardates)) {
        foreach ($all as $door) {
            $DB->set_field('doors_door', 'availablefrom', 0, ['id' => $door->id]);
        }
        redirect(
            $pageurl,
            get_string('bulkcleared', 'mod_doors'),
            0,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        $time = (int)$bulkdata->startdate;
        $interval = (int)$bulkdata->interval;
        foreach ($all as $door) {
            if (!empty($bulkdata->weekdaysonly) && $interval == DAYSECS) {
                // Skip Saturdays and Sundays, in the user's timezone rather
                // than the server's.
                while (in_array((int)usergetdate($time)['wday'], [0, 6], true)) {
                    $time += DAYSECS;
                }
            }
            $DB->set_field('doors_door', 'availablefrom', $time, ['id' => $door->id]);
            $time += $interval;
        }
        redirect(
            $pageurl,
            get_string('bulkapplied', 'mod_doors'),
            0,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

$doorrecords = doors_get_doors($doors);
$doors->alldoors = $doorrecords;
$output = $PAGE->get_renderer('mod_doors');

// JavaScript must be registered before any output starts.
if ($doors->layout === 'free') {
    $PAGE->requires->js(new moodle_url('/mod/doors/js/doors_edit.js'), true);
    $PAGE->requires->js_amd_inline('
require([], function() {
    window.ModDoorsEdit.init(' . json_encode([
        'containerid' => 'doors-cal-' . $cm->id . '-edit',
        'saveurl' => (new moodle_url('/mod/doors/savepos.php'))->out(false),
        'cmid' => (int)$cm->id,
        'sesskey' => sesskey(),
        'buttonid' => 'doors-savepos',
        'biggerid' => 'doors-size-up',
        'smallerid' => 'doors-size-down',
        'strings' => [
            'saved' => get_string('positionssaved', 'mod_doors'),
            'error' => get_string('errorsaving', 'mod_doors'),
            'selectfirst' => get_string('selectdoorfirst', 'mod_doors'),
        ],
    ]) . ');
});
');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managedoors', 'mod_doors'));

$navlinks = html_writer::link(
    new moodle_url('/mod/doors/view.php', ['id' => $cm->id]),
    get_string('backtocalendar', 'mod_doors'),
    ['class' => 'btn btn-secondary mr-2 me-2']
);
if (has_capability('mod/doors:viewreport', $context)) {
    $navlinks .= html_writer::link(
        new moodle_url('/mod/doors/report.php', ['id' => $cm->id]),
        get_string('report', 'mod_doors'),
        ['class' => 'btn btn-secondary']
    );
}
echo html_writer::div($navlinks, 'mb-3');

// Free layout: drag the doors where you want them.
if ($doors->layout === 'free') {
    echo $OUTPUT->heading(get_string('positiondoors', 'mod_doors'), 3);
    echo html_writer::div(get_string('positiondoorsinfo', 'mod_doors'), 'text-muted mb-2');
    echo $output->calendar($doors, $doorrecords, [], $cm, $context, true);
    echo html_writer::div(
        html_writer::tag('button', get_string('savepositions', 'mod_doors'), [
            'type' => 'button',
            'class' => 'btn btn-primary mr-2 me-2',
            'id' => 'doors-savepos',
        ]) .
        html_writer::tag('button', get_string('doorbigger', 'mod_doors'), [
            'type' => 'button',
            'class' => 'btn btn-secondary mr-2 me-2',
            'id' => 'doors-size-up',
        ]) .
        html_writer::tag('button', get_string('doorsmaller', 'mod_doors'), [
            'type' => 'button',
            'class' => 'btn btn-secondary mr-2 me-2',
            'id' => 'doors-size-down',
        ]) .
        html_writer::span('', 'doors-savepos-status'),
        'mb-4 mt-2'
    );
}

// Door contents table.
$fs = get_file_storage();

// Describe what has been put behind a door: takes the door record, the
// module context and the file storage, returns a summary string.
$summarise = function ($door, $context, $fs) {
    $bits = [];
    if (trim(strip_tags($door->content ?? '')) !== '') {
        $bits[] = get_string('hastext', 'mod_doors');
    }
    if ($fs->get_area_files($context->id, 'mod_doors', 'media', $door->id, 'id', false)) {
        $bits[] = get_string('hasmedia', 'mod_doors');
    }
    if (!empty($door->linkurl)) {
        $bits[] = get_string('haslink', 'mod_doors');
    }
    if (!empty($door->cmid)) {
        $bits[] = get_string('hasactivity', 'mod_doors');
    }
    if ($fs->get_area_files($context->id, 'mod_doors', 'doorimage', $door->id, 'id', false)) {
        $bits[] = get_string('hasdoorimage', 'mod_doors');
    }

    return $bits ? implode(', ', $bits)
        : html_writer::span(get_string('empty', 'mod_doors'), 'text-muted');
};

$table = new html_table();
$table->head = [
    get_string('door', 'mod_doors'),
    get_string('doortitle', 'mod_doors'),
    get_string('contentsummary', 'mod_doors'),
    get_string('availablefrom', 'mod_doors'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable doors-admin-table';

$ordered = $DB->get_records_select(
    'doors_door',
    'doorsid = :doorsid AND doornumber <= :numdoors',
    ['doorsid' => $doors->id, 'numdoors' => (int)$doors->numdoors],
    'doornumber ASC'
);

foreach ($ordered as $door) {
    $label = ($door->doorlabel !== null && $door->doorlabel !== '')
        ? $door->doornumber . ' (' . s($door->doorlabel) . ')'
        : $door->doornumber;

    $editurl = new moodle_url('/mod/doors/editdoor.php', ['id' => $cm->id, 'doorid' => $door->id]);
    $clearurl = new moodle_url('/mod/doors/edit.php', [
        'id' => $cm->id,
        'doorid' => $door->id,
        'action' => 'clear',
        'sesskey' => sesskey(),
    ]);

    $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
    $actions .= $OUTPUT->action_icon(
        $clearurl,
        new pix_icon('t/delete', get_string('cleardoor', 'mod_doors')),
        null,
        ['data-doors-confirm' => get_string('cleardoorconfirm', 'mod_doors', $door->doornumber)]
    );

    $table->data[] = [
        $label,
        format_string($door->title ?? ''),
        $summarise($door, $context, $fs),
        $door->availablefrom ? userdate($door->availablefrom) : '-',
        $actions,
    ];
}

echo html_writer::table($table);

// Doors that are stored but hidden because the door count was reduced.
$hidden = doors_get_hidden_doors($doors);
if ($hidden) {
    echo $OUTPUT->heading(get_string('hiddendoors', 'mod_doors'), 3);
    echo html_writer::div(get_string('hiddendoorsinfo', 'mod_doors', count($hidden)), 'text-muted mb-2');

    $hiddentable = new html_table();
    $hiddentable->head = [
        get_string('door', 'mod_doors'),
        get_string('doortitle', 'mod_doors'),
        get_string('contentsummary', 'mod_doors'),
        get_string('actions'),
    ];
    $hiddentable->attributes['class'] = 'generaltable doors-hidden-table';

    foreach ($hidden as $door) {
        $deleteurl = new moodle_url('/mod/doors/edit.php', [
            'id' => $cm->id,
            'doorid' => $door->id,
            'action' => 'delete',
            'sesskey' => sesskey(),
        ]);
        $editurl = new moodle_url('/mod/doors/editdoor.php', ['id' => $cm->id, 'doorid' => $door->id]);

        $actions = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
        $actions .= $OUTPUT->action_icon(
            $deleteurl,
            new pix_icon('t/delete', get_string('deletedoor', 'mod_doors')),
            null,
            ['data-doors-confirm' => get_string('deletedoorconfirm', 'mod_doors', $door->doornumber)]
        );

        $hiddentable->data[] = [
            $door->doornumber,
            format_string($door->title ?? ''),
            $summarise($door, $context, $fs),
            $actions,
        ];
    }

    echo html_writer::table($hiddentable);
}

echo $OUTPUT->heading(get_string('bulkdates', 'mod_doors'), 3);
$bulkform->display();

$PAGE->requires->js_amd_inline('
require([], function() {
    document.querySelectorAll("[data-doors-confirm]").forEach(function(el) {
        el.addEventListener("click", function(e) {
            if (!window.confirm(el.getAttribute("data-doors-confirm"))) {
                e.preventDefault();
            }
        });
    });
});
');

echo $OUTPUT->footer();
