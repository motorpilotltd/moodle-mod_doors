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

declare(strict_types=1);

namespace mod_doors\completion;

use core_completion\activity_custom_completion;

/**
 * Custom completion rules for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {
    /**
     * Get the completion state for the given rule.
     *
     * @param string $rule The rule name.
     * @return int COMPLETION_COMPLETE or COMPLETION_INCOMPLETE.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $doors = $DB->get_record('doors', ['id' => $this->cm->instance], 'id, numdoors, completionopened');
        if (!$doors) {
            return COMPLETION_INCOMPLETE;
        }

        $required = (int)$doors->completionopened;
        if ($required <= 0) {
            return COMPLETION_INCOMPLETE;
        }

        $sql = "SELECT COUNT(o.id)
                  FROM {doors_opened} o
                  JOIN {doors_door} dd ON dd.id = o.doorid
                 WHERE o.doorsid = :doorsid
                   AND o.userid = :userid
                   AND dd.doornumber <= :numdoors";

        $count = (int)$DB->count_records_sql($sql, [
            'doorsid' => $doors->id,
            'userid' => $this->userid,
            'numdoors' => (int)$doors->numdoors,
        ]);

        return $count >= $required ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Rules defined by this module.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionopened'];
    }

    /**
     * Descriptions of the custom rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $required = $this->cm->customdata['customcompletionrules']['completionopened'] ?? 0;

        return [
            'completionopened' => get_string('completiondetail:opened', 'mod_doors', $required),
        ];
    }

    /**
     * Sort order of completion rules.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionopened'];
    }
}
