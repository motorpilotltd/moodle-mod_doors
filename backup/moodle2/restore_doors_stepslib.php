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
 * Restore steps for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one doors activity.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_doors_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('doors', '/activity/doors');
        $paths[] = new restore_path_element('doors_door', '/activity/doors/doorlist/door');

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'doors_opening',
                '/activity/doors/doorlist/door/openings/opening'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the doors element.
     *
     * @param array $data The data.
     * @return void
     */
    protected function process_doors($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('doors', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a door element.
     *
     * @param array $data The data.
     * @return void
     */
    protected function process_doors_door($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->doorsid = $this->get_new_parentid('doors');
        if (!empty($data->availablefrom)) {
            $data->availablefrom = $this->apply_date_offset($data->availablefrom);
        }
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('doors_door', $data);
        $this->set_mapping('doors_door', $oldid, $newitemid, true);
    }

    /**
     * Process an opening record.
     *
     * @param array $data The data.
     * @return void
     */
    protected function process_doors_opening($data) {
        global $DB;

        $data = (object)$data;
        $data->doorid = $this->get_new_parentid('doors_door');
        $data->doorsid = $this->get_new_parentid('doors');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timeopened = $this->apply_date_offset($data->timeopened);

        if (empty($data->userid)) {
            return;
        }

        $DB->insert_record('doors_opened', $data);
    }

    /**
     * Remap links to other activities once every module has been restored.
     *
     * This has to wait until the end: the activity a door points at may be
     * restored after this one. Where no mapping exists, because the target
     * was not part of the backup, the original id is kept only when this is
     * a same-site restore and the course module still exists - anywhere
     * else the id would be meaningless (or worse, collide with an unrelated
     * activity), so the link is cleared with a warning in the restore log.
     *
     * @return void
     */
    protected function after_restore() {
        global $DB;

        $doorsid = $this->task->get_activityid();
        $doors = $DB->get_records_select(
            'doors_door',
            'doorsid = :doorsid AND cmid > 0',
            ['doorsid' => $doorsid],
            '',
            'id, cmid, doornumber'
        );

        foreach ($doors as $door) {
            $newcmid = $this->get_mappingid('course_module', $door->cmid);
            if ($newcmid) {
                if ($newcmid != $door->cmid) {
                    $DB->set_field('doors_door', 'cmid', $newcmid, ['id' => $door->id]);
                }
                continue;
            }
            if (
                $this->task->is_samesite()
                && $DB->record_exists('course_modules', ['id' => $door->cmid])
            ) {
                continue;
            }
            $DB->set_field('doors_door', 'cmid', 0, ['id' => $door->id]);
            $this->log(
                'Activity link on door ' . $door->doornumber
                    . ' could not be restored and was removed.',
                backup::LOG_WARNING
            );
        }
    }

    /**
     * Add the related files once everything is restored.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_doors', 'intro', null);
        $this->add_related_files('mod_doors', 'background', null);
        $this->add_related_files('mod_doors', 'doorclosed', null);
        $this->add_related_files('mod_doors', 'dooropened', null);
        $this->add_related_files('mod_doors', 'content', 'doors_door');
        $this->add_related_files('mod_doors', 'media', 'doors_door');
        $this->add_related_files('mod_doors', 'doorimage', 'doors_door');
    }
}
