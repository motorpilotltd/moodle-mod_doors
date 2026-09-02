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
 * Restore task for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/doors/backup/moodle2/restore_doors_stepslib.php');

/**
 * Restore task definition.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_doors_activity_task extends restore_activity_task {
    /**
     * No particular settings for this activity.
     *
     * @return void
     */
    protected function define_my_settings() {
        return;
    }

    /**
     * Define the structure steps.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_doors_activity_structure_step('doors_structure', 'doors.xml'));
    }

    /**
     * Define the contents to be processed by the link decoder.
     *
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('doors', ['intro'], 'doors');
        $contents[] = new restore_decode_content('doors_door', ['content'], 'doors_door');

        return $contents;
    }

    /**
     * Define the decoding rules for links.
     *
     * @return array
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('DOORSVIEWBYID', '/mod/doors/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('DOORSINDEX', '/mod/doors/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }
}
