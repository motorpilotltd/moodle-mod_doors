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
 * Instance settings form for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');

/**
 * Instance settings form.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_doors_mod_form extends moodleform_mod {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $config = get_config('mod_doors');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('doorsname', 'mod_doors'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // Doors.
        $mform->addElement('header', 'doorssettings', get_string('doorssettings', 'mod_doors'));
        $mform->setExpanded('doorssettings');

        $range = [];
        for ($i = 1; $i <= DOORS_MAX_DOORS; $i++) {
            $range[$i] = $i;
        }
        $mform->addElement('select', 'numdoors', get_string('numdoors', 'mod_doors'), $range);
        $mform->setDefault('numdoors', !empty($config->numdoors) ? $config->numdoors : 24);
        $mform->addHelpButton('numdoors', 'numdoors', 'mod_doors');

        $mform->addElement('select', 'openmode', get_string('openmode', 'mod_doors'), [
            'any' => get_string('openmode:any', 'mod_doors'),
            'sequential' => get_string('openmode:sequential', 'mod_doors'),
        ]);
        $mform->setDefault('openmode', 'any');
        $mform->addHelpButton('openmode', 'openmode', 'mod_doors');

        $mform->addElement('advcheckbox', 'randomise', get_string('randomise', 'mod_doors'));
        $mform->addHelpButton('randomise', 'randomise', 'mod_doors');

        $mform->addElement('advcheckbox', 'reopen', get_string('reopen', 'mod_doors'));
        $mform->setDefault('reopen', 1);
        $mform->addHelpButton('reopen', 'reopen', 'mod_doors');

        $mform->addElement('advcheckbox', 'markopened', get_string('markopened', 'mod_doors'));
        $mform->setDefault('markopened', 1);

        $mform->addElement('select', 'openedstyle', get_string('openedstyle', 'mod_doors'), [
            'flip' => get_string('openedstyle:flip', 'mod_doors'),
            'dim' => get_string('openedstyle:dim', 'mod_doors'),
        ]);
        $mform->setDefault('openedstyle', !empty($config->openedstyle) ? $config->openedstyle : 'flip');
        $mform->addHelpButton('openedstyle', 'openedstyle', 'mod_doors');
        $mform->hideIf('openedstyle', 'markopened', 'notchecked');

        $mform->addElement('advcheckbox', 'showprogress', get_string('showprogress', 'mod_doors'));
        $mform->setDefault('showprogress', 1);

        // Appearance.
        $mform->addElement('header', 'appearance', get_string('appearance', 'mod_doors'));

        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'mod_doors'));
        $mform->setDefault('printintro', 1);
        $mform->addHelpButton('printintro', 'printintro', 'mod_doors');

        $mform->addElement('select', 'layout', get_string('layout', 'mod_doors'), doors_layout_options());
        $mform->setDefault('layout', !empty($config->layout) ? $config->layout : 'grid');
        $mform->addHelpButton('layout', 'layout', 'mod_doors');

        $cols = [];
        for ($i = 1; $i <= 10; $i++) {
            $cols[$i] = $i;
        }
        $mform->addElement('select', 'gridcols', get_string('gridcols', 'mod_doors'), $cols);
        $mform->setDefault('gridcols', 6);
        $mform->hideIf('gridcols', 'layout', 'neq', 'grid');

        $mform->addElement('text', 'maxwidth', get_string('maxwidth', 'mod_doors'), ['size' => 8]);
        $mform->setType('maxwidth', PARAM_INT);
        $mform->setDefault('maxwidth', !empty($config->maxwidth) ? $config->maxwidth : 0);
        $mform->addHelpButton('maxwidth', 'maxwidth', 'mod_doors');

        $mform->addElement('advcheckbox', 'centredoors', get_string('centredoors', 'mod_doors'));
        $mform->addHelpButton('centredoors', 'centredoors', 'mod_doors');
        $mform->hideIf('centredoors', 'layout', 'neq', 'grid');

        $mform->addElement('select', 'aspect', get_string('aspect', 'mod_doors'), doors_aspect_options());
        $mform->setDefault('aspect', 'square');
        $mform->hideIf('aspect', 'layout', 'neq', 'grid');

        $mform->addElement('select', 'doorshape', get_string('doorshape', 'mod_doors'), doors_shape_options());
        $mform->setDefault('doorshape', !empty($config->doorshape) ? $config->doorshape : 'rounded');

        $mform->addElement('select', 'facelayout', get_string('facelayout', 'mod_doors'), [
            'overlay' => get_string('facelayout:overlay', 'mod_doors'),
            'stacked' => get_string('facelayout:stacked', 'mod_doors'),
        ]);
        $mform->setDefault('facelayout', 'overlay');
        $mform->addHelpButton('facelayout', 'facelayout', 'mod_doors');

        $mform->addElement('advcheckbox', 'transparentdoors', get_string('transparentdoors', 'mod_doors'));
        $mform->addHelpButton('transparentdoors', 'transparentdoors', 'mod_doors');

        $gaps = [];
        foreach ([0, 2, 4, 6, 8, 10, 12, 16, 20, 24, 32] as $gap) {
            $gaps[$gap] = $gap . 'px';
        }
        $mform->addElement('select', 'doorgap', get_string('doorgap', 'mod_doors'), $gaps);
        $mform->setDefault('doorgap', 10);
        $mform->addHelpButton('doorgap', 'doorgap', 'mod_doors');

        $mform->addElement('select', 'backgroundfit', get_string('backgroundfit', 'mod_doors'), [
            'cover' => get_string('backgroundfit:cover', 'mod_doors'),
            'fit' => get_string('backgroundfit:fit', 'mod_doors'),
        ]);
        $mform->setDefault('backgroundfit', 'cover');
        $mform->addHelpButton('backgroundfit', 'backgroundfit', 'mod_doors');

        $imageoptions = [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ];

        $mform->addElement('filemanager', 'background', get_string('background', 'mod_doors'), null, $imageoptions);
        $mform->addHelpButton('background', 'background', 'mod_doors');

        $opacities = [100 => get_string('bgopacity:full', 'mod_doors')];
        foreach ([90, 80, 70, 60, 50, 40, 30, 20, 10] as $percent) {
            $opacities[$percent] = $percent . '%';
        }
        $mform->addElement('select', 'bgopacity', get_string('bgopacity', 'mod_doors'), $opacities);
        $mform->setDefault('bgopacity', 100);
        $mform->addHelpButton('bgopacity', 'bgopacity', 'mod_doors');

        $mform->addElement('filemanager', 'doorclosed', get_string('doorclosed', 'mod_doors'), null, $imageoptions);
        $mform->addHelpButton('doorclosed', 'doorclosed', 'mod_doors');

        $mform->addElement('filemanager', 'dooropened', get_string('dooropened', 'mod_doors'), null, $imageoptions);
        $mform->addHelpButton('dooropened', 'dooropened', 'mod_doors');

        $mform->addElement(
            'text',
            'bgcolour',
            get_string('bgcolour', 'mod_doors'),
            ['size' => 12, 'placeholder' => $config->bgcolour ?? '']
        );
        $mform->setType('bgcolour', PARAM_TEXT);
        $mform->addHelpButton('bgcolour', 'colourfield', 'mod_doors');

        $mform->addElement('select', 'colourmode', get_string('colourmode', 'mod_doors'), [
            'fixed' => get_string('colourmode:fixed', 'mod_doors'),
            'varied' => get_string('colourmode:varied', 'mod_doors'),
        ]);
        $mform->setDefault('colourmode', !empty($config->colourmode) ? $config->colourmode : 'fixed');
        $mform->addHelpButton('colourmode', 'colourmode', 'mod_doors');

        $mform->addElement(
            'text',
            'doorcolour',
            get_string('doorcolour', 'mod_doors'),
            ['size' => 12, 'placeholder' => $config->doorcolour ?? '']
        );
        $mform->setType('doorcolour', PARAM_TEXT);
        $mform->addHelpButton('doorcolour', 'doorcolour', 'mod_doors');
        $mform->hideIf('doorcolour', 'colourmode', 'eq', 'varied');

        $mform->addElement(
            'textarea',
            'palette',
            get_string('palette', 'mod_doors'),
            ['rows' => 3, 'cols' => 60, 'spellcheck' => 'false',
            'placeholder' => $config->palette ?? '']
        );
        $mform->setType('palette', PARAM_TEXT);
        $mform->addHelpButton('palette', 'palette', 'mod_doors');
        $mform->hideIf('palette', 'colourmode', 'neq', 'varied');

        $mform->addElement(
            'text',
            'openedcolour',
            get_string('openedcolour', 'mod_doors'),
            ['size' => 12, 'placeholder' => $config->openedcolour ?? '']
        );
        $mform->setType('openedcolour', PARAM_TEXT);
        $mform->addHelpButton('openedcolour', 'colourfield', 'mod_doors');

        $mform->addElement(
            'text',
            'doortextcolour',
            get_string('doortextcolour', 'mod_doors'),
            ['size' => 12, 'placeholder' => $config->doortextcolour ?? '']
        );
        $mform->setType('doortextcolour', PARAM_TEXT);
        $mform->addHelpButton('doortextcolour', 'colourfield', 'mod_doors');

        $mform->addElement('advcheckbox', 'shownumbers', get_string('shownumbers', 'mod_doors'));
        $mform->setDefault('shownumbers', 1);

        $mform->addElement('advcheckbox', 'showtitles', get_string('showtitles', 'mod_doors'));
        $mform->addHelpButton('showtitles', 'showtitles', 'mod_doors');

        $mform->addElement('advcheckbox', 'showactivityicon', get_string('showactivityicon', 'mod_doors'));
        $mform->setDefault(
            'showactivityicon',
            isset($config->showactivityicon) ? $config->showactivityicon : 1
        );
        $mform->addHelpButton('showactivityicon', 'showactivityicon', 'mod_doors');

        $mform->addElement('select', 'buttonstyle', get_string('buttonstyle', 'mod_doors'), [
            'theme' => get_string('buttonstyle:theme', 'mod_doors'),
            'calendar' => get_string('buttonstyle:calendar', 'mod_doors'),
        ]);
        $mform->setDefault('buttonstyle', !empty($config->buttonstyle) ? $config->buttonstyle : 'theme');
        $mform->addHelpButton('buttonstyle', 'buttonstyle', 'mod_doors');

        $mform->addElement(
            'textarea',
            'customcss',
            get_string('customcss', 'mod_doors'),
            ['rows' => 8, 'cols' => 60, 'spellcheck' => 'false', 'id' => 'id_doors_customcss']
        );
        $mform->setType('customcss', PARAM_RAW);
        $mform->addHelpButton('customcss', 'customcss', 'mod_doors');

        $mform->addElement(
            'static',
            'customcssreference',
            '',
            doors_css_reference_html(!empty($this->_cm->id) ? $this->_cm->id : 0)
        );

        $PAGE->requires->js(new moodle_url('/mod/doors/js/doors_cssref.js'), false);
        $PAGE->requires->js_amd_inline('
require([], function() {
    window.ModDoorsCssRef.init(' . json_encode(['textareaid' => 'id_doors_customcss']) . ');
});
');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Prepare the form data (draft file areas).
     *
     * @param array $defaultvalues Default values.
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        // The completion elements carry a suffix on the default completion
        // form (Moodle 4.3+), so the checkbox state must be derived under
        // the same suffixed names the elements were created with.
        $suffix = $this->get_suffix();
        $completionopenedel = 'completionopened' . $suffix;
        $defaultvalues['completionopenedenabled' . $suffix] =
            !empty($defaultvalues[$completionopenedel]) ? 1 : 0;
        if (empty($defaultvalues[$completionopenedel])) {
            $defaultvalues[$completionopenedel] = 1;
        }

        if ($this->current->instance) {
            foreach (['background', 'doorclosed', 'dooropened'] as $area) {
                $draftitemid = file_get_submitted_draft_itemid($area);
                file_prepare_draft_area(
                    $draftitemid,
                    $this->context->id,
                    'mod_doors',
                    $area,
                    0,
                    ['subdirs' => 0, 'maxfiles' => 1]
                );
                $defaultvalues[$area] = $draftitemid;
            }
        } else {
            foreach (['background', 'doorclosed', 'dooropened'] as $area) {
                $draftitemid = file_get_submitted_draft_itemid($area);
                file_prepare_draft_area($draftitemid, null, 'mod_doors', $area, 0);
                $defaultvalues[$area] = $draftitemid;
            }
        }
    }

    /**
     * Add the custom completion rule.
     *
     * @return array Element names.
     */
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();

        $enabledel = 'completionopenedenabled' . $suffix;
        $countel = 'completionopened' . $suffix;
        $groupel = 'completionopenedgroup' . $suffix;

        $group = [];
        $group[] = $mform->createElement(
            'checkbox',
            $enabledel,
            '',
            get_string('completionopened', 'mod_doors')
        );
        $group[] = $mform->createElement('text', $countel, '', ['size' => 3]);
        $mform->setType($countel, PARAM_INT);
        $mform->addGroup(
            $group,
            $groupel,
            get_string('completionopenedgroup', 'mod_doors'),
            [' '],
            false
        );
        $mform->hideIf($countel, $enabledel, 'notchecked');
        $mform->addHelpButton($groupel, 'completionopenedgroup', 'mod_doors');

        return [$groupel];
    }

    /**
     * Whether a custom completion rule is enabled.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();

        return !empty($data['completionopenedenabled' . $suffix]) && $data['completionopened' . $suffix] > 0;
    }

    /**
     * Post process the submitted data.
     *
     * @param stdClass $data Submitted data.
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        foreach (['bgcolour', 'doorcolour', 'openedcolour', 'doortextcolour'] as $field) {
            if (isset($data->$field)) {
                $data->$field = doors_clean_colour($data->$field);
            }
        }

        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? null;
            $autocompletion = !empty($completion) && $completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->{'completionopenedenabled' . $suffix}) || !$autocompletion) {
                $data->{'completionopened' . $suffix} = 0;
            }
        }
    }

    /**
     * Validate the submitted data.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['numdoors'] < 1 || $data['numdoors'] > DOORS_MAX_DOORS) {
            $errors['numdoors'] = get_string('errornumdoors', 'mod_doors', DOORS_MAX_DOORS);
        }

        foreach (['bgcolour', 'doorcolour', 'openedcolour', 'doortextcolour'] as $field) {
            if (!empty($data[$field]) && doors_clean_colour($data[$field]) === '') {
                $errors[$field] = get_string('errorcolour', 'mod_doors');
            }
        }

        if (!empty($data['maxwidth']) && ($data['maxwidth'] < 320 || $data['maxwidth'] > 4000)) {
            $errors['maxwidth'] = get_string('errormaxwidth', 'mod_doors');
        }

        if (!empty($data['palette']) && !doors_parse_palette($data['palette'])) {
            $errors['palette'] = get_string('errorpalette', 'mod_doors');
        }

        $suffix = $this->get_suffix();
        if (!empty($data['completionopenedenabled' . $suffix])) {
            $required = (int)($data['completionopened' . $suffix] ?? 0);
            // On the default completion form there is no numdoors to check against.
            $maxdoors = isset($data['numdoors']) ? (int)$data['numdoors'] : DOORS_MAX_DOORS;
            if ($required < 1 || $required > $maxdoors) {
                $errors['completionopenedgroup' . $suffix] = get_string('errorcompletionopened', 'mod_doors');
            }
        }

        return $errors;
    }
}
