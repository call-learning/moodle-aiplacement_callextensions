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

use aiplacement_callextensions\extension_factory;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Launch an action
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launch_action extends external_api {
    /**
     * Checks the parameters and executes the action.
     *
     * @param int $contextid the context the action is running in
     * @param string $actiondata the action data
     * @return array an action id if any action is currently running, 0 otherwise
     * @throws \invalid_parameter_exception
     */
    public static function execute(
        int $contextid,
        string $actiondata,
    ): array {
        global $USER;
        [
            'contextid' => $contextid,
            'actionname' => $actionname,
            'actiondata' => $actiondata,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'actionname' => $actiondata,
            'actiondata' => $actiondata,
        ]);

        $context = \context::instance_by_id($contextid);
        self::validate_context($context);
        $extension = extension_factory::create($context, $USER);
        if ($extension) {
            return ['actionid' => $extension->launch_action($actionname, json_decode($actiondata, true))];
        }
        return ['actionid' => 0];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context the action is running in'),
            'actionname' => new external_value(PARAM_ALPHANUMEXT, 'The action name'),
            'actiondata' => new external_value(PARAM_RAW, 'The action data'),
        ]);
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'actionid' => new external_value(PARAM_INT, 'Action id if any action is currently running, 0 otherwise'),
        ]);
    }
}