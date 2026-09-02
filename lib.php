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
 * Library of interface functions and constants for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Maximum number of doors a calendar may contain. */
define('DOORS_MAX_DOORS', 31);

/**
 * Return whether the module supports a given feature.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True/false, a value, or null if not known.
 */
function doors_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Add a new doors instance.
 *
 * @param stdClass $data Form data.
 * @param mod_doors_mod_form|null $mform The form.
 * @return int New instance id.
 */
function doors_add_instance($data, $mform = null) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->numdoors = min(DOORS_MAX_DOORS, max(1, (int)$data->numdoors));
    $data->id = $DB->insert_record('doors', $data);

    doors_post_process_files($data, $mform);
    doors_sync_doors($data->id, $data->numdoors);

    return $data->id;
}

/**
 * Update an existing doors instance.
 *
 * @param stdClass $data Form data.
 * @param mod_doors_mod_form|null $mform The form.
 * @return bool
 */
function doors_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();
    $data->numdoors = min(DOORS_MAX_DOORS, max(1, (int)$data->numdoors));
    $DB->update_record('doors', $data);

    doors_post_process_files($data, $mform);
    doors_sync_doors($data->id, $data->numdoors);

    return true;
}

/**
 * Save the instance level file areas (background and default door images).
 *
 * @param stdClass $data Form data (must contain id).
 * @param mod_doors_mod_form|null $mform The form.
 * @return void
 */
function doors_post_process_files($data, $mform = null) {
    if (empty($data->coursemodule)) {
        return;
    }
    $context = context_module::instance($data->coursemodule);
    foreach (['background', 'doorclosed', 'dooropened'] as $area) {
        if (isset($data->$area)) {
            file_save_draft_area_files(
                $data->$area,
                $context->id,
                'mod_doors',
                $area,
                0,
                ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']]
            );
        }
    }
}

/**
 * Delete a doors instance and everything belonging to it.
 *
 * @param int $id Instance id.
 * @return bool
 */
function doors_delete_instance($id) {
    global $DB;

    if (!$doors = $DB->get_record('doors', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('doors_opened', ['doorsid' => $doors->id]);
    $DB->delete_records('doors_door', ['doorsid' => $doors->id]);
    $DB->delete_records('doors', ['id' => $doors->id]);

    return true;
}

/**
 * Make sure door records exist for doors 1 to $numdoors.
 *
 * Doors are never deleted here. Reducing the number of doors simply hides the
 * ones above the new count, so their content comes back if the count is raised
 * again. Use doors_delete_door() to remove a stored door for good.
 *
 * @param int $doorsid Instance id.
 * @param int $numdoors Required number of doors.
 * @return void
 */
function doors_sync_doors($doorsid, $numdoors) {
    global $DB;

    $numdoors = min(DOORS_MAX_DOORS, max(1, (int)$numdoors));
    $existing = $DB->get_records('doors_door', ['doorsid' => $doorsid], 'doornumber ASC', 'id, doornumber');

    $have = [];
    foreach ($existing as $door) {
        $have[$door->doornumber] = $door->id;
    }

    $now = time();
    for ($i = 1; $i <= $numdoors; $i++) {
        if (isset($have[$i])) {
            continue;
        }
        $record = new stdClass();
        $record->doorsid = $doorsid;
        $record->doornumber = $i;
        $record->contentformat = FORMAT_HTML;
        $record->linkmode = 'link';
        $record->linknewwindow = 1;
        $record->availablefrom = 0;
        // Spread new doors over the canvas so the free layout is usable immediately.
        $percol = 6;
        $col = ($i - 1) % $percol;
        $row = (int)floor(($i - 1) / $percol);
        $record->posx = round(4 + ($col * 15.5), 3);
        $record->posy = round(4 + ($row * 18.0), 3);
        $record->poswidth = 12;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $DB->insert_record('doors_door', $record);
    }
}

/**
 * Permanently delete one door, its files and its opening records.
 *
 * @param stdClass $door The door record.
 * @param context $context The module context.
 * @return void
 */
function doors_delete_door($door, $context) {
    global $DB;

    $fs = get_file_storage();
    foreach (['doorimage', 'content', 'media'] as $area) {
        $fs->delete_area_files($context->id, 'mod_doors', $area, $door->id);
    }
    $DB->delete_records('doors_opened', ['doorid' => $door->id]);
    $DB->delete_records('doors_door', ['id' => $door->id]);
}

/**
 * Serve files belonging to this module.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param context $context Context.
 * @param string $filearea File area.
 * @param array $args Path arguments.
 * @param bool $forcedownload Force download.
 * @param array $options Options.
 * @return bool False if the file was not found.
 */
function doors_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, true, $cm);

    if (!has_capability('mod/doors:view', $context)) {
        return false;
    }

    $instancelevel = ['background', 'doorclosed', 'dooropened'];
    $doorlevel = ['doorimage', 'content', 'media'];

    if (in_array($filearea, $instancelevel, true)) {
        $itemid = 0;
        array_shift($args);
    } else if (in_array($filearea, $doorlevel, true)) {
        $itemid = (int)array_shift($args);
        // Make sure the door really belongs to this activity.
        if (!$DB->record_exists('doors_door', ['id' => $itemid, 'doorsid' => $cm->instance])) {
            return false;
        }
    } else {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_doors', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Add module specific information to the course module info.
 *
 * @param stdClass $coursemodule The course module.
 * @return cached_cm_info|null
 */
function doors_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat, completionopened';
    if (!$doors = $DB->get_record('doors', ['id' => $coursemodule->instance], $fields)) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $doors->name;

    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('doors', $doors, $coursemodule->id, false);
    }

    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $info->customdata['customcompletionrules']['completionopened'] = $doors->completionopened;
    }

    return $info;
}

/**
 * Remove user data as part of a course reset.
 *
 * @param stdClass $data Reset form data.
 * @return array
 */
function doors_reset_userdata($data) {
    global $DB;

    $status = [];
    if (!empty($data->reset_doors_opened)) {
        $sql = "SELECT id FROM {doors} WHERE course = ?";
        $DB->delete_records_select('doors_opened', "doorsid IN ($sql)", [$data->courseid]);
        $status[] = [
            'component' => get_string('modulenameplural', 'mod_doors'),
            'item' => get_string('resetopened', 'mod_doors'),
            'error' => false,
        ];
    }

    return $status;
}

/**
 * Add the reset option to the course reset form.
 *
 * @param MoodleQuickForm $mform The form.
 * @return void
 */
function doors_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'doorsheader', get_string('modulenameplural', 'mod_doors'));
    $mform->addElement('advcheckbox', 'reset_doors_opened', get_string('resetopened', 'mod_doors'));
}

/**
 * Extend the activity settings navigation.
 *
 * @param settings_navigation $settings The settings navigation.
 * @param navigation_node $node The module node.
 * @return void
 */
function doors_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    $cm = $settings->get_page()->cm;
    if (empty($cm)) {
        return;
    }

    $context = context_module::instance($cm->id);

    if (has_capability('mod/doors:manage', $context)) {
        $node->add(
            get_string('managedoors', 'mod_doors'),
            new moodle_url('/mod/doors/edit.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_doors_edit',
            new pix_icon('t/edit', '')
        );
    }

    if (has_capability('mod/doors:viewreport', $context)) {
        $node->add(
            get_string('report', 'mod_doors'),
            new moodle_url('/mod/doors/report.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'mod_doors_report',
            new pix_icon('i/report', '')
        );
    }
}
