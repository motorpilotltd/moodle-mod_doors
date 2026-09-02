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
 * Click a selector in the reference to drop it into the custom CSS box.
 *
 * Plain script, no build step required.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

window.ModDoorsCssRef = (function() {
    'use strict';

    /**
     * Insert a rule into the textarea at the caret and put the caret inside it.
     *
     * @param {Element} textarea The custom CSS textarea.
     * @param {String} snippet The rule to insert.
     */
    function insert(textarea, snippet) {
        var value = textarea.value;
        var start = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : value.length;
        var end = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : value.length;
        var before = value.slice(0, start);
        var after = value.slice(end);

        // Keep rules on their own lines rather than running into what is there.
        if (before.length && !/\n\s*$/.test(before)) {
            snippet = '\n' + snippet;
        }
        if (after.length && !/^\s*\n/.test(after)) {
            snippet = snippet + '\n';
        }

        textarea.value = before + snippet + after;

        // Drop the caret on the blank line between the braces.
        var caret = before.length + snippet.indexOf('{') + 6;
        textarea.focus();
        if (textarea.setSelectionRange) {
            textarea.setSelectionRange(caret, caret);
        }

        textarea.dispatchEvent(new Event('change', {bubbles: true}));
    }

    return {
        /**
         * Wire up the reference.
         *
         * @param {Object} cfg Configuration from PHP.
         */
        init: function(cfg) {
            var textarea = document.getElementById(cfg.textareaid);
            var reference = document.querySelector('.doors-cssref');
            if (!textarea || !reference) {
                return;
            }

            reference.addEventListener('click', function(e) {
                var button = e.target.closest('.doors-cssref-item');
                if (!button) {
                    return;
                }
                e.preventDefault();
                insert(textarea, button.getAttribute('data-doors-css'));
            });
        }
    };
})();
