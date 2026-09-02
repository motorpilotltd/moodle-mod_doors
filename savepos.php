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
 * Ajax endpoint: save door positions for the free layout.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$positions = required_param('positions', PARAM_RAW);

require_sesskey();

$cm = get_coursemodule_from_id('doors', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$doors = $DB->get_record('doors', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/doors:manage', $context);

$decoded = json_decode($positions, true);
if (!is_array($decoded)) {
    echo json_encode(['status' => 'error', 'message' => get_string('errorsaving', 'mod_doors')]);
    die();
}

$now = time();
foreach ($decoded as $item) {
    if (!is_array($item) || empty($item['id']) || !isset($item['x'], $item['y'])) {
        continue;
    }
    $door = $DB->get_record('doors_door', ['id' => (int)$item['id'], 'doorsid' => $doors->id], 'id');
    if (!$door) {
        continue;
    }
    $update = new stdClass();
    $update->id = $door->id;
    $update->posx = max(0, min(100, (float)$item['x']));
    $update->posy = max(0, min(100, (float)$item['y']));
    if (isset($item['w'])) {
        $update->poswidth = max(2, min(60, (float)$item['w']));
    }
    $update->timemodified = $now;
    $DB->update_record('doors_door', $update);
}

echo json_encode(['status' => 'ok']);
