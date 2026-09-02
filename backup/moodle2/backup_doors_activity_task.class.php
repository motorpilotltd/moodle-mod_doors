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
 * Backup task for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/doors/backup/moodle2/backup_doors_stepslib.php');

/**
 * Backup task definition.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_doors_activity_task extends backup_activity_task {
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
        $this->add_step(new backup_doors_activity_structure_step('doors_structure', 'doors.xml'));
    }

    /**
     * Encode links to this activity.
     *
     * @param string $content The content to encode.
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/doors\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@DOORSINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/doors\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@DOORSVIEWBYID*$2@$', $content);

        return $content;
    }
}
