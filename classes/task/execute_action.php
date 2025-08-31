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
namespace aiplacement_callextensions\task;

use aiplacement_callextensions\ai_action;
use aiplacement_callextensions\extension_factory;

/**
 * Adhoc task to execute an action.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class execute_action extends \core\task\adhoc_task {
    /**
     * Execute the task.
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $actionid = $data->actionid;
        $action = ai_action::get_record(['id' => $actionid]);
        if (!$action) {
            mtrace("Action with id $actionid not found");
            return;
        }

        $context = \context::instance_by_id($action->get('contextid'));

        $user = \core_user::get_user($action->get('userid'), '*', MUST_EXIST);
        if (!$extension = extension_factory::create($context, $user)) {
            mtrace("No extension found for action with id {$action->actionname}({$action->id})");
            $action->set('status', ai_action::STATUS_ERROR);
            $action->set('statustext', get_string('actionstatusfailed', 'aiplacement_callextensions'));
            $action->save();
            return;
        }

        try {
            $action->set('status', ai_action::STATUS_RUNNING);
            $action->set_progress_status(0, get_string('actionstatusstarting', 'aiplacement_callextensions'));
            $extension->execute_action($action);
            $action->set('status', ai_action::STATUS_FINISHED);
            $action->set_progress_status(100, get_string('actionstatusfinished', 'aiplacement_callextensions'));
            $action->save();
        } catch (\Exception $e) {
            mtrace("Action with id {$action->actionname}({$action->id}) could not be set to running: {$e->getMessage()}");
            $action->set_progress_status(100, get_string('actionstatusfailed', 'aiplacement_callextensions'));
            $action->set('status', ai_action::STATUS_ERROR);
            $action->save();
        }
    }
}
