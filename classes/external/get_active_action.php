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
namespace aiplacement_callextensions\external;

use aiplacement_callextensions\ai_action;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Get Running or pending Action (if any)
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_active_action extends external_api {
    /**
     * Checks the parameters and executes the action.
     * @param int $userid The user id.
     * @param int $contextid The context id.
     * @return int an action id if any action is currently running, 0 otherwise
     * @throws \restricted_context_exception
     */
    public static function execute(
        int $contextid,
        int $userid,
    ): array {
        global $USER;
        [
            'contextid' => $contextid,
            'userid' => $userid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'userid' => $userid,
        ]);
        if (!$userid) {
            $userid = $USER->id;
        } else {
            // Check user exists.
            \core_user::get_user($userid, '*', MUST_EXIST);
        }
        // Check context exists.
        $context = \context::instance_by_id($contextid, MUST_EXIST);
        self::validate_context($context);
        require_capability('aiplacement/callextensions:runaction', $context);

        $actions = ai_action::get_records_select(
            "contextid = :contextid AND userid = :userid AND (status = :statusrunning OR status = :statuspending)",
            [
                'userid' => $userid,
                'contextid' => $contextid,
                'statusrunning' => ai_action::STATUS_RUNNING,
                'statuspending' => ai_action::STATUS_PENDING,
            ]
        );
        $action = end($actions);
        if (!$action) {
            return ['actionid' => 0];
        } else {
            return ['actionid' => $action->get('id')];
        }
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context the action is running in'),
            'userid' => new external_value(PARAM_INT, 'The user running the action (or 0 if current user)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'actionid' => new external_value(PARAM_INT, 'Action id if any action is currently active (running, pending), 0 otherwise'),
        ]);
    }
}