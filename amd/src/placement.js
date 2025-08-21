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
 * Module to load and render the form for the CALL Learning placement module.
 *
 * @module     aiplacement_callextensions/placement
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import 'core/copy_to_clipboard';
import Notification from 'core/notification';
import {getString} from 'core/str';
import ModalForm from 'core_form/modalform';
import ModalDeleteCancel from 'core/modal_delete_cancel';
import Repository from "./repository";
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';

const CALLExtensionAssist = class {
    /**
     * The context ID.
     * @type {Integer}
     */
    contextId;

    /**
     * The current action
     * @type {String}
     */
    currentAction;

    /**
     * Progress interval.
     */
    progressInterval;

    /**
     * Constructor.
     * @param {String} actionButtonSelector The selector for the action button.
     */
    constructor(actionButtonSelector) {
        // Get the button by data-id attribute.
        this.actionButton = document.querySelector(actionButtonSelector);
        if (!this.actionButton) {
            return;
        }
        this.contextId = this.actionButton.dataset.contextid ? parseInt(this.actionButton.dataset.contextid, 10) : 0;

        this.registerEventListeners();
    }

    /**
     * Register event listeners.
     */
    registerEventListeners() {
        if (!this.actionButton) {
            return;
        }

        this.actionButton.addEventListener('click', async (event) => {
            event.preventDefault();
            const isActive = await Repository.getActiveAction(this.contextId, 0);
            if (!isActive || isActive.actionid) {
                this.handleActionProgress(isActive.actionid);
                return;
            }
            this.handleLaunchAction();
        });
    }

    /**
     * Handle form navigation for multi-step wizard.
     */
    async handleLaunchAction() {
        const actionName = await getString(`action:${this.actionButton.dataset.actionName}`, 'aiplacement_callextensions');
        const dialogTitle = await getString('actiondialog:title', 'aiplacement_callextensions', actionName);
        const form = new ModalForm({
            modalConfig: {
                title: dialogTitle,
            },
            formClass: 'aiplacement_callextensions\\form\\mod_assist_action_form',
            args: {
                component: this.actionButton.dataset.component,
                cmid: this.actionButton.dataset.cmid,
                actionname: this.actionButton.dataset.actionName,
            },
            saveButtonText: await getString('submit'),
        });

        form.addEventListener(form.events.FORM_SUBMITTED, event => {
            if (!event.detail.result) {
                Notification.addNotification({
                    type: 'error',
                    message: event.detail.message
                });
            } else {
                form.hide();
                // Reload the page to show the new content.
                window.location.reload();
            }
        });
        form.show();
    }

    /**
     * Handle an action already in progress.
     * @param {Number} actionId The action id.
     */
    async handleActionProgress(actionId) {
        // Create a modal to show the progress.
        const actionName = await getString(`action:${this.actionButton.dataset.actionName}`, 'aiplacement_callextensions');
        const modal = await ModalDeleteCancel.create({
            title: getString('actiondialog:status', 'aiplacement_callextensions', actionName),
            large: true,
        });
        const updateBody = (modal, actionId) => {
            return Repository.actionStatus(actionId).then((action) => {
                if (!action || action.status === 3) {
                    modal.hide();
                    modal.destroy(); // Destroy the modal.
                    Notification.addNotification({
                        type: 'failure',
                        message: getString('actiondialog:statuscheckfailure', 'aiplacement_callextensions')
                    });
                    clearInterval(this.progressInterval);
                } else {
                    if (action.status === 1 || action.status === 2) {
                        modal.hide();
                        modal.destroy();
                        Notification.addNotification({
                            type: 'failure',
                            message: getString('actiondialog:statuscheckfailure', 'aiplacement_callextensions')
                        });
                        clearInterval(this.progressInterval);
                    } else {
                        const content = Templates.renderForPromise('aiplacement_callextensions/progress', action);
                        modal.setBodyContent(content);
                    }
                }
            }).catch(Notification.exception);
        };
        modal.getRoot().on(ModalEvents.delete, () => {
            clearInterval(this.progressInterval);
            Repository.cancelAction(actionId).then(() => {
                modal.hide();
                modal.destroy(); // Destroy the modal.
                Notification.addNotification({
                    type: 'success',
                    message: getString('actiondialog:cancelled', 'aiplacement_callextensions')
                });
            }).catch(Notification.exception);
        });
        modal.setDeleteButtonText(await getString('actiondialog:cancel', 'aiplacement_callextensions'));
        // Initial body update.
        await updateBody(modal, actionId);
        const refreshHandler = () =>
            updateBody(modal, actionId).then((shouldContinue) => !shouldContinue && clearInterval(this.progressInterval));
        // Refresh the progress every 5 seconds.
        this.progressInterval = setInterval(refreshHandler, 5000);

        modal.show();
    }
};

export default CALLExtensionAssist;
