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

import ModalForm from 'core_form/modalform';
import ModalWizard from "./modal_wizard";
import * as FormChangeChecker from 'core_form/changechecker';
import Fragment from 'core/fragment';
import Ajax from 'core/ajax';

export default class FormWizard extends ModalForm {
    /**
     * Constructor
     *
     * Shows the required form inside a modal dialogue
     *
     * @param {Object} config parameters for the form and modal dialogue:
     * @paramy {String} config.formClass PHP class name that handles the form (should extend \core_form\modal )
     * @paramy {String} config.moduleName module name to use if different to core/modal_save_cancel (optional)
     * @paramy {Object} config.modalConfig modal config - title, header, footer, etc.
     *              Default: {removeOnClose: true, large: true}
     * @paramy {Object} config.args Arguments for the initial form rendering (for example, id of the edited entity)
     * @paramy {String} config.saveButtonText the text to display on the Modal "Save" button (optional)
     * @paramy {String} config.saveButtonClasses additional CSS classes for the Modal "Save" button
     * @paramy {HTMLElement} config.returnFocus element to return focus to after the dialogue is closed
     */
    constructor(config) {
        config.moduleName = config.moduleName || 'aiplacement_callextensions/modal_wizard';
        config.args.step = config.args.step || 1; // Default to step 1 if not provided.
        super(config);
    }
    show() {
        // Show the modal.
        return super.show().then(() => {
            // When Next is clicked, we trigger the save then move to the next step.
            this.modal.getRoot().on(ModalWizard.EVENTS.next, (e) => {
                e.preventDefault();
                const event = this.trigger(this.events.SUBMIT_BUTTON_PRESSED);
                if (!event.defaultPrevented) {
                    this.submitFormAjax(ModalWizard.EVENTS.next);
                }
            });

            // When Previous is clicked, we trigger the save then move to the previous step.
            this.modal.getRoot().on(ModalWizard.EVENTS.previous, (e) => {
                e.preventDefault();
                const event = this.trigger(this.events.SUBMIT_BUTTON_PRESSED);
                if (!event.defaultPrevented) {
                    this.submitFormAjax(ModalWizard.EVENTS.previous);
                }
            });
        });
    }

    /**
     * Submit the form via AJAX call to the core_form_dynamic_form WS
     * @param {String} eventType The type of event to trigger when the form is submitted.
     */
    async submitFormAjax(eventType = this.events.SUBMIT_BUTTON_PRESSED) {
        // If we found invalid fields, focus on the first one and do not submit via ajax.
        if (!this.validateElements()) {
            this.trigger(this.events.CLIENT_VALIDATION_ERROR, null, false);
            return;
        }
        this.disableButtons();

        // Convert all the form elements values to a serialised string.
        const form = this.modal.getRoot().find('form');
        let formDataArray = form.serializeArray();
        const stepIndex = formDataArray.findIndex(item => item.name === 'step');
        if (stepIndex !== -1) {
            const currentStep = parseInt(formDataArray[stepIndex].value, 10) || 1;
            if (eventType === ModalWizard.EVENTS.next) {
                formDataArray[stepIndex].value = currentStep + 1;
            } else if (eventType === ModalWizard.EVENTS.previous) {
                const previousStep = currentStep - 1;
                formDataArray[stepIndex].value = previousStep >= 1 ? previousStep : 1;
            }
        }
        const formData = new URLSearchParams(
            formDataArray.map(item => [item.name, item.value])
        ).toString();
        // Now we can continue...
        Ajax.call([{
            methodname: 'core_form_dynamic_form',
            args: {
                formdata: formData,
                form: this.config.formClass
            }
        }])[0]
            .then((response) => {
                if (!response.submitted) {
                    // Form was not submitted because validation failed.
                    const promise = new Promise(
                        resolve => resolve({html: response.html, js: Fragment.processCollectedJavascript(response.javascript)}));
                    this.modal.setBodyContent(promise);
                    this.enableButtons();
                    this.trigger(this.events.SERVER_VALIDATION_ERROR);
                } else {
                    // Form was submitted properly. Hide the modal and execute callback.
                    const data = JSON.parse(response.data);
                    FormChangeChecker.markFormSubmitted(form[0]);
                    const event = this.trigger(this.events.FORM_SUBMITTED, data);
                    if (!event.defaultPrevented) {
                        this.modal.hide();
                    }
                }
                return null;
            })
            .catch(exception => {
                this.enableButtons();
                this.onSubmitError(exception);
            });
    }

}
