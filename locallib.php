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
 * Internal helper functions for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/doors/lib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Get the active doors for an instance, in display order.
 *
 * Doors numbered above the instance's current door count are stored but not
 * shown, so lowering the number of doors hides content rather than losing it.
 *
 * @param stdClass $doors The instance record.
 * @return array Array of door records.
 */
function doors_get_doors($doors) {
    global $DB;

    $records = $DB->get_records_select(
        'doors_door',
        'doorsid = :doorsid AND doornumber <= :numdoors',
        ['doorsid' => $doors->id, 'numdoors' => (int)$doors->numdoors],
        'doornumber ASC'
    );

    if (!empty($doors->randomise)) {
        // Stable shuffle: same order every time for the same calendar, but not 1..n.
        $keys = array_keys($records);
        $seeded = [];
        foreach ($keys as $key) {
            $seeded[$key] = crc32($doors->id . '-' . $records[$key]->doornumber);
        }
        asort($seeded);
        $ordered = [];
        foreach (array_keys($seeded) as $key) {
            $ordered[$key] = $records[$key];
        }
        return $ordered;
    }

    return $records;
}

/**
 * Get the doors that are stored but currently hidden by the door count.
 *
 * @param stdClass $doors The instance record.
 * @return array Array of door records.
 */
function doors_get_hidden_doors($doors) {
    global $DB;

    return $DB->get_records_select(
        'doors_door',
        'doorsid = :doorsid AND doornumber > :numdoors',
        ['doorsid' => $doors->id, 'numdoors' => (int)$doors->numdoors],
        'doornumber ASC'
    );
}

/**
 * Get the set of active door ids the user has already opened.
 *
 * @param stdClass $doors The instance record.
 * @param int $userid User id.
 * @return array doorid => timeopened
 */
function doors_get_opened($doors, $userid) {
    global $DB;

    $sql = "SELECT o.doorid, o.timeopened
              FROM {doors_opened} o
              JOIN {doors_door} dd ON dd.id = o.doorid
             WHERE o.doorsid = :doorsid
               AND o.userid = :userid
               AND dd.doornumber <= :numdoors";

    $records = $DB->get_records_sql($sql, [
        'doorsid' => $doors->id,
        'userid' => $userid,
        'numdoors' => (int)$doors->numdoors,
    ]);

    $opened = [];
    foreach ($records as $record) {
        $opened[$record->doorid] = $record->timeopened;
    }

    return $opened;
}

/**
 * Work out whether a door can be opened by this user right now.
 *
 * @param stdClass $door The door record.
 * @param stdClass $doors The instance record.
 * @param array $opened Map of doorid => timeopened for the user.
 * @param array $alldoors All door records for the instance (numbered order not required).
 * @param context $context Module context.
 * @return array [bool available, string reason] Reason is a translated string or ''.
 */
function doors_door_available($door, $doors, array $opened, array $alldoors, $context) {
    if (has_capability('mod/doors:ignoreavailability', $context)) {
        return [true, ''];
    }

    if (!empty($door->availablefrom) && $door->availablefrom > time()) {
        return [false, get_string('lockeduntil', 'mod_doors', userdate($door->availablefrom))];
    }

    if ($doors->openmode === 'sequential' && $door->doornumber > 1) {
        foreach ($alldoors as $other) {
            if ($other->doornumber == $door->doornumber - 1) {
                if (!isset($opened[$other->id])) {
                    return [false, get_string('lockedsequential', 'mod_doors', $other->doornumber)];
                }
                break;
            }
        }
    }

    return [true, ''];
}

/**
 * Get a URL for the single file stored in one of this module's image areas.
 *
 * @param context $context Module context.
 * @param string $filearea File area name.
 * @param int $itemid Item id.
 * @return moodle_url|null
 */
function doors_get_image_url($context, $filearea, $itemid = 0) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_doors', $filearea, $itemid, 'filename', false);
    if (!$files) {
        return null;
    }
    $file = reset($files);

    return moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );
}

/**
 * The pixel dimensions of the background image, if there is one.
 *
 * @param context $context Module context.
 * @return array|null [width, height], or null when there is no usable image.
 */
function doors_get_background_size($context) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_doors', 'background', 0, 'filename', false);
    if (!$files) {
        return null;
    }

    $file = reset($files);
    $info = $file->get_imageinfo();
    if (empty($info['width']) || empty($info['height'])) {
        return null;
    }

    return [(int)$info['width'], (int)$info['height']];
}

