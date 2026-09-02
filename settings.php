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
 * Site level defaults for mod_doors.
 *
 * Every setting here is only a starting point: each activity can override it.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/doors/locallib.php');

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_doors/defaultsheading',
        get_string('setting:heading', 'mod_doors'),
        get_string('setting:heading_desc', 'mod_doors')
    ));

    $range = [];
    for ($i = 1; $i <= DOORS_MAX_DOORS; $i++) {
        $range[$i] = $i;
    }
    $settings->add(new admin_setting_configselect(
        'mod_doors/numdoors',
        get_string('setting:numdoors', 'mod_doors'),
        get_string('setting:numdoors_desc', 'mod_doors'),
        24,
        $range
    ));

    $settings->add(new admin_setting_configselect(
        'mod_doors/layout',
        get_string('setting:layout', 'mod_doors'),
        get_string('setting:layout_desc', 'mod_doors'),
        'grid',
        doors_layout_options()
    ));

    $settings->add(new admin_setting_configtext(
        'mod_doors/maxwidth',
        get_string('setting:maxwidth', 'mod_doors'),
        get_string('setting:maxwidth_desc', 'mod_doors'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configselect(
        'mod_doors/doorshape',
        get_string('setting:doorshape', 'mod_doors'),
        get_string('setting:doorshape_desc', 'mod_doors'),
        'rounded',
        doors_shape_options()
    ));

    $settings->add(new admin_setting_configselect(
        'mod_doors/openedstyle',
        get_string('setting:openedstyle', 'mod_doors'),
        get_string('setting:openedstyle_desc', 'mod_doors'),
        'flip',
        [
            'flip' => get_string('openedstyle:flip', 'mod_doors'),
            'dim' => get_string('openedstyle:dim', 'mod_doors'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_doors/showactivityicon',
        get_string('setting:showactivityicon', 'mod_doors'),
        get_string('setting:showactivityicon_desc', 'mod_doors'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_doors/buttonstyle',
        get_string('setting:buttonstyle', 'mod_doors'),
        get_string('setting:buttonstyle_desc', 'mod_doors'),
        'theme',
        [
            'theme' => get_string('buttonstyle:theme', 'mod_doors'),
            'calendar' => get_string('buttonstyle:calendar', 'mod_doors'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_doors/colourmode',
        get_string('setting:colourmode', 'mod_doors'),
        get_string('setting:colourmode_desc', 'mod_doors'),
        'fixed',
        [
            'fixed' => get_string('colourmode:fixed', 'mod_doors'),
            'varied' => get_string('colourmode:varied', 'mod_doors'),
        ]
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'mod_doors/doorcolour',
        get_string('setting:doorcolour', 'mod_doors'),
        get_string('setting:doorcolour_desc', 'mod_doors'),
        '#1f5c8b'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'mod_doors/openedcolour',
        get_string('setting:openedcolour', 'mod_doors'),
        get_string('setting:openedcolour_desc', 'mod_doors'),
        '#2e7d4f'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'mod_doors/doortextcolour',
        get_string('setting:doortextcolour', 'mod_doors'),
        get_string('setting:doortextcolour_desc', 'mod_doors'),
        '#ffffff'
    ));

    $settings->add(new admin_setting_configcolourpicker(
        'mod_doors/bgcolour',
        get_string('setting:bgcolour', 'mod_doors'),
        get_string('setting:bgcolour_desc', 'mod_doors'),
        ''
    ));

    // Show which colours this site would fall back to when the box is left empty.
    $detected = doors_core_course_palette();
    $palettedesc = get_string('setting:palette_desc', 'mod_doors');
    $palettedesc .= ' ' . get_string(
        $detected ? 'setting:palettedetected' : 'setting:palettenotdetected',
        'mod_doors',
        implode(', ', $detected ? $detected : doors_default_palette())
    );

    $settings->add(new admin_setting_configtextarea(
        'mod_doors/palette',
        get_string('setting:palette', 'mod_doors'),
        $palettedesc,
        '',
        PARAM_TEXT,
        60,
        3
    ));
}
