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

/**
 * Return if any action is currently running with this user id and context id.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace aiplacement_callextensions\external;

use aiplacement_callextensions\ai_action;
use core\task\manager;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
/**
 * Cancel an action
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cancel_action extends external_api {
    /**
     * Checks the parameters and executes the action.
     *
     * @param int $actionid
     * @return array an action id if any action is currently running, 0 otherwise
     * @throws \invalid_parameter_exception
     */
    public static function execute(
        int $actionid,
    ): array {
        [
            'actionid' => $actionid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'actionid' => $actionid,
        ]);
        self::validate_context(\context_system::instance());
        $action = ai_action::get_record(['id' => $actionid]);
        if ($action) {
            $action->set('status', ai_action::STATUS_CANCELLED);
            $action->set('statustext', get_string('actionstatuscancelled', 'aiplacement_callextensions'));
            $action->save();
            // Check for adhoc tasks and remove them.
            $alltasks = \core\task\manager::get_adhoc_tasks(\aiplacement_callextensions\task\execute_action::class);
            foreach ($alltasks as $task) {
                $data = $task->get_custom_data();
                if (isset($data->actionid) && $data->actionid == $actionid) {
                    $lockedtask = \core\task\manager::get_adhoc_task($task->get_id());
                    \core\task\manager::adhoc_task_complete($lockedtask);
                }
            }
            return ['hascancelled' => true];
        }
        return ['hascancelled' => false];
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
            'hascancelled' => new external_value(PARAM_BOOL, 'Action has been cancelled (true) or not (false)'),
        ]);
    }
}