/**
 * Build the HTML shown inside a door once it has been opened.
 *
 * @param stdClass $door The door record.
 * @param stdClass $doors The instance record.
 * @param stdClass $cm The course module.
 * @param context $context Module context.
 * @return string HTML.
 */
function doors_render_door_content($door, $doors, $cm, $context) {
    global $CFG;

    require_once($CFG->libdir . '/filelib.php');

    $out = '';
    $media = '';
    $mediamanager = core_media_manager::instance();

    // Uploaded media (image, video or audio).
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_doors', 'media', $door->id, 'filename', false);
    if ($files) {
        $file = reset($files);
        $url = moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        $mimetype = $file->get_mimetype();
        if (strpos($mimetype, 'image/') === 0) {
            $media .= html_writer::div(
                html_writer::empty_tag('img', [
                    'src' => $url->out(),
                    'alt' => s($door->title ?? ''),
                    'class' => 'doors-media-image',
                ]),
                'doors-media'
            );
        } else if ($mediamanager->can_embed_url($url)) {
            $media .= html_writer::div($mediamanager->embed_url($url, $file->get_filename()), 'doors-media');
        } else {
            $media .= html_writer::div(
                html_writer::link($url, $file->get_filename(), ['class' => 'btn btn-secondary']),
                'doors-media'
            );
        }
    }

    // The file sits above or below the text, as the door was set up.
    $mediabelow = (($door->mediaposition ?? 'above') === 'below');
    if (!$mediabelow) {
        $out .= $media;
    }

    // Rich text content.
    if (!empty($door->content)) {
        $content = file_rewrite_pluginfile_urls(
            $door->content,
            'pluginfile.php',
            $context->id,
            'mod_doors',
            'content',
            $door->id
        );
        $formatoptions = (object)['noclean' => true, 'overflowdiv' => true, 'context' => $context];
        $out .= html_writer::div(
            format_text($content, $door->contentformat, $formatoptions),
            'doors-content'
        );
    }

    if ($mediabelow) {
        $out .= $media;
    }

    [$buttonclass, $buttonstyle] = doors_button_attributes($doors);

    // Link to another activity on this site.
    if (!empty($door->cmid)) {
        $targetcm = doors_get_target_cm($door->cmid);
        if ($targetcm && $targetcm->uservisible && $targetcm->url) {
            $showicon = !isset($doors->showactivityicon) || !empty($doors->showactivityicon);

            $label = '';
            if ($showicon) {
                $label .= html_writer::empty_tag('img', [
                    'src' => $targetcm->get_icon_url()->out(false),
                    'alt' => '',
                    'class' => 'doors-activity-icon',
                    'aria-hidden' => 'true',
                ]);
            }
            $text = html_writer::span(format_string($targetcm->get_formatted_name()), 'doors-activity-name');

            if ($targetcm->course != $cm->course) {
                $coursename = format_string(get_course($targetcm->course)->fullname);
                $text .= html_writer::span(
                    get_string('dooractivityincourse', 'mod_doors', $coursename),
                    'doors-activity-course'
                );
            }

            $label .= html_writer::div($text, 'doors-activity-text');

            $out .= html_writer::div(
                html_writer::link($targetcm->url, $label, [
                    'class' => $buttonclass . ' doors-activity-link',
                    'style' => $buttonstyle,
                ]),
                'doors-activitywrap'
            );
        }
    }

    // Link or embedded external resource.
    if (!empty($door->linkurl)) {
        $linkurl = new moodle_url($door->linkurl);
        if ($door->linkmode === 'embed' && $mediamanager->can_embed_url($linkurl)) {
            $out .= html_writer::div(
                $mediamanager->embed_url($linkurl, $door->linktext ?: $door->title),
                'doors-embed'
            );
        } else if ($door->linkmode === 'iframe') {
            // Many sites refuse to be framed, and the browser gives us no
            // reliable way to detect that, so always offer a way out.
            $out .= html_writer::div(
                html_writer::tag('iframe', '', [
                    'src' => $linkurl->out(false),
                    'class' => 'doors-iframe',
                    'allowfullscreen' => 'allowfullscreen',
                    'referrerpolicy' => 'no-referrer-when-downgrade',
                    'title' => s($door->linktext ?: ($door->title ?? '')),
                ]) .
                html_writer::div(
                    html_writer::link($linkurl, get_string('iframefallback', 'mod_doors'), [
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                        'class' => 'doors-iframe-fallback',
                    ]),
                    'doors-iframe-note'
                ),
                'doors-embed'
            );
        } else {
            $attributes = ['class' => $buttonclass . ' doors-link', 'style' => $buttonstyle];
            if (!empty($door->linknewwindow)) {
                $attributes['target'] = '_blank';
                $attributes['rel'] = 'noopener noreferrer';
            }
            $out .= html_writer::div(
                html_writer::link($linkurl, $door->linktext ?: get_string('openlink', 'mod_doors'), $attributes),
                'doors-linkwrap'
            );
        }
    }

    if (trim($out) === '') {
        $out = html_writer::div(get_string('nocontentyet', 'mod_doors'), 'doors-empty text-muted');
    }

    return $out;
}

