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

use aiplacement_callextensions\ai_action;
use aiplacement_callextensions\extension_factory;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
global $CFG;
require_once("{$CFG->libdir}/clilib.php");

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'glossaryid' => null,
        "filetoimport" => null,
    ],
    [
        'h' => 'help',
        'g' => 'glossaryid',
        'f' => 'filetoimport',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help = <<<EOT
Create a glossary from a text file.

Options:
 -h, --help             Print out this help
 -g, --glossaryid       The glossary course module id to create the glossary in
EOT;

if ($options['help']) {
    echo $help;
    exit(0);
}
if (empty($options['glossaryid'])) {
    cli_error("You must specify a glossary id with --glossaryid");
}
$cm = get_coursemodule_from_id('glossary', (int) $options['glossaryid'], 0, false, MUST_EXIST);
if (empty($options['filetoimport'])) {
    cli_error("You must specify a file to import with --filetoimport");
}
if (!file_exists($options['filetoimport'])) {
    cli_error("File not found or not readable: {$options['filetoimport']}");
}
$file = fopen($options['filetoimport'], 'r');
if (!$file) {
    cli_error("File not found or not readable: {$options['filetoimport']}");
}
$content = file_get_contents($options['filetoimport']);
if ($content === false) {
    cli_error("File not found or not readable: {$options['filetoimport']}");
}
// Pre-parse the list of words.
$listofwords = preg_split('/\r\n|\r|\n/', $content);
$listofwords = array_map(function ($line) {
    if (strpos($line, '=') !== false) {
        [$term, $frenchdefinition] = explode('=', $line, 2);
        return trim($term);
    }
    return trim($line);
}, $listofwords);
$listofwords = array_filter(array_map('trim', $listofwords));
$listofwords = array_values(array_unique($listofwords));
try {
    $params = [
        'wordlist' => $listofwords,
    ];
    $admin = get_admin();
    // Set the admin user for the context globally.
    $USER = $admin;
    $context = \context_module::instance($cm->id);
    $extension = extension_factory::create($context, $admin);
    if (!$extension) {
        cli_error("No AI Placement extension found for module id $cm->id");
    }
    $action = new ai_action(0, (object) [
        'actionname' => 'glossary_generate_definitions',
        'contextid' => $context->id,
        'userid' => $admin->id,
        'actiondata' => json_encode($params),
        'status' => ai_action::STATUS_PENDING,
    ]);
    $action->save();
    $extension->execute_action($action);
    $action->set('status', ai_action::STATUS_FINISHED);
    $action->set_progress_status(100, get_string('actionstatusfinished', 'aiplacement_callextensions'));
} finally {
    fclose($file);
}
