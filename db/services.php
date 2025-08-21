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
 * Web services
 *
 * @package aiplacement_callextensions
 * @copyright 2025 Laurent David <laurent@call-learning.fr>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'aiplacement_callextensions_get_active_action' => [
        'classname' => \aiplacement_callextensions\external\get_active_action::class,
        'methodname' => 'execute',
        'description' => 'Check and return any running or pending action for the current user.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'aiplacement_callextensions_cancel_action' => [
        'classname' => \aiplacement_callextensions\external\cancel_action::class,
        'methodname' => 'execute',
        'description' => 'Cancel a given action.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'aiplacement_callextensions_launch_action' => [
        'classname' => \aiplacement_callextensions\external\launch_action::class,
        'methodname' => 'execute',
        'description' => 'Launch an action for the current user.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'aiplacement_callextensions_get_action_status' => [
        'classname' => \aiplacement_callextensions\external\action_status::class,
        'methodname' => 'execute',
        'description' => 'Get current action status.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];