/**
 * Load the course module a door points at, if it still exists.
 *
 * @param int $cmid Course module id.
 * @return cm_info|null
 */
function doors_get_target_cm($cmid) {
    global $DB;

    if (empty($cmid)) {
        return null;
    }

    $record = $DB->get_record('course_modules', ['id' => $cmid], 'id, course', IGNORE_MISSING);
    if (!$record) {
        return null;
    }

    try {
        $modinfo = get_fast_modinfo($record->course);
        return $modinfo->get_cm($cmid);
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * The activities in a course that a door could link to.
 *
 * Only activities the current user can see and that have a page of their own
 * are offered, so labels and hidden activities stay out of the list.
 *
 * @param int $courseid The course to list.
 * @param int $excludecmid A course module to leave out, normally the calendar itself.
 * @return array cmid => label, always including a "none" entry.
 */
function doors_activity_options($courseid, $excludecmid = 0) {
    global $CFG;

    require_once($CFG->dirroot . '/course/lib.php');

    $options = [0 => get_string('dooractivitynone', 'mod_doors')];

    if (empty($courseid)) {
        return $options;
    }

    try {
        $course = get_course($courseid);
    } catch (\Throwable $e) {
        return $options;
    }

    if (!can_access_course($course)) {
        return $options;
    }

    $modinfo = get_fast_modinfo($course);
    $sectionnames = [];
    foreach ($modinfo->get_section_info_all() as $section) {
        $sectionnames[$section->section] = get_section_name($course, $section);
    }

    foreach ($modinfo->cms as $cm) {
        if ($cm->id == $excludecmid || !$cm->has_view() || !$cm->uservisible) {
            continue;
        }
        $section = $sectionnames[$cm->sectionnum] ?? '';
        $options[$cm->id] = ($section !== '' ? $section . ': ' : '') . format_string($cm->name);
    }

    return $options;
}

/**
 * Work out how the buttons inside a door should be styled.
 *
 * With the theme setting the buttons follow the site's primary colour, which is
 * Bootstrap's job. With the calendar setting they take the door colour, so a
 * campaign branded calendar is branded all the way through.
 *
 * @param stdClass $doors The instance record.
 * @return array [string classes, string inline style]
 */
function doors_button_attributes($doors) {
    if (($doors->buttonstyle ?? 'theme') !== 'calendar') {
        return ['btn btn-primary', ''];
    }

    // Prefer the door colour, then the first palette colour, then the opened colour.
    $colour = doors_effective_colour($doors, 'doorcolour');
    if ($colour === '' && ($doors->colourmode ?? 'fixed') === 'varied') {
        $palette = doors_get_palette($doors);
        $colour = reset($palette);
    }
    if ($colour === '') {
        $colour = doors_effective_colour($doors, 'openedcolour');
    }
    if ($colour === '') {
        // Nothing to work with, so leave it to the theme.
        return ['btn btn-primary', ''];
    }

    $text = doors_contrast_colour($colour);
    $classes = 'btn doors-btn';
    if ($text === '#1d2125') {
        // Dark text means a light button, so the icon must go dark too.
        $classes .= ' doors-btn-light';
    }

    $style = '--doors-btn-bg:' . $colour;
    if ($text !== '') {
        $style .= ';--doors-btn-fg:' . $text;
    }

    return [$classes, $style];
}

/**
 * Record that a user has opened a door and update completion.
 *
 * @param stdClass $door The door record.
 * @param stdClass $doors The instance record.
 * @param stdClass $cm The course module.
 * @param stdClass $course The course.
 * @param context $context Module context.
 * @return void
 */
function doors_mark_opened($door, $doors, $cm, $course, $context) {
    global $DB, $USER;

    if (isguestuser() || !isloggedin()) {
        return;
    }

    if (!$DB->record_exists('doors_opened', ['doorid' => $door->id, 'userid' => $USER->id])) {
        $record = new stdClass();
        $record->doorsid = $doors->id;
        $record->doorid = $door->id;
        $record->userid = $USER->id;
        $record->timeopened = time();
        $DB->insert_record('doors_opened', $record);
    }

    $event = \mod_doors\event\door_opened::create([
        'context' => $context,
        'objectid' => $door->id,
        'other' => ['doornumber' => $door->doornumber, 'doorsid' => $doors->id],
    ]);
    $event->trigger();

    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && $doors->completionopened > 0) {
        $completion->update_state($cm, COMPLETION_UNKNOWN);
    }
}

/**
 * Count how many active doors a user has opened.
 *
 * @param stdClass $doors The instance record.
 * @param int $userid User id.
 * @return int
 */
function doors_count_opened($doors, $userid) {
    global $DB;

    $sql = "SELECT COUNT(o.id)
              FROM {doors_opened} o
              JOIN {doors_door} dd ON dd.id = o.doorid
             WHERE o.doorsid = :doorsid
               AND o.userid = :userid
               AND dd.doornumber <= :numdoors";

    return (int)$DB->count_records_sql($sql, [
        'doorsid' => $doors->id,
        'userid' => $userid,
        'numdoors' => (int)$doors->numdoors,
    ]);
}

/**
 * Return the list of selectable layouts.
 *
 * @return array
 */
function doors_layout_options() {
    return [
        'grid' => get_string('layout:grid', 'mod_doors'),
        'free' => get_string('layout:free', 'mod_doors'),
    ];
}

/**
 * Return the list of selectable door shapes.
 *
 * @return array
 */
function doors_shape_options() {
    return [
        'square' => get_string('shape:square', 'mod_doors'),
        'rounded' => get_string('shape:rounded', 'mod_doors'),
        'circle' => get_string('shape:circle', 'mod_doors'),
    ];
}

/**
 * Return the list of selectable door aspect ratios.
 *
 * @return array
 */
function doors_aspect_options() {
    return [
        'square' => get_string('aspect:square', 'mod_doors'),
        'portrait' => get_string('aspect:portrait', 'mod_doors'),
        'landscape' => get_string('aspect:landscape', 'mod_doors'),
    ];
}

/**
 * The palette Moodle itself uses for course cards, read from the site.
 *
 * Site settings are checked first, then core's own colour generator, so
 * whatever this Moodle actually gives its course cards is what we use. Returns
 * an empty array when neither is available.
 *
 * @return array
 */
function doors_core_course_palette() {
    global $CFG, $OUTPUT;

    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    // Sites that expose the course card colours as admin settings.
    $palette = [];
    for ($i = 1; $i <= 10; $i++) {
        $name = 'coursecolor' . $i;
        if (!empty($CFG->$name)) {
            $colour = doors_clean_colour($CFG->$name);
            if ($colour !== '') {
                $palette[] = $colour;
            }
        }
    }
    if (count($palette) > 1) {
        $cached = $palette;
        return $cached;
    }

    // Otherwise ask core's colour generator for the colours it would hand out.
    $palette = [];
    if (is_object($OUTPUT) && method_exists($OUTPUT, 'get_generated_color_for_id')) {
        try {
            for ($i = 1; $i <= 10; $i++) {
                $colour = doors_clean_colour($OUTPUT->get_generated_color_for_id($i));
                if ($colour !== '' && !in_array($colour, $palette, true)) {
                    $palette[] = $colour;
                }
            }
        } catch (\Throwable $e) {
            $palette = [];
        }
    }

    $cached = count($palette) > 1 ? $palette : [];

    return $cached;
}

/**
 * The palette of last resort, used when the site offers nothing to read.
 *
 * @return array
 */
function doors_default_palette() {
    return [
        '#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894',
        '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7',
    ];
}

/**
 * Get the palette in use for an instance.
 *
 * Activity list, then the plugin's site wide list, then the site's own course
 * card colours, then the built in list.
 *
 * @param stdClass $doors The instance record.
 * @return array List of colour values, never empty.
 */
function doors_get_palette($doors) {
    // The activity's own list wins.
    $palette = doors_parse_palette($doors->palette ?? '');
    if ($palette) {
        return $palette;
    }

    // Then the site wide list for this plugin.
    $palette = doors_parse_palette(get_config('mod_doors', 'palette'));
    if ($palette) {
        return $palette;
    }

    // Then whatever colours this Moodle gives its course cards.
    $palette = doors_core_course_palette();
    if ($palette) {
        return $palette;
    }

    return doors_default_palette();
}

/**
 * Turn a author supplied palette string into a list of valid colours.
 *
 * @param string $raw Comma, space or newline separated colours.
 * @return array
 */
function doors_parse_palette($raw) {
    $palette = [];
    foreach (preg_split('/[\s,]+/', (string)$raw) as $candidate) {
        $colour = doors_clean_colour($candidate);
        if ($colour !== '') {
            $palette[] = $colour;
        }
    }

    return $palette;
}

/**
 * Assign a palette colour to each door, avoiding two neighbours matching.
 *
 * The choice is derived from the instance and door number, so it looks random
 * but stays the same on every page load.
 *
 * @param stdClass $doors The instance record.
 * @param array $doorrecords Door records in display order.
 * @return array doorid => colour
 */
function doors_build_colour_map($doors, array $doorrecords) {
    $map = [];

    if (($doors->colourmode ?? 'fixed') !== 'varied') {
        return $map;
    }

    if (!empty($doors->transparentdoors)) {
        // Nothing is painted behind the text, so a per door colour would only
        // scramble the contrast calculation that goes with it.
        return $map;
    }

    $palette = doors_get_palette($doors);
    $count = count($palette);
    $previous = null;

    foreach ($doorrecords as $door) {
        $index = abs(crc32($doors->id . '-' . $door->doornumber)) % $count;
        if ($count > 1 && $palette[$index] === $previous) {
            $index = ($index + 1) % $count;
        }
        $previous = $palette[$index];
        $map[$door->id] = $palette[$index];
    }

    return $map;
}

/**
 * Pick black or white text for a background colour.
 *
 * @param string $colour A hex colour.
 * @return string A hex colour, or an empty string if the input is not hex.
 */
function doors_contrast_colour($colour) {
    if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', (string)$colour)) {
        return '';
    }

    $hex = ltrim($colour, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    $red = hexdec(substr($hex, 0, 2));
    $green = hexdec(substr($hex, 2, 2));
    $blue = hexdec(substr($hex, 4, 2));

    // Perceived brightness, the usual W3C style weighting.
    $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

    return $brightness > 140 ? '#1d2125' : '#ffffff';
}

/**
 * The colour to use for one of the instance colour fields.
 *
 * An activity's own value wins. Where it is empty the site default set in
 * Site administration is used, and where that is empty too the stylesheet
 * default applies.
 *
 * @param stdClass $doors The instance record.
 * @param string $field One of bgcolour, doorcolour, openedcolour, doortextcolour.
 * @return string A colour value, or an empty string.
 */
function doors_effective_colour($doors, $field) {
    if (!empty($doors->$field)) {
        return $doors->$field;
    }

    return doors_clean_colour(get_config('mod_doors', $field));
}

/**
 * The selector reference shown under the custom CSS box.
 *
 * Each group has a title, a flag saying whether its selectors sit inside the
 * activity wrapper, and a list of item key => selector. Descriptions come from
 * the language pack as cssref:<key>.
 *
 * @return array
 */
function doors_css_reference() {
    return [
        'properties' => [
            'title' => get_string('cssref:properties', 'mod_doors'),
            'scoped' => true,
            'declaration' => true,
            'items' => [
                'doorbg' => '--doors-door-bg',
                'bgopacity' => '--doors-bg-opacity',
                'doorfg' => '--doors-door-fg',
                'dooropenbg' => '--doors-door-open-bg',
                'frontbg' => '--doors-door-front-bg',
                'frontfg' => '--doors-door-front-fg',
                'gap' => '--doors-gap',
                'cols' => '--doors-cols',
            ],
        ],
        'structure' => [
            'title' => get_string('cssref:structure', 'mod_doors'),
            'scoped' => true,
            'declaration' => false,
            'items' => [
                'wrapper' => '.doors-wrapper',
                'canvas' => '.doors-canvas',
                'bg' => '.doors-bg',
                'layoutfree' => '.doors-layout-free',
                'transparent' => '.doors-transparent',
                'facestacked' => '.doors-face-stacked',
                'centre' => '.doors-centre',
                'openeddim' => '.doors-opened-dim',
                'progress' => '.doors-progress',
                'progressbar' => '.doors-progress-bar',
                'progressfill' => '.doors-progress-fill',
                'flash' => '.doors-flash',
            ],
        ],
        'door' => [
            'title' => get_string('cssref:door', 'mod_doors'),
            'scoped' => true,
            'declaration' => false,
            'items' => [
                'doorbutton' => '.doors-door',
                'dooropen' => '.doors-door.is-open',
                'doorlocked' => '.doors-door.is-locked',
                'inner' => '.doors-door-inner',
                'face' => '.doors-door-face',
                'front' => '.doors-door-front',
                'back' => '.doors-door-back',
                'bgimg' => '.doors-door-bgimg',
                'dimmed' => '.doors-door-bgimg.doors-dimmed',
                'caption' => '.doors-door-caption',
                'plate' => '.doors-door-caption.doors-plate',
                'number' => '.doors-door-number',
                'label' => '.doors-door-number.doors-door-label',
                'doortitle' => '.doors-door-title',
                'lock' => '.doors-door-lock',
                'tick' => '.doors-door-tick',
            ],
        ],
        'content' => [
            'title' => get_string('cssref:content', 'mod_doors'),
            'scoped' => false,
            'declaration' => false,
            'items' => [
                'media' => '.doors-media',
                'mediaimage' => '.doors-media-image',
                'richtext' => '.doors-content',
                'activitylink' => '.doors-activity-link',
                'activityicon' => '.doors-activity-icon',
                'activitytext' => '.doors-activity-text',
                'btn' => '.doors-btn',
                'link' => '.doors-link',
                'embed' => '.doors-embed',
                'iframe' => '.doors-iframe',
                'iframenote' => '.doors-iframe-note',
                'emptydoor' => '.doors-empty',
            ],
        ],
        'modal' => [
            'title' => get_string('cssref:modal', 'mod_doors'),
            'scoped' => false,
            'declaration' => false,
            'items' => [
                'backdrop' => '.doors-modal-backdrop',
                'modalpanel' => '.doors-modal',
                'modalheader' => '.doors-modal-header',
                'modaltitle' => '.doors-modal-title',
                'modalclose' => '.doors-modal-close',
                'modalbody' => '.doors-modal-body',
            ],
        ],
    ];
}

/**
 * Build the markup for the selector reference under the custom CSS box.
 *
 * @param int $cmid The course module id, or 0 for an activity being created.
 * @return string HTML.
 */
function doors_css_reference_html($cmid = 0) {
    $prefix = $cmid ? '#doors-cal-' . $cmid . ' ' : '';

    $out = html_writer::start_tag('details', ['class' => 'doors-cssref']);
    $out .= html_writer::tag('summary', get_string('cssrefopen', 'mod_doors'));
    $out .= html_writer::div(
        get_string($cmid ? 'cssrefintro' : 'cssrefintronew', 'mod_doors'),
        'doors-cssref-intro'
    );

    foreach (doors_css_reference() as $group) {
        $out .= html_writer::tag('h6', $group['title'], ['class' => 'doors-cssref-title']);
        if (!$group['scoped']) {
            $out .= html_writer::div(get_string('cssrefunscoped', 'mod_doors'), 'doors-cssref-note');
        }
        $out .= html_writer::start_tag('ul', ['class' => 'doors-cssref-list']);

        foreach ($group['items'] as $key => $selector) {
            if (!empty($group['declaration'])) {
                $snippet = $prefix . ".doors-canvas {\n    " . $selector . ": ;\n}\n";
            } else {
                $snippet = ($group['scoped'] ? $prefix : '') . $selector . " {\n    \n}\n";
            }

            $out .= html_writer::tag(
                'li',
                html_writer::tag('button', s($selector), [
                    'type' => 'button',
                    'class' => 'doors-cssref-item',
                    'data-doors-css' => $snippet,
                    'title' => get_string('cssrefinsert', 'mod_doors'),
                ]) .
                html_writer::span(get_string('cssref:' . $key, 'mod_doors'), 'doors-cssref-desc')
            );
        }

        $out .= html_writer::end_tag('ul');
    }

    $out .= html_writer::end_tag('details');

    return $out;
}

/**
 * Sanitise a colour value coming from a form.
 *
 * @param string|null $value Raw value.
 * @return string Empty string if not a valid colour.
 */
function doors_clean_colour($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
        return $value;
    }
    if (preg_match('/^[a-zA-Z]{3,20}$/', $value)) {
        return $value;
    }

    return '';
}
