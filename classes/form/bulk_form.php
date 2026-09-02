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

namespace mod_doors\form;

/**
 * Bulk release date scheduling form.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_form extends \moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'bulkdates');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('date_time_selector', 'startdate', get_string('bulkstart', 'mod_doors'));
        $mform->addHelpButton('startdate', 'bulkstart', 'mod_doors');

        $mform->addElement('select', 'interval', get_string('bulkinterval', 'mod_doors'), [
            0 => get_string('bulkinterval:none', 'mod_doors'),
            DAYSECS => get_string('bulkinterval:daily', 'mod_doors'),
            DAYSECS * 7 => get_string('bulkinterval:weekly', 'mod_doors'),
        ]);
        $mform->setDefault('interval', DAYSECS);

        $mform->addElement('advcheckbox', 'weekdaysonly', get_string('bulkweekdays', 'mod_doors'));
        $mform->hideIf('weekdaysonly', 'interval', 'neq', (string)DAYSECS);

        $buttons = [
            $mform->createElement('submit', 'applydates', get_string('bulkapply', 'mod_doors')),
            $mform->createElement('submit', 'cleardates', get_string('bulkclear', 'mod_doors')),
        ];
        $mform->addGroup($buttons, 'bulkbuttons', '', [' '], false);
    }
}
