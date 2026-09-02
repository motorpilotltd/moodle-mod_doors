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
 * Form for editing the contents of a single door.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/doors/locallib.php');

/**
 * Single door editing form.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_doors_door_form extends moodleform {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $door = $this->_customdata['door'];
        $doors = $this->_customdata['doors'];
        $editoroptions = $this->_customdata['editoroptions'];

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'doorid', $door->id);
        $mform->setType('doorid', PARAM_INT);

        $mform->addElement(
            'header',
            'doorheader',
            get_string('editingdoor', 'mod_doors', $door->doornumber)
        );
        $mform->setExpanded('doorheader');

        $mform->addElement('text', 'title', get_string('doortitle', 'mod_doors'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'doortitle', 'mod_doors');

        $mform->addElement('text', 'doorlabel', get_string('doorlabel', 'mod_doors'), ['size' => 12]);
        $mform->setType('doorlabel', PARAM_TEXT);
        $mform->addHelpButton('doorlabel', 'doorlabel', 'mod_doors');

        $mform->addElement(
            'editor',
            'content_editor',
            get_string('doorcontent', 'mod_doors'),
            null,
            $editoroptions
        );
        $mform->setType('content_editor', PARAM_RAW);
        $mform->addHelpButton('content_editor', 'doorcontent', 'mod_doors');

        $mform->addElement('filemanager', 'media', get_string('doormedia', 'mod_doors'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image', 'video', 'audio'],
        ]);
        $mform->addHelpButton('media', 'doormedia', 'mod_doors');

        $mform->addElement('select', 'mediaposition', get_string('mediaposition', 'mod_doors'), [
            'above' => get_string('mediaposition:above', 'mod_doors'),
            'below' => get_string('mediaposition:below', 'mod_doors'),
        ]);
        $mform->setDefault('mediaposition', 'above');
        $mform->addHelpButton('mediaposition', 'mediaposition', 'mod_doors');

        $mform->addElement('header', 'activityheader', get_string('dooractivityheader', 'mod_doors'));

        $mform->addElement(
            'course',
            'linkcourseid',
            get_string('dooractivitycourse', 'mod_doors'),
            ['multiple' => false]
        );
        $mform->setType('linkcourseid', PARAM_INT);
        $mform->setDefault('linkcourseid', $this->_customdata['linkcourseid']);
        $mform->addHelpButton('linkcourseid', 'dooractivitycourse', 'mod_doors');

        $mform->registerNoSubmitButton('reloadactivities');
        $mform->addElement('submit', 'reloadactivities', get_string('dooractivityreload', 'mod_doors'));

        $mform->addElement(
            'select',
            'cmid',
            get_string('dooractivity', 'mod_doors'),
            $this->_customdata['activities']
        );
        $mform->setType('cmid', PARAM_INT);
        $mform->addHelpButton('cmid', 'dooractivity', 'mod_doors');

        $mform->addElement('header', 'linkheader', get_string('doorlinkheader', 'mod_doors'));
        $mform->addElement(
            'static',
            'linkheaderinfo',
            '',
            get_string('doorlinkheaderinfo', 'mod_doors')
        );

        $mform->addElement(
            'url',
            'linkurl',
            get_string('doorlinkurl', 'mod_doors'),
            ['size' => 60],
            ['usefilepicker' => false]
        );
        $mform->setType('linkurl', PARAM_URL);
        $mform->addHelpButton('linkurl', 'doorlinkurl', 'mod_doors');

        $mform->addElement('text', 'linktext', get_string('doorlinktext', 'mod_doors'), ['size' => 40]);
        $mform->setType('linktext', PARAM_TEXT);

        $mform->addElement('select', 'linkmode', get_string('doorlinkmode', 'mod_doors'), [
            'link' => get_string('linkmode:link', 'mod_doors'),
            'embed' => get_string('linkmode:embed', 'mod_doors'),
            'iframe' => get_string('linkmode:iframe', 'mod_doors'),
        ]);
        $mform->addHelpButton('linkmode', 'doorlinkmode', 'mod_doors');

        $mform->addElement('advcheckbox', 'linknewwindow', get_string('doorlinknewwindow', 'mod_doors'));
        $mform->hideIf('linknewwindow', 'linkmode', 'neq', 'link');

        $mform->addElement('header', 'appearanceheader', get_string('appearance', 'mod_doors'));

        $mform->addElement('filemanager', 'doorimage', get_string('doorimage', 'mod_doors'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['web_image'],
        ]);
        $mform->addHelpButton('doorimage', 'doorimage', 'mod_doors');

        $mform->addElement('text', 'doorcolour', get_string('doorcolour', 'mod_doors'), ['size' => 12]);
        $mform->setType('doorcolour', PARAM_TEXT);
        $mform->addHelpButton('doorcolour', 'colourfield', 'mod_doors');

        $mform->addElement(
            'date_time_selector',
            'availablefrom',
            get_string('availablefrom', 'mod_doors'),
            ['optional' => true]
        );
        $mform->addHelpButton('availablefrom', 'availablefrom', 'mod_doors');

        if ($doors->layout === 'free') {
            $mform->addElement('text', 'posx', get_string('posx', 'mod_doors'), ['size' => 6]);
            $mform->setType('posx', PARAM_FLOAT);
            $mform->addElement('text', 'posy', get_string('posy', 'mod_doors'), ['size' => 6]);
            $mform->setType('posy', PARAM_FLOAT);
            $mform->addElement('text', 'poswidth', get_string('poswidth', 'mod_doors'), ['size' => 6]);
            $mform->setType('poswidth', PARAM_FLOAT);
            $mform->addHelpButton('poswidth', 'poswidth', 'mod_doors');
        }

        $this->add_action_buttons(true, get_string('savechanges'));
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

        if (!empty($data['doorcolour']) && doors_clean_colour($data['doorcolour']) === '') {
            $errors['doorcolour'] = get_string('errorcolour', 'mod_doors');
        }

        if (!empty($data['linkmode']) && $data['linkmode'] !== 'link' && empty($data['linkurl'])) {
            $errors['linkurl'] = get_string('errorlinkurlrequired', 'mod_doors');
        }

        // Embedded media only works for URLs one of the site's media players
        // recognises. Say so now rather than quietly showing a button later.
        if (!empty($data['linkurl']) && ($data['linkmode'] ?? '') === 'embed') {
            if (!core_media_manager::instance()->can_embed_url(new moodle_url($data['linkurl']))) {
                $errors['linkmode'] = get_string('errornotembeddable', 'mod_doors');
            }
        }

        return $errors;
    }
}
