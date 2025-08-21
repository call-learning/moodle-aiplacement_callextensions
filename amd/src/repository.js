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
 * Gateway to the webservices.
 *
 * @module     aiplacement_callextensions/placement
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Wizard repository class.
 */
class Repository {

    /**
     * Get action for this wizard (next, previous, finish).
     * @param {Number} contextid The context id.
     * @param {Number} userid The user id.
     * @return {Promise} The promise.
     */
    static getActiveAction(contextid, userid) {
        const request = {
            methodname: 'aiplacement_callextensions_get_active_action',
            args: {
                contextid,
                userid
            }
        };
        return Ajax.call([request])[0]
            .fail(Notification.exception);
    }

    /**
     * Get action for this wizard (next, previous, finish).
     * @param {Number} actionid The action id.
     * @return {Promise} The promise.
     */
    static cancelAction(actionid) {
        const request = {
            methodname: 'aiplacement_callextensions_cancel_action',
            args: {actionid}
        };
        return Ajax.call([request])[0]
            .fail(Notification.exception);
    }

    /**
     * Launch an action via the core_form_dynamic_form web service.
     *
     * @param {Number} actionid The context id.
     * @return {Promise} The promise resolving the server response.
     */
    static actionStatus(actionid) {
        const request = {
            methodname: 'aiplacement_callextensions_get_action_status',
            args: {actionid}
        };
        return Ajax.call([request])[0]
            .fail(Notification.exception);
    }
}

export default Repository;