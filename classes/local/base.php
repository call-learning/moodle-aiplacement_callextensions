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

use core\context;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;
use core\output\renderer_base;

/**
 * Handler for the AI Placement call extensions.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Constructor for the glossary assist UI.
     *
     * @var \stdClass
     */
    public function __construct(
        /* @var renderer_base $output the output classe */
        protected renderer_base $output,
        /* @var context $context the current context */
        protected context $context,
        /* @var \stdClass $user the current user */
        public \stdClass $user
    ) {

    }

    /**
     * Adds any HTML to the page before the footer HTML is generated.
     *
     * @param before_footer_html_generation $hook
     */
    abstract public function before_footer_html_generation(before_footer_html_generation $hook) : void;

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
    abstract public function is_enabled(): bool;

    /**
     * Add action form definitions specific to the module type.
     *
     * @param \MoodleQuickForm $mform The form object.
     * @param string $action The action being performed.
     * @param int|null $step The step number, if applicable.
     * @return void
     */
    abstract public function add_action_form_definitions(\MoodleQuickForm $mform, string $action, ?int $step = null): void;

    /**
     * Process the form data for the specific action.
     *
     * @param object $data The form data.
     * @param string $action The action being performed.
     * @return array The processed result.
     */
    abstract public function process_action_data(object $data, string $action): array;
}