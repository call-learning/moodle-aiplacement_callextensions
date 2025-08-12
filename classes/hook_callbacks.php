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

namespace aiplacement_callextensions;

use aiplacement_callextensions\local\mod_glossary_extension;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for the course assist AI Placement.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Bootstrap the course assist UI.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        $extension = utils::get_extension_from_context($hook->renderer->get_page()->context);
        if ($extension  && $extension->is_enabled()) {
            $extension->before_footer_html_generation($hook);
        }
    }

    /**
     * Bootstrap the summarise button.
     *
     * @param after_http_headers $hook
     */
    public static function after_http_headers(after_http_headers $hook): void {
        $extension = utils::get_extension_from_context($hook->renderer->get_page()->context);
        if ($extension  && $extension->is_enabled()) {
            $extension->after_http_headers($hook);
        }
    }
}
