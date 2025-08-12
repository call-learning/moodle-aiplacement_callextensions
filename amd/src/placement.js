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
import FormWizard from 'aiplacement_callextensions/form_wizard';
import {getString} from 'core/str';


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
     * The current action data
     * @type {String}
     */
    currentActionData;

    /**
     * The current generated content data
     * @type {String}
     */
    currentGeneratedContent;

    /**
     * Constructor.
     * @param {String} actionButtonSelector The selector for the action button.
     */
    constructor(actionButtonSelector ) {
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
            this.handleFormNavigation(1);
        });
    }

    /**
     * Handle form navigation for multi-step wizard.
     * @param {number} step The step to navigate to. Defaults to 1.
     */
    handleFormNavigation(step) {
        const wizard = new FormWizard({
            modalConfig: {
                title: getString('wizardtitle', 'aiplacement_callextensions'),
            },
            formClass: 'aiplacement_callextensions\\form\\mod_assist_action_form',
            args: {
                action: this.actionButton.dataset.aiAction,
                component: this.actionButton.dataset.component,
                cmid: this.actionButton.dataset.cmid,
                step: step,
            },
            saveButtonText: getString('next', 'aiplacement_callextensions'),
        });

        wizard.addEventListener(wizard.events.FORM_SUBMITTED, event => {
            if (!event.detail.result) {
                Notification.addNotification({
                    type: 'error',
                    message: event.detail.errors.join('<br>')
                });
            } else {
                // Reload the form with updated step data.
                this.handleFormNavigation(event.detail.actiondata.step);
            }
        });

        wizard.show();
    }

    /**
     * Check if the AI drawer is open.
     * @return {boolean} True if the AI drawer is open, false otherwise.
     */
    isAIDrawerOpen() {
        return this.aiDrawerElement.classList.contains('show');
    }

    /**
     * Open the AI drawer.
     */
    openAIDrawer() {
        this.aiDrawerElement.classList.add('show');
    }

    /**
     * Close the AI drawer.
     */
    closeAIDrawer() {
        this.aiDrawerElement.classList.remove('show');
    }

    /**
     * Toggle the AI drawer.
     */
    toggleAIDrawer() {
        if (this.isAIDrawerOpen()) {
            this.closeAIDrawer();
        } else {
            this.openAIDrawer();
        }
    }

    /**
     * Clear actions.
     */
    clearActions() {
        this.aiDrawerBodyElement.dataset.currentAction = '';
        this.aiDrawerBodyElement.dataset.currentActionData = '';
    }
};

export default CALLExtensionAssist;
