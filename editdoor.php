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
 * Edit the contents of a single door.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');
require_once($CFG->dirroot . '/mod/doors/door_form.php');

$id = required_param('id', PARAM_INT);
$doorid = required_param('doorid', PARAM_INT);

$cm = get_coursemodule_from_id('doors', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$doors = $DB->get_record('doors', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doors:manage', $context);

$door = $DB->get_record('doors_door', ['id' => $doorid, 'doorsid' => $doors->id], '*', MUST_EXIST);

$pageurl = new moodle_url('/mod/doors/editdoor.php', ['id' => $cm->id, 'doorid' => $door->id]);
$returnurl = new moodle_url('/mod/doors/edit.php', ['id' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_title(format_string($doors->name));
$PAGE->set_heading(format_string($course->fullname));

$editoroptions = [
    'subdirs' => 0,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'context' => $context,
    'noclean' => true,
    'trusttext' => false,
];

// Prepare the existing data.
$data = clone $door;
$data->id = $cm->id;
$data->doorid = $door->id;
$data->content = $door->content;
$data->contentformat = $door->contentformat;
$data = file_prepare_standard_editor($data, 'content', $editoroptions, $context, 'mod_doors', 'content', $door->id);

$draftmedia = file_get_submitted_draft_itemid('media');
file_prepare_draft_area($draftmedia, $context->id, 'mod_doors', 'media', $door->id, ['subdirs' => 0, 'maxfiles' => 1]);
$data->media = $draftmedia;

$draftimage = file_get_submitted_draft_itemid('doorimage');
file_prepare_draft_area(
    $draftimage,
    $context->id,
    'mod_doors',
    'doorimage',
    $door->id,
    ['subdirs' => 0, 'maxfiles' => 1]
);
$data->doorimage = $draftimage;

// Which course the activity picker is showing. Defaults to the course the
// linked activity lives in, or this one, and changes when the picker is reloaded.
$targetcm = doors_get_target_cm($door->cmid);
$defaultcourseid = $targetcm ? $targetcm->course : $course->id;
$linkcourseid = optional_param('linkcourseid', $defaultcourseid, PARAM_INT);

$data->linkcourseid = $linkcourseid;
$data->cmid = ($targetcm && $targetcm->course == $linkcourseid) ? $door->cmid : 0;

$mform = new mod_doors_door_form($pageurl, [
    'door' => $door,
    'doors' => $doors,
    'cmid' => $cm->id,
    'linkcourseid' => $linkcourseid,
    'activities' => doors_activity_options($linkcourseid, $cm->id),
    'editoroptions' => $editoroptions,
]);
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($formdata = $mform->get_data()) {
    $record = new stdClass();
    $record->id = $door->id;
    $record->title = $formdata->title;
    $record->doorlabel = $formdata->doorlabel;
    $record->linkurl = $formdata->linkurl;
    $record->linktext = $formdata->linktext;
    $record->linkmode = $formdata->linkmode;
    $record->mediaposition = ($formdata->mediaposition === 'below') ? 'below' : 'above';

    // Only store a link to an activity the editor can actually see.
    $record->cmid = 0;
    if (!empty($formdata->cmid)) {
        $chosen = doors_get_target_cm($formdata->cmid);
        if ($chosen && $chosen->uservisible && $chosen->has_view()) {
            $record->cmid = (int)$chosen->id;
        }
    }
    $record->linknewwindow = !empty($formdata->linknewwindow) ? 1 : 0;
    $record->availablefrom = !empty($formdata->availablefrom) ? (int)$formdata->availablefrom : 0;
    $record->doorcolour = doors_clean_colour($formdata->doorcolour);
    $record->timemodified = time();

    if ($doors->layout === 'free') {
        $record->posx = max(0, min(100, (float)$formdata->posx));
        $record->posy = max(0, min(100, (float)$formdata->posy));
        $record->poswidth = max(2, min(60, (float)$formdata->poswidth));
    }

    // Rich text content and its embedded files.
    $formdata->id = $door->id;
    $formdata = file_postupdate_standard_editor(
        $formdata,
        'content',
        $editoroptions,
        $context,
        'mod_doors',
        'content',
        $door->id
    );
    $record->content = $formdata->content;
    $record->contentformat = $formdata->contentformat;

    $DB->update_record('doors_door', $record);

    file_save_draft_area_files(
        $formdata->media,
        $context->id,
        'mod_doors',
        'media',
        $door->id,
        ['subdirs' => 0, 'maxfiles' => 1]
    );
    file_save_draft_area_files(
        $formdata->doorimage,
        $context->id,
        'mod_doors',
        'doorimage',
        $door->id,
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    redirect(
        $returnurl,
        get_string('doorsaved', 'mod_doors', $door->doornumber),
        0,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($doors->name));
$mform->display();
echo $OUTPUT->footer();
