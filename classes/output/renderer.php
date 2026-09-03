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
use plugin_renderer_base;

/**
 * Renderer for mod_doors.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render the calendar.
     *
     * @param \stdClass $doors The instance record.
     * @param array $doorrecords Door records in display order.
     * @param array $opened Map of doorid => timeopened.
     * @param \stdClass $cm The course module.
     * @param \context $context The module context.
     * @param bool $editing Whether this is the drag-to-position editing view.
     * @return string HTML.
     */
    public function calendar($doors, array $doorrecords, array $opened, $cm, $context, $editing = false) {
        $uniqid = 'doors-cal-' . $cm->id . ($editing ? '-edit' : '');

        $backgroundurl = doors_get_image_url($context, 'background');
        $closedurl = doors_get_image_url($context, 'doorclosed');
        $openedurl = doors_get_image_url($context, 'dooropened');

        $bgcolour = doors_effective_colour($doors, 'bgcolour');
        $doorcolour = doors_effective_colour($doors, 'doorcolour');
        $openedcolour = doors_effective_colour($doors, 'openedcolour');
        $textcolour = doors_effective_colour($doors, 'doortextcolour');

        $canvasstyle = [];
        if ($bgcolour) {
            $canvasstyle[] = 'background-color:' . $bgcolour;
        }
        $canvasstyle[] = '--doors-cols:' . max(1, (int)$doors->gridcols);
        $canvasstyle[] = '--doors-gap:' . max(0, (int)($doors->doorgap ?? 10)) . 'px';

        // Fitting the doors to the picture needs the picture's proportions.
        $fitting = false;
        if (($doors->backgroundfit ?? 'cover') === 'fit') {
            $size = doors_get_background_size($context);
            if ($size) {
                $canvasstyle[] = '--doors-bg-ratio:' . $size[0] . '/' . $size[1];
                $fitting = true;
            }
        }
        if ($doorcolour) {
            $canvasstyle[] = '--doors-door-bg:' . $doorcolour;
        }
        if ($openedcolour) {
            $canvasstyle[] = '--doors-door-open-bg:' . $openedcolour;
        }
        if ($textcolour) {
            $canvasstyle[] = '--doors-door-fg:' . $textcolour;
        }

        $openedstyle = !empty($doors->openedstyle) ? $doors->openedstyle : 'flip';

        $classes = [
            'doors-wrapper',
            'doors-layout-' . $doors->layout,
            'doors-shape-' . $doors->doorshape,
            'doors-aspect-' . $doors->aspect,
            'doors-opened-' . $openedstyle,
        ];
        if (!empty($doors->transparentdoors)) {
            $classes[] = 'doors-transparent';
        }
        $classes[] = 'doors-face-' . (!empty($doors->facelayout) ? $doors->facelayout : 'overlay');
        if (!empty($doors->centredoors) && !$fitting) {
            // Fitting the doors to a picture means tiling it exactly, which
            // leaves nothing to centre.
            $classes[] = 'doors-centre';
        }
        if ($fitting) {
            $classes[] = 'doors-fit';
        }
        if ($editing) {
            $classes[] = 'doors-editing';
        }
        if (!$doors->markopened) {
            $classes[] = 'doors-nomark';
        }

        $wrapperstyle = '';
        if (!empty($doors->maxwidth)) {
            $wrapperstyle = 'max-width:' . (int)$doors->maxwidth . 'px';
        }

        $out = '';
        $out .= html_writer::start_div(implode(' ', $classes), [
            'id' => $uniqid,
            'style' => $wrapperstyle,
            'data-cmid' => $cm->id,
            'data-reopen' => $doors->reopen ? 1 : 0,
            'data-editing' => $editing ? 1 : 0,
        ]);

        if (!$editing && $doors->showprogress) {
            $out .= $this->progress($doors, count($doorrecords), count($opened));
        }

        $out .= html_writer::start_div('doors-canvas', ['style' => implode(';', $canvasstyle)]);

        // The picture is its own layer rather than the canvas background, so it
        // can be faded or filtered without taking the doors with it.
        if ($backgroundurl) {
            $bgstyle = 'background-image:url(' . $backgroundurl->out(false) . ')';
            $opacity = isset($doors->bgopacity) ? (int)$doors->bgopacity : 100;
            if ($opacity >= 0 && $opacity < 100) {
                // A custom property rather than opacity itself, so custom CSS
                // can still override it without needing !important.
                $bgstyle .= ';--doors-bg-opacity:' . round($opacity / 100, 2);
            }
            $out .= html_writer::div('', 'doors-bg', ['style' => $bgstyle, 'aria-hidden' => 'true']);
        }

        $colourmap = doors_build_colour_map($doors, $doorrecords);

        foreach ($doorrecords as $door) {
            $out .= $this->door(
                $door,
                $doors,
                $opened,
                $context,
                $closedurl,
                $openedurl,
                $cm,
                $editing,
                $colourmap
            );
        }

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Render a single door.
     *
     * @param \stdClass $door The door record.
     * @param \stdClass $doors The instance record.
     * @param array $opened Map of doorid => timeopened.
     * @param \context $context The module context.
     * @param moodle_url|null $closedurl Default closed image.
     * @param moodle_url|null $openedurl Default opened image.
     * @param \stdClass $cm The course module.
     * @param bool $editing Whether this is the editing view.
     * @param array $colourmap doorid => palette colour, empty when a fixed colour is used.
     * @return string HTML.
     */
    protected function door(
        $door,
        $doors,
        array $opened,
        $context,
        $closedurl,
        $openedurl,
        $cm,
        $editing,
        array $colourmap = []
    ) {
        $isopen = isset($opened[$door->id]);
        $doorimage = doors_get_image_url($context, 'doorimage', $door->id);

        $classes = ['doors-door'];
        if ($isopen && $doors->markopened) {
            $classes[] = 'is-open';
        }

        $style = [];
        if (!empty($door->doorcolour)) {
            $style[] = '--doors-door-bg:' . $door->doorcolour;
        } else if (isset($colourmap[$door->id])) {
            $style[] = '--doors-door-front-bg:' . $colourmap[$door->id];
            $frontfg = doors_contrast_colour($colourmap[$door->id]);
            if ($frontfg !== '') {
                $style[] = '--doors-door-front-fg:' . $frontfg;
            }
        }
        if ($doors->layout === 'free') {
            $style[] = 'left:' . (float)$door->posx . '%';
            $style[] = 'top:' . (float)$door->posy . '%';
            $style[] = 'width:' . (float)$door->poswidth . '%';
        }

        $available = true;
        $reason = '';
        if (!$editing) {
            [$available, $reason] = doors_door_available($door, $doors, $opened, $doors->alldoors, $context);
        }
        if (!$available) {
            $classes[] = 'is-locked';
        }

        $label = $door->doorlabel !== null && $door->doorlabel !== ''
            ? $door->doorlabel
            : $door->doornumber;

        // A word on the door face needs different treatment from a numeral.
        $islabel = ($door->doorlabel !== null && $door->doorlabel !== '');
        $numberclass = 'doors-door-number' . ($islabel ? ' doors-door-label' : '');

        $frontimage = $doorimage ? $doorimage : $closedurl;

        $front = '';
        if ($frontimage) {
            $front .= html_writer::empty_tag('img', [
                'src' => $frontimage->out(false),
                'alt' => '',
                'class' => 'doors-door-bgimg',
                'aria-hidden' => 'true',
            ]);
        }

        // Over an image, the caption sits on a shaded plate so it stays legible
        // whatever the artwork is doing underneath.
        $stacked = (($doors->facelayout ?? 'overlay') === 'stacked');
        $frontcaption = $doors->shownumbers || ($doors->showtitles && !empty($door->title));
        $front .= html_writer::start_div(
            'doors-door-caption' . ($frontimage && $frontcaption && !$stacked ? ' doors-plate' : '')
        );
        if ($doors->shownumbers) {
            $front .= html_writer::span($label, $numberclass);
        }
        if ($doors->showtitles && !empty($door->title)) {
            $front .= html_writer::span(format_string($door->title), 'doors-door-title');
        }
        $front .= html_writer::span('', 'doors-door-tick', ['aria-hidden' => 'true']);
        $front .= html_writer::end_div();
        if (!$available) {
            $front .= html_writer::div(
                $this->pix_icon('t/locked', get_string('locked', 'mod_doors'), 'moodle'),
                'doors-door-lock'
            );
        }

        // With no dedicated opened image, keep the door's own artwork on the
        // back and dim it, rather than dropping to a plain colour.
        $back = '';
        $backimage = $openedurl;
        $reusedart = false;
        if (!$backimage) {
            $backimage = $doorimage ? $doorimage : $closedurl;
            $reusedart = (bool)$backimage;
        }
        if ($backimage) {
            $back .= html_writer::empty_tag('img', [
                'src' => $backimage->out(false),
                'alt' => '',
                'class' => 'doors-door-bgimg' . ($reusedart ? ' doors-dimmed' : ''),
                'aria-hidden' => 'true',
            ]);
        }
        $back .= html_writer::start_div('doors-door-caption' . ($backimage && !$stacked ? ' doors-plate' : ''));
        $back .= html_writer::span($label, $numberclass);
        if (!empty($door->title)) {
            $back .= html_writer::span(format_string($door->title), 'doors-door-title');
        }
        $back .= html_writer::end_div();

        $inner = html_writer::div($front, 'doors-door-face doors-door-front')
            . html_writer::div($back, 'doors-door-face doors-door-back');

        $ariatitle = !empty($door->title) ? format_string($door->title) : '';
        $arialabel = get_string($isopen ? 'doorlabelariaopened' : 'doorlabelaria', 'mod_doors', (object)[
            'number' => $label,
            'title' => $ariatitle,
        ]);

        $attributes = [
            'class' => implode(' ', $classes),
            'style' => implode(';', $style),
            'data-doorid' => $door->id,
            'data-doornumber' => $door->doornumber,
            'data-available' => $available ? 1 : 0,
            'data-reason' => $reason,
            'data-opened' => $isopen ? 1 : 0,
            'aria-label' => $arialabel,
        ];

        if ($editing) {
            $attributes['type'] = 'button';
            $attributes['draggable'] = 'false';
            return html_writer::tag('button', html_writer::div($inner, 'doors-door-inner'), $attributes);
        }

        $attributes['type'] = 'button';

        return html_writer::tag('button', html_writer::div($inner, 'doors-door-inner'), $attributes);
    }

    /**
     * Render the progress indicator.
     *
     * @param \stdClass $doors The instance record.
     * @param int $total Total doors.
     * @param int $done Doors opened.
     * @return string HTML.
     */
    protected function progress($doors, $total, $done) {
        $percent = $total ? round(($done / $total) * 100) : 0;
        $text = get_string('progresscount', 'mod_doors', (object)['done' => $done, 'total' => $total]);

        $bar = html_writer::div('', 'doors-progress-fill', ['style' => 'width:' . $percent . '%']);

        return html_writer::div(
            html_writer::div($text, 'doors-progress-text') .
            html_writer::div($bar, 'doors-progress-bar', [
                'role' => 'progressbar',
                'aria-valuenow' => $done,
                'aria-valuemin' => 0,
                'aria-valuemax' => $total,
                'aria-label' => $text,
            ]),
            'doors-progress'
        );
    }
}
