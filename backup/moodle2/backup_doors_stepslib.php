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
 * Backup steps for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete structure for backup, with file and id annotations.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_doors_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $doors = new backup_nested_element('doors', ['id'], [
            'name', 'intro', 'introformat', 'printintro', 'numdoors', 'layout', 'gridcols', 'maxwidth', 'doorshape',
            'aspect', 'bgcolour', 'doorcolour', 'doortextcolour', 'openedcolour', 'colourmode',
            'palette', 'shownumbers',
            'showtitles', 'showactivityicon', 'buttonstyle', 'randomise', 'openmode', 'reopen',
            'markopened', 'openedstyle',
            'showprogress',
            'customcss', 'completionopened', 'timecreated', 'timemodified',
        ]);

        $doorlist = new backup_nested_element('doorlist');

        $door = new backup_nested_element('door', ['id'], [
            'doornumber', 'doorlabel', 'title', 'content', 'contentformat', 'linkurl', 'linktext',
            'linkmode', 'cmid', 'mediaposition', 'linknewwindow', 'availablefrom', 'doorcolour',
            'posx', 'posy', 'poswidth',
            'timecreated', 'timemodified',
        ]);

        $openings = new backup_nested_element('openings');

        $opening = new backup_nested_element('opening', ['id'], [
            'userid', 'timeopened',
        ]);

        $doors->add_child($doorlist);
        $doorlist->add_child($door);
        $door->add_child($openings);
        $openings->add_child($opening);

        $doors->set_source_table('doors', ['id' => backup::VAR_ACTIVITYID]);
        $door->set_source_table('doors_door', ['doorsid' => backup::VAR_PARENTID], 'doornumber ASC');

        if ($userinfo) {
            $opening->set_source_table('doors_opened', ['doorid' => backup::VAR_PARENTID]);
            $opening->annotate_ids('user', 'userid');
        }

        $door->annotate_ids('course_module', 'cmid');

        $doors->annotate_files('mod_doors', 'intro', null);
        $doors->annotate_files('mod_doors', 'background', null);
        $doors->annotate_files('mod_doors', 'doorclosed', null);
        $doors->annotate_files('mod_doors', 'dooropened', null);
        $door->annotate_files('mod_doors', 'content', 'id');
        $door->annotate_files('mod_doors', 'media', 'id');
        $door->annotate_files('mod_doors', 'doorimage', 'id');

        return $this->prepare_activity_structure($doors);
    }
}
