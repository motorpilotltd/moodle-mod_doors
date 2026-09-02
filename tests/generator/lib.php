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
 * Data generator for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_doors_generator extends testing_module_generator {
    /**
     * Create a doors instance.
     *
     * @param array|stdClass|null $record Instance data.
     * @param array|null $options Course module options.
     * @return stdClass The instance record with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;

        $defaults = [
            'numdoors' => 24,
            'openmode' => 'any',
            'layout' => 'grid',
            'gridcols' => 6,
            'aspect' => 'square',
            'doorshape' => 'rounded',
            'randomise' => 0,
            'reopen' => 1,
            'markopened' => 1,
            'openedstyle' => 'flip',
            'showprogress' => 1,
            'shownumbers' => 1,
            'showtitles' => 0,
            'showactivityicon' => 1,
            'buttonstyle' => 'theme',
            'colourmode' => 'fixed',
            'printintro' => 1,
            'maxwidth' => 0,
            'completionopened' => 0,
        ];
        foreach ($defaults as $field => $value) {
            if (!isset($record->$field)) {
                $record->$field = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
