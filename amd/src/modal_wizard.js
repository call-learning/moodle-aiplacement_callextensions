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
 * Wizard form for the CALL Learning placement module.
 *
 * @module     aiplacement_callextensions/wizardform
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import Notification from 'core/notification';
import * as CustomEvents from 'core/custom_interaction_events';
import $ from 'jquery';

/**
 * ModalWizard class
 */
export default class ModalWizard extends Modal {
    static TYPE = 'WIZARD';
    static TEMPLATE = 'aiplacement_callextensions/modal_wizard';
    static EVENTS = {
        next: 'modal-wizard:next',
        previous: 'modal-wizard:previous',
    };
    /**
     * Constructor
     *
     * Shows the required form inside a modal dialogue
     *
     * @param {HTMLElement} root The root element of the modal.
     */
    constructor(root) {
        super(root);
        this.removeOnClose = true; // Default to removing the modal on close.
        if (!this.getFooter().find(this.getActionSelector('cancel')).length) {
            Notification.exception({message: 'No cancel button found'});
        }
    }

    /**
     * Register all event listeners.
     */
    registerEventListeners() {
        // Call the parent registration.
        super.registerEventListeners();

        // Register to close on save/cancel.
        this.registerCloseOnSave();
        this.registerCloseOnCancel();
        this.registerCloseOnNext();
        this.registerCloseOnPrevious();
    }

    /**
     * Register a listener to close the dialogue when the save button is pressed.
     *
     * @method registerCloseOnNext
     */
    registerCloseOnNext() {
        // Handle the clicking of the Cancel button.
        this.getModal().on(CustomEvents.events.activate, this.getActionSelector('next'), (e, data) => {
            const nextEvent = $.Event(ModalWizard.EVENTS.next);
            this.getRoot().trigger(nextEvent, this);

            if (!nextEvent.isDefaultPrevented()) {
                data.originalEvent.preventDefault();

                if (this.removeOnClose) {
                    this.destroy();
                } else {
                    this.hide();
                }
            }
        });
    }

    /**
     * Register a listener to close the dialogue when the save button is pressed.
     *
     * @method registerCloseOnPrevious
     */
    registerCloseOnPrevious() {
        // Handle the clicking of the Cancel button.
        this.getModal().on(CustomEvents.events.activate, this.getActionSelector('previous'), (e, data) => {
            const previousEvent = $.Event(ModalWizard.EVENTS.previous);
            this.getRoot().trigger(previousEvent, this);

            if (!previousEvent.isDefaultPrevented()) {
                data.originalEvent.preventDefault();

                if (this.removeOnClose) {
                    this.destroy();
                } else {
                    this.hide();
                }
            }
        });
    }

}

ModalWizard.registerModalType();
