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
 * Front end behaviour for mod_doors.
 *
 * Deliberately written as a plain (non AMD) script so the plugin needs no
 * build toolchain. It is loaded with $PAGE->requires->js().
 *
 * @package    mod_doors
 * @copyright  2026 Jon Bolton, Simon Lewis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

window.ModDoors = (function() {
    'use strict';

    var config = null;
    var container = null;
    var modal = null;
    var lastFocus = null;

    /**
     * Create the modal shell once and reuse it.
     */
    function buildModal() {
        if (modal) {
            return modal;
        }

        var backdrop = document.createElement('div');
        backdrop.className = 'doors-modal-backdrop';
        backdrop.setAttribute('hidden', 'hidden');

        var dialog = document.createElement('div');
        dialog.className = 'doors-modal';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('tabindex', '-1');

        var header = document.createElement('div');
        header.className = 'doors-modal-header';

        var title = document.createElement('h3');
        title.className = 'doors-modal-title';
        title.id = 'doors-modal-title';
        dialog.setAttribute('aria-labelledby', title.id);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'doors-modal-close';
        close.innerHTML = '&times;';
        close.setAttribute('aria-label', config.strings.close);

        var body = document.createElement('div');
        body.className = 'doors-modal-body';

        header.appendChild(title);
        header.appendChild(close);
        dialog.appendChild(header);
        dialog.appendChild(body);
        backdrop.appendChild(dialog);
        document.body.appendChild(backdrop);

        close.addEventListener('click', hideModal);
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) {
                hideModal();
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !backdrop.hasAttribute('hidden')) {
                hideModal();
            }
        });

        modal = {backdrop: backdrop, dialog: dialog, title: title, body: body};

        return modal;
    }

    /**
     * Show the modal with the given title and HTML body.
     *
     * @param {String} titleText Modal heading.
     * @param {String} html Body markup.
     */
    function showModal(titleText, html) {
        var m = buildModal();
        m.title.textContent = titleText || '';
        m.body.innerHTML = html;
        m.backdrop.removeAttribute('hidden');
        document.body.classList.add('doors-modal-open');
        m.dialog.focus();
        notifyContentUpdated(m.body);
    }

    /**
     * Hide the modal and return focus to the door that opened it.
     */
    function hideModal() {
        if (!modal) {
            return;
        }
        modal.backdrop.setAttribute('hidden', 'hidden');
        modal.body.innerHTML = '';
        document.body.classList.remove('doors-modal-open');
        if (lastFocus) {
            lastFocus.focus();
        }
    }

    /**
     * Let Moodle filters (media players, MathJax) process newly injected content.
     *
     * @param {Element} node The node holding the new content.
     */
    function notifyContentUpdated(node) {
        try {
            require(['core_filters/events'], function(Events) {
                Events.notifyFilterContentUpdated(node);
            }, function() {
                require(['core/event'], function(Event) {
                    Event.notifyFilterContentUpdated(node);
                }, function() {
                    return;
                });
            });
        } catch (e) {
            return;
        }
    }

    /**
     * Show a transient message under the calendar.
     *
     * @param {String} text Message text.
     */
    function flash(text) {
        var note = container.querySelector('.doors-flash');
        if (!note) {
            note = document.createElement('div');
            note.className = 'doors-flash';
            note.setAttribute('role', 'status');
            container.appendChild(note);
        }
        note.textContent = text;
        note.classList.add('is-visible');
        window.clearTimeout(note.timer);
        note.timer = window.setTimeout(function() {
            note.classList.remove('is-visible');
        }, 4000);
    }

    /**
     * Update the progress bar after a door is opened.
     *
     * @param {Number} done Doors opened.
     * @param {Number} total Total doors.
     */
    function updateProgress(done, total) {
        var wrap = container.querySelector('.doors-progress');
        if (!wrap) {
            return;
        }
        var fill = wrap.querySelector('.doors-progress-fill');
        var text = wrap.querySelector('.doors-progress-text');
        var bar = wrap.querySelector('.doors-progress-bar');
        if (fill && total) {
            fill.style.width = Math.round((done / total) * 100) + '%';
        }
        if (text) {
            text.textContent = text.textContent.replace(/\d+/, done);
        }
        if (bar) {
            bar.setAttribute('aria-valuenow', done);
        }
    }

    /**
     * Unlock any doors that became available (sequential mode).
     *
     * @param {Array} unlocked Door ids now available.
     */
    function applyUnlocked(unlocked) {
        if (!unlocked || !unlocked.length) {
            return;
        }
        unlocked.forEach(function(doorid) {
            var el = container.querySelector('.doors-door[data-doorid="' + doorid + '"]');
            if (el) {
                el.classList.remove('is-locked');
                el.setAttribute('data-available', '1');
                el.setAttribute('data-reason', '');
            }
        });
    }

    /**
     * Handle a click on a door.
     *
     * @param {Element} doorEl The door button.
     */
    function openDoor(doorEl) {
        var doorid = doorEl.getAttribute('data-doorid');
        var alreadyOpen = doorEl.getAttribute('data-opened') === '1';
        var reopen = container.getAttribute('data-reopen') === '1';

        if (doorEl.getAttribute('data-available') !== '1') {
            flash(doorEl.getAttribute('data-reason') || config.strings.locked);
            doorEl.classList.add('doors-shake');
            window.setTimeout(function() {
                doorEl.classList.remove('doors-shake');
            }, 600);
            return;
        }

        if (alreadyOpen && !reopen) {
            flash(config.strings.alreadyopened);
            return;
        }

        lastFocus = doorEl;
        doorEl.classList.add('is-loading');

        var params = new URLSearchParams();
        params.append('cmid', config.cmid);
        params.append('doorid', doorid);
        params.append('sesskey', config.sesskey);

        fetch(config.openurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        }).then(function(response) {
            return response.json();
        }).then(function(data) {
            doorEl.classList.remove('is-loading');
            if (!data || data.status !== 'ok') {
                var message = (data && data.message) ? data.message : config.strings.errorloading;
                flash(message);
                return;
            }
            doorEl.classList.add('is-open');
            doorEl.setAttribute('data-opened', '1');
            updateProgress(data.openedcount, data.total);
            applyUnlocked(data.unlocked);
            window.setTimeout(function() {
                showModal(data.title, data.html);
            }, 350);
        }).catch(function() {
            doorEl.classList.remove('is-loading');
            flash(config.strings.errorloading);
        });
    }

    return {
        /**
         * Initialise the calendar.
         *
         * @param {Object} cfg Configuration passed from PHP.
         */
        init: function(cfg) {
            config = cfg;
            container = document.getElementById(cfg.containerid);
            if (!container) {
                return;
            }
            container.addEventListener('click', function(e) {
                var doorEl = e.target.closest('.doors-door');
                if (doorEl && container.contains(doorEl)) {
                    e.preventDefault();
                    openDoor(doorEl);
                }
            });
        }
    };
})();
