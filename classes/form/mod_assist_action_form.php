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

namespace aiplacement_callextensions\form;

use aiplacement_callextensions\extension_factory;
use aiplacement_callextensions\local\base;
use aiplacement_callextensions\utils;
use context;
use core_component;
use core_external\external_api;
use core_form\dynamic_form;
use moodle_exception;
use moodle_url;

/**
 * Dynamic form for module assist actions.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_assist_action_form extends dynamic_form {

    /**
     * @var context|null The current context, if set
     */
    protected ?context $currentcontext = null;

    /**
     * @var base|null The base extension instance, if set
     */
    protected ?base $extension = null;

    /**
     * Process the dynamic form submission.
     *
     * @return array Array containing the result of the form processing
     * @throws moodle_exception If access is denied
     */
    public function process_dynamic_submission(): array {
        $this->check_access_for_dynamic_submission();
        if (empty($this->extension)) {
            return [
                'result' => false,
                'error' => get_string('modulenotfound', 'aiplacement_callextensions'),
            ];
        }

        $formdata = $this->get_data();
        $processeddata = $this->extension->process_action_data($formdata, $this->get_action());

        return $processeddata;
    }

    /**
     * Validate and retrieve the cmid and component parameters.
     *
     * @return array An associative array containing 'cmid' and 'component'
     * @throws moodle_exception If parameters are invalid or missing
     */
    private function get_cmid_and_component(): array {
        $cmid = $this->optional_param('cmid', null, PARAM_INT);
        $component = $this->optional_param('component', null, PARAM_COMPONENT);

        if (empty($cmid) || empty($component)) {
            throw new moodle_exception('missingparams', 'aiplacement_callextensions', '', 'cmid or component');
        }

        return ['cmid' => $cmid, 'component' => $component];
    }

    /**
     * Get the context for dynamic form submission.
     *
     * @return context The module context
     * @throws moodle_exception If module cannot be found
     */
    protected function get_context_for_dynamic_submission(): context {
        if ($this->currentcontext !== null) {
            return $this->currentcontext;
        }
        $params = $this->get_cmid_and_component();
        $component = core_component::normalize_componentname($params['component']);
        [$plugintype, $pluginname] = explode('_', $component, 2);
        $cm = get_coursemodule_from_id($pluginname, $params['cmid']);
        $this->currentcontext = \context_module::instance($cm->id);
        if ($this->currentcontext) {
            $this->extension = extension_factory::create($this->currentcontext);
        }
        return $this->currentcontext;
    }

    /**
     * Set the data for dynamic form submission.
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        $data = (object) [
            'cmid' => $this->optional_param('cmid', 0, PARAM_INT),
            'userid' => $this->optional_param('userid', 0, PARAM_INT),
            'actionname' => $this->optional_param('actionname', '', PARAM_ALPHANUMEXT),
            'component' => $this->optional_param('component', '', PARAM_COMPONENT),
        ];
        $this->set_data($data);
    }

    /**
     * Check access permissions for dynamic form submission.
     *
     * @return void
     * @throws moodle_exception If user doesn't have required capability
     */
    protected function check_access_for_dynamic_submission(): void {
        if (!has_capability('aiplacement/callextensions:use', $this->get_context_for_dynamic_submission())) {
            throw new moodle_exception(get_string('cannotgenerate', 'aiplacement_callextensions'), '');
        }
    }

    /**
     * Get the page URL for dynamic form submission.
     *
     * @return moodle_url The URL to redirect to after form submission
     * @throws moodle_exception If component is invalid
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        $params = $this->get_cmid_and_component();
        $component = core_component::normalize_componentname($params['component']);
        [$plugintype, $pluginname] = explode('_', $component, 2);
        if ($plugintype !== 'mod') {
            throw new moodle_exception('invalidcomponent', 'aiplacement_callextensions');
        }
        return new moodle_url('/mod/view.php', ['id' => $params['cmid']]);
    }

    /**
     * Define the form elements for the action form.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'actionname');
        $mform->setType('actionname', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'component');
        $mform->setType('component', PARAM_COMPONENT);
        if ($this->extension) {
            $this->extension->add_action_form_definitions($mform, $this->get_action());
        }

    }

    /**
     * Validate the form data.
     *
     * @param array $data
     * @param array $files
     * @return array|null
     */
    public function validation($data, $files) {
        return $this->extension?->validate_action_data($data, $files, $this->get_action());
    }
    /**
     * Get the action parameter from the form.
     *
     * @return string The action to be performed
     */
    private function get_action(): string {
        return $this->optional_param('actionname', '', PARAM_ALPHANUMEXT);
    }
}
