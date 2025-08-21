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

namespace aiplacement_callextensions\local;

use aiplacement_callextensions\ai_action;
use aiplacement_callextensions\utils;
use core\context;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;

/**
 * Handler for the AI Placement call extensions.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var context The current context. */
    protected context $context;

    /** @var \stdClass|null The current user, defaults to global $USER if not provided. */
    protected ?\stdClass $user = null;

    /**
     * Constructor for the glossary assist UI.
     *
     * @param context $context The current context.
     * @param \stdClass|null $user The current user, defaults to global $USER
     *                             if not provided.
     */
    public function __construct(
        /** @var int $contextid the current context */
        protected int $contextid,
        /** @var int $userid the current user id */
        protected int $userid,
    ) {
        $this->context = context::instance_by_id($this->contextid);
        $this->user = \core_user::get_user($this->userid, '*', MUST_EXIST);
    }

    /**
     * Adds any HTML to the page before the footer HTML is generated.
     *
     * @param before_footer_html_generation $hook
     */
    abstract public function before_footer_html_generation(before_footer_html_generation $hook): void;

    /**
     * Add any HTML to the page after the HTTP headers have been sent.
     *
     * @param after_http_headers $hook
     */
    abstract public function after_http_headers(after_http_headers $hook): void;

    /**
     * Check if the extension is enabled for the given context.
     *
     * @return bool True if the extension is enabled, false otherwise.
     */
    public function is_enabled(): bool {
        return utils::generic_preflight_checks($this->context, $this->user) &&
            utils::is_assist_available($this->context, $this->user);
    }

    /**
     * Add action form definitions specific to the module type.
     *
     * @param \MoodleQuickForm $mform The form object.
     * @param string $action The action being performed.
     * @return void
     */
    abstract public function add_action_form_definitions(\MoodleQuickForm $mform, string $action): void;

    /**
     * Process the form data for the specific action.
     *
     * @param object $data The form data.
     * @param string $action The action being performed.
     * @return array The processed result with ['result' => bool, 'message' => string, 'data'=> processed data].
     * Note that result => false is due to internal processing error, not validation error
     * (that should be handled in validate_action_data).
     */
    abstract public function process_action_data(object $data, string $action): array;

    /**
     * Validate the action data for the specific action.
     *
     * @param array $data The form data.
     * @param array $files The files data.
     * @param string $action The action being performed.
     * @return array The validated result.
     */
    abstract public function validate_action_data(array $data, array $files, string $action): array;

    /**
     * Get the context for the module.
     *
     * @return context The context for the module.
     */
    public function get_context(): context {
        return $this->context;
    }

    /**
     * Get the user for whom the module is being processed.
     *
     * @return \stdClass The user object.
     */
    public function get_user(): \stdClass {
        return $this->user;
    }

    /**
     * Execute the action for the module.
     */
    abstract public function execute_action(ai_action $action): void;

    /**
     * Convert the action data to a string for logging or display purposes.
     *
     * @param ai_action $action The action instance.
     * @return string The action data as a string.
     */
    abstract public function actiondata_to_string(ai_action $action): string;

    /**
     * Launch the execution of a specific action.
     *
     * @param string $actionname The name of the action to launch.
     * @param array $params The parameters for the action.
     * @return ai_action The launched action instance.
     */
    public function launch_action(string $actionname, array $params): ai_action {
        // Check if an action is already running for this context and user.
        if (
            ai_action::record_exists_select(
                'contextid = :contextid AND userid = :userid AND status = :status',
                [
                'contextid' => $this->context->id,
                'userid' => $this->userid,
                'status' => ai_action::STATUS_RUNNING,
                ]
            )
        ) {
            throw new \moodle_exception(
                'actionalreadyrunning',
                'aiplacement_callextensions',
                ['action' => $actionname, 'contextid' => $this->context->id]
            );
        };
        $action = new ai_action(0, (object) [
            'actionname' => $actionname,
            'contextid' => $this->context->id,
            'userid' => $this->userid,
            'actiondata' => json_encode($params),
            'status' => ai_action::STATUS_PENDING,
        ]);
        $action->save();

        // Execute the action via adhoc task.
        $task = new \aiplacement_callextensions\task\execute_action();
        $task->set_custom_data([
            'actionid' => $action->get('id'),
        ]);
        \core\task\manager::queue_adhoc_task($task);

        return $action;
    }
}
