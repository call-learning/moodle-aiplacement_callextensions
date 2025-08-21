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
 * CLI script to launch execution of AI Placement course assist.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use aiplacement_callextensions\extension_factory;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
global $CFG;
require_once("{$CFG->libdir}/clilib.php");


list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        "moduleid" => null,
        'param' => "{}",
    ], [
        'h' => 'help',
        'm' => 'moduleid',
        'p' => 'param',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = <<<EOT
Launch AI Placement course assist CLI script.

Options:
 -h, --help                Print out this help
 -m, --moduleid      The module id to execute the task for
 -p, --param       Set a parameter for the task (json string)
EOT;

if ($options['help']) {
    echo $help;
    exit(0);
}
$taskparams = trim($options['param'], "'") ?? '{}';
$params = json_decode($taskparams, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    cli_error("Invalid JSON in --param option:  {$taskparams} " . json_last_error_msg());
}
if (empty($options['moduleid'])) {
    cli_error("You must specify a module id with --moduleid");
}
$moduleid = (int)$options['moduleid'];
if ($moduleid <= 0) {
    cli_error("Invalid module id specified: $moduleid");
}
$admin = get_admin();
// Set the admin user for the context globally.
$USER = $admin;

$context = \context_module::instance($moduleid);
$extension = extension_factory::create($context, $admin);
if (!$extension) {
    cli_error("No AI Placement extension found for module id $moduleid");
}
$extension->execute_action((array) $params);