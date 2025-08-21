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
use aiplacement_callextensions\extension_factory;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Get current action status (if any action is currently running or pending)
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_status extends external_api {
    /**
     * Checks the parameters and executes the action.
     *
     * @param int $actionid The user id.
     * @return int an action id if any action is currently running, 0 otherwise
     * @throws \restricted_context_exception
     */
    public static function execute(
        int $actionid,
    ): array {
        global $USER;
        [
            'actionid' => $actionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'actionid' => $actionid,
        ]);
        $action = ai_action::get_record(['id' => $actionid]);
        if (!$action) {
            throw new \moodle_exception('invalidactionid', 'aiplacement_callextensions');
        }
        $context = \context::instance_by_id($action->get('contextid'));
        self::validate_context($context);
        $actionrecord = $action->to_record();
        $extension = extension_factory::create($context, $USER);
        $actiondata = '';
        if ($extension) {
            $actiondata = $extension->actiondata_to_string($action);
        }
        return [
            'actionid' => $actionrecord->id,
            'actionname' => $actionrecord->actionname,
            'statustext' => $actionrecord->statustext,
            'status' => $actionrecord->status,
            'progress' => $actionrecord->progress,
            'actiondata' => $actiondata,
        ];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'actionid' => new external_value(PARAM_INT, 'The action identifier'),
        ]);
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'actionid' => new external_value(
                PARAM_INT,
                'Action id if any action is currently active (running, pending), 0 otherwise'
            ),
            'actionname' => new external_value(PARAM_TEXT, 'The action name'),
            'statustext' => new external_value(PARAM_TEXT, 'The action status text'),
            'status' => new external_value(PARAM_INT, 'The action status'),
            'progress' => new external_value(PARAM_INT, 'The action progress (0-100)'),
            'actiondata' => new external_value(PARAM_TEXT, 'The action data'),
        ]);
    }
}
