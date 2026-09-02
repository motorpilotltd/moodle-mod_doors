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

namespace mod_doors\output;

use html_writer;
use moodle_url;

/**
 * The door opening report.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_table extends \table_sql {
    /** @var \stdClass The doors instance. */
    protected $doors;

    /** @var \stdClass The course module. */
    protected $cm;

    /** @var \stdClass The course. */
    protected $course;

    /** @var \context The module context. */
    protected $modulecontext;

    /** @var array Door records in number order. */
    protected $doorrecords;

    /** @var bool Whether completion is tracked for this activity. */
    protected $showcompletion;

    /** @var array userid => [doorid => timeopened] for the rows on this page. */
    protected $opened = [];

    /** @var array userid => completion state for the rows on this page. */
    protected $completions = [];

    /**
     * Constructor.
     *
     * @param string $uniqueid Table id.
     * @param \stdClass $doors The instance record.
     * @param \stdClass $cm The course module.
     * @param \stdClass $course The course.
     * @param \context $context The module context.
     * @param array $doorrecords Door records in number order.
     * @param bool $showcompletion Whether to include the completion column.
     */
    public function __construct($uniqueid, $doors, $cm, $course, $context, array $doorrecords, $showcompletion) {
        parent::__construct($uniqueid);

        $this->doors = $doors;
        $this->cm = $cm;
        $this->course = $course;
        $this->modulecontext = $context;
        $this->doorrecords = $doorrecords;
        $this->showcompletion = $showcompletion;

        $this->define_table_columns();
    }

    /**
     * Set up the columns and headers.
     *
     * @return void
     */
    protected function define_table_columns() {
        $columns = ['fullname', 'openedcount'];
        $headers = [get_string('fullname'), get_string('opened', 'mod_doors')];

        if ($this->showcompletion) {
            $columns[] = 'completionstate';
            $headers[] = get_string('completion', 'completion');
        }

        foreach ($this->doorrecords as $door) {
            $columns[] = 'door_' . $door->id;
            $headers[] = ($door->doorlabel !== null && $door->doorlabel !== '')
                ? format_string($door->doorlabel)
                : $door->doornumber;
        }

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->no_sorting('completionstate');
        foreach ($this->doorrecords as $door) {
            $this->no_sorting('door_' . $door->id);
        }

        $this->sortable(true, 'lastname', SORT_ASC);
        $this->collapsible(false);
        $this->set_attribute('class', 'generaltable doors-report-table');
    }

    /**
     * Build the query behind the table.
     *
     * @param string $search A name fragment to filter on, or an empty string.
     * @return void
     */
    public function build_sql($search = '') {
        global $DB;

        $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false);

        [$esql, $eparams] = get_enrolled_sql($this->modulecontext, 'mod/doors:view', 0, true);

        $openedsql = "(SELECT COUNT(o.id)
                         FROM {doors_opened} o
                         JOIN {doors_door} dd ON dd.id = o.doorid
                        WHERE o.userid = u.id
                          AND o.doorsid = :doorsid
                          AND dd.doornumber <= :numdoors)";

        // Whether the name fields arrive with a leading comma varies, so join them ourselves.
        $namefields = ltrim(trim($userfields->selects), ', ');

        $fields = "u.id, u.picture, u.imagealt, u.email, $namefields, $openedsql AS openedcount";
        $from = "{user} u JOIN ($esql) je ON je.id = u.id";
        $where = 'u.deleted = 0';

        $params = $eparams + $userfields->params + [
            'doorsid' => $this->doors->id,
            'numdoors' => (int)$this->doors->numdoors,
        ];

        if ($search !== '') {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $like = $DB->sql_like($fullname, ':search', false, false);
            $where .= " AND $like";
            $params['search'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);
    }

    /**
     * Fetch the page of rows, then the opening and completion data for those users.
     *
     * @param int $pagesize Rows per page.
     * @param bool $useinitialsbar Whether to use the initials bar.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        parent::query_db($pagesize, $useinitialsbar);

        if ($this->is_downloading()) {
            // Downloading leaves rawdata as a recordset, which must not be
            // walked twice, so load everyone rather than the ids on the page.
            $this->load_openings(null);
            $this->load_completions(null);
            return;
        }

        $userids = [];
        foreach ($this->rawdata as $row) {
            $userids[] = $row->id;
        }

        $this->load_openings($userids);
        $this->load_completions($userids);
    }

    /**
     * Load which doors these users have opened.
     *
     * @param array|null $userids User ids on this page, or null for everyone.
     * @return void
     */
    protected function load_openings($userids) {
        global $DB;

        $this->opened = [];
        if (is_array($userids) && !$userids) {
            return;
        }

        $params = ['doorsid' => $this->doors->id];
        $select = 'doorsid = :doorsid';

        if (is_array($userids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
            $select .= " AND userid $insql";
            $params += $inparams;
        }

        $records = $DB->get_records_select(
            'doors_opened',
            $select,
            $params,
            '',
            'id, userid, doorid, timeopened'
        );

        foreach ($records as $record) {
            $this->opened[$record->userid][$record->doorid] = $record->timeopened;
        }
    }

    /**
     * Load the completion state for these users.
     *
     * @param array|null $userids User ids on this page, or null for everyone.
     * @return void
     */
    protected function load_completions($userids) {
        global $DB;

        $this->completions = [];
        if (!$this->showcompletion || (is_array($userids) && !$userids)) {
            return;
        }

        $params = ['cmid' => $this->cm->id];
        $select = 'coursemoduleid = :cmid';

        if (is_array($userids)) {
            [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
            $select .= " AND userid $insql";
            $params += $inparams;
        }

        $records = $DB->get_records_select(
            'course_modules_completion',
            $select,
            $params,
            '',
            'id, userid, completionstate'
        );

        foreach ($records as $record) {
            $this->completions[$record->userid] = (int)$record->completionstate;
        }
    }

    /**
     * The participant's name, linked to their profile.
     *
     * @param \stdClass $row The row.
     * @return string
     */
    public function col_fullname($row) {
        $name = fullname($row, has_capability('moodle/site:viewfullnames', $this->modulecontext));

        if ($this->is_downloading()) {
            return $name;
        }

        return html_writer::link(
            new moodle_url('/user/view.php', ['id' => $row->id, 'course' => $this->course->id]),
            $name
        );
    }

    /**
     * How many doors this participant has opened.
     *
     * @param \stdClass $row The row.
     * @return string
     */
    public function col_openedcount($row) {
        $count = (int)$row->openedcount;

        if ($this->is_downloading()) {
            return $count;
        }

        return $count . ' / ' . count($this->doorrecords);
    }

    /**
     * The activity completion state.
     *
     * @param \stdClass $row The row.
     * @return string
     */
    public function col_completionstate($row) {
        $state = $this->completions[$row->id] ?? COMPLETION_INCOMPLETE;

        switch ($state) {
            case COMPLETION_COMPLETE:
            case COMPLETION_COMPLETE_PASS:
                $text = get_string('reportcomplete', 'mod_doors');
                $class = 'text-success';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $text = get_string('reportcompletefail', 'mod_doors');
                $class = 'text-danger';
                break;
            default:
                $text = get_string('reportincomplete', 'mod_doors');
                $class = 'text-muted';
        }

        if ($this->is_downloading()) {
            return $text;
        }

        return html_writer::span($text, $class);
    }

    /**
     * Fill the per door columns.
     *
     * @param string $column The column name.
     * @param \stdClass $row The row.
     * @return string|null
     */
    public function other_cols($column, $row) {
        if (strpos($column, 'door_') !== 0) {
            return null;
        }

        $doorid = (int)substr($column, 5);
        $timeopened = $this->opened[$row->id][$doorid] ?? null;

        if ($this->is_downloading()) {
            return $timeopened ? userdate($timeopened) : '';
        }

        if ($timeopened) {
            return html_writer::span('&#10003;', 'text-success', [
                'title' => userdate($timeopened),
            ]);
        }

        return html_writer::span('&ndash;', 'text-muted');
    }
}
