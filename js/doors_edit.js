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
 * Drag to position doors in the free layout.
 *
 * Plain script, no build step required.
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

window.ModDoorsEdit = (function() {
    'use strict';

    var config = null;
    var container = null;
    var canvas = null;
    var dragging = null;
    var selected = null;
    var offset = {x: 0, y: 0};

    /**
     * Mark a door as the one the size buttons and keys act on.
     *
     * @param {Element} doorEl The door.
     */
    function select(doorEl) {
        if (selected && selected !== doorEl) {
            selected.classList.remove('is-selected');
        }
        selected = doorEl;
        doorEl.classList.add('is-selected');
        // Safari does not focus a button when it is clicked, and preventing the
        // default on pointerdown stops it everywhere, so do it by hand.
        doorEl.focus();
    }

    /**
     * Start dragging a door.
     *
     * @param {Event} e Pointer event.
     */
    function onPointerDown(e) {
        var doorEl = e.target.closest('.doors-door');
        if (!doorEl) {
            return;
        }
        e.preventDefault();
        select(doorEl);
        dragging = doorEl;
        var rect = doorEl.getBoundingClientRect();
        offset.x = e.clientX - rect.left;
        offset.y = e.clientY - rect.top;
        doorEl.classList.add('is-dragging');
        doorEl.setPointerCapture(e.pointerId);
    }

    /**
     * Move the door with the pointer.
     *
     * @param {Event} e Pointer event.
     */
    function onPointerMove(e) {
        if (!dragging) {
            return;
        }
        var bounds = canvas.getBoundingClientRect();
        var x = ((e.clientX - offset.x - bounds.left) / bounds.width) * 100;
        var y = ((e.clientY - offset.y - bounds.top) / bounds.height) * 100;
        x = Math.max(0, Math.min(100, x));
        y = Math.max(0, Math.min(100, y));
        dragging.style.left = x.toFixed(2) + '%';
        dragging.style.top = y.toFixed(2) + '%';
    }

    /**
     * Finish dragging.
     */
    function onPointerUp() {
        if (!dragging) {
            return;
        }
        dragging.classList.remove('is-dragging');
        dragging = null;
        setStatus('');
    }

    /**
     * Nudge a door with the arrow keys for fine positioning.
     *
     * @param {Event} e Keyboard event.
     */
    function onKeyDown(e) {
        var doorEl = e.target.closest ? e.target.closest('.doors-door') : null;
        if (!doorEl) {
            doorEl = selected;
        }
        if (!doorEl) {
            return;
        }
        var step = e.shiftKey ? 0.5 : 2;
        var left = parseFloat(doorEl.style.left) || 0;
        var top = parseFloat(doorEl.style.top) || 0;
        var handled = true;
        switch (e.key) {
            case 'ArrowLeft': left -= step; break;
            case 'ArrowRight': left += step; break;
            case 'ArrowUp': top -= step; break;
            case 'ArrowDown': top += step; break;
            case '+':
            case '=': resize(doorEl, 1); break;
            case '-': resize(doorEl, -1); break;
            default: handled = false;
        }
        if (!handled) {
            return;
        }
        e.preventDefault();
        doorEl.style.left = Math.max(0, Math.min(100, left)).toFixed(2) + '%';
        doorEl.style.top = Math.max(0, Math.min(100, top)).toFixed(2) + '%';
    }

    /**
     * Grow or shrink a door.
     *
     * @param {Element} doorEl The door.
     * @param {Number} direction 1 to grow, -1 to shrink.
     */
    function resize(doorEl, direction) {
        var width = parseFloat(doorEl.style.width) || 12;
        width = Math.max(2, Math.min(60, width + direction));
        doorEl.style.width = width.toFixed(2) + '%';
    }

    /**
     * Show a short status message next to the save button.
     *
     * @param {String} text Message.
     */
    function setStatus(text) {
        var status = document.querySelector('.doors-savepos-status');
        if (status) {
            status.textContent = text;
        }
    }

    /**
     * Persist all door positions.
     */
    function save() {
        var payload = [];
        container.querySelectorAll('.doors-door').forEach(function(doorEl) {
            payload.push({
                id: parseInt(doorEl.getAttribute('data-doorid'), 10),
                x: parseFloat(doorEl.style.left) || 0,
                y: parseFloat(doorEl.style.top) || 0,
                w: parseFloat(doorEl.style.width) || 12
            });
        });

        var params = new URLSearchParams();
        params.append('cmid', config.cmid);
        params.append('sesskey', config.sesskey);
        params.append('positions', JSON.stringify(payload));

        fetch(config.saveurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        }).then(function(r) {
            return r.json();
        }).then(function(data) {
            setStatus(data && data.status === 'ok' ? config.strings.saved : config.strings.error);
        }).catch(function() {
            setStatus(config.strings.error);
        });
    }

    return {
        /**
         * Initialise dragging.
         *
         * @param {Object} cfg Configuration from PHP.
         */
        init: function(cfg) {
            config = cfg;
            container = document.getElementById(cfg.containerid);
            if (!container) {
                return;
            }
            canvas = container.querySelector('.doors-canvas');
            canvas.addEventListener('pointerdown', onPointerDown);
            canvas.addEventListener('pointermove', onPointerMove);
            canvas.addEventListener('pointerup', onPointerUp);
            canvas.addEventListener('pointercancel', onPointerUp);
            canvas.addEventListener('keydown', onKeyDown);

            // Keys can also arrive on the document when nothing is focused.
            document.addEventListener('keydown', function(e) {
                if (!selected) {
                    return;
                }
                if (e.target && e.target.closest && e.target.closest('.doors-door')) {
                    return;
                }
                onKeyDown(e);
            });

            var button = document.getElementById(cfg.buttonid);
            if (button) {
                button.addEventListener('click', save);
            }

            var bigger = document.getElementById(cfg.biggerid);
            if (bigger) {
                bigger.addEventListener('click', function() {
                    if (!selected) {
                        setStatus(config.strings.selectfirst);
                        return;
                    }
                    resize(selected, 1);
                    setStatus('');
                });
            }

            var smaller = document.getElementById(cfg.smallerid);
            if (smaller) {
                smaller.addEventListener('click', function() {
                    if (!selected) {
                        setStatus(config.strings.selectfirst);
                        return;
                    }
                    resize(selected, -1);
                    setStatus('');
                });
            }
        }
    };
})();
