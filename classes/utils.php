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


use aiplacement_callextensions\local\base;
use moodle_page;

/**
 * AI Placement course assist utils.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
    /**
     * Check if AI Placement course assist is available for the context.
     *
     * @param \context $context The context.
     * @param \stdClass $user The user for whom the check is being performed.
     * @return bool True if AI Placement course assist is available, false otherwise.
     */
    public static function is_assist_available(\context $context, \stdClass $user): bool {
        [$plugintype, $pluginname] = explode('_', \core_component::normalize_componentname('aiplacement_callextensions'), 2);
        $manager = \core_plugin_manager::resolve_plugininfo_class($plugintype);
        if (!$manager::is_plugin_enabled($pluginname)) {
            return false;
        }

        //$providers = manager::get_providers_for_actions([summarise_text::class], true);
        //if (!has_capability('aiplacement/callextensions:summarise_text', $context)
        //    || !manager::is_action_available(summarise_text::class)
        //    || !manager::is_action_enabled('aiplacement_callextensions', summarise_text::class)
        //    || empty($providers[summarise_text::class])
        //) {
        //    return false;
        //}

        return true;
    }

    /**
     * Get the assist UI instance.
     *
     * @param \context $context The context for which the assist UI is being requested.
     * @param string $modulename
     * @param array $capabilities The capabilities required to use the assist UI.
     * @param string $currentpagelayout The current page layout.
     * @return bool representing the matching \aiplacement_callextensions\output\assist_ui
     */
    public static function preflight_checks_for_module(
        \context $context,
        string $modulename,
        array $capabilities,
        array $pagetypeallowed = ['mod-*'],
        moodle_page $page,
    ): bool {
        $cm = get_coursemodule_from_id(
            $modulename,
            $context->instanceid,
            0,
            false,
            IGNORE_MISSING
        );
        if (!$cm) {
            // If we cannot find the course module, we cannot proceed.
            return false;
        }
        if (!has_all_capabilities($capabilities, $context)) {
            return false;
        }
        if (in_array($page->pagelayout, ['maintenance', 'print', 'redirect', 'embedded'])) {
            // Do not try to show assist UI inside iframe, in maintenance mode,
            // when printing, or during redirects.
            return false;
        }
        if (in_array($page->pagetype, $pagetypeallowed) === false) {
            // Only load the assist UI for allowed page types.
            return false;
        }

        if ($cm->modname !== $modulename) {
            // Only load the assist UI for glossary modules.
            return false;
        }
        return true;
    }

    /**
     * Perform generic preflight checks for AI Placement actions.
     *
     * @param \context $context The context to check.
     * @param \stdClass $user The user for whom the checks are being performed.
     * @return bool True if all checks pass, false otherwise.
     */
    public static function generic_preflight_checks(\context $context, \stdClass $user): bool {
        // Check if we are during initial install.
        if (during_initial_install()) {
            return false;
        }

        // Check if the current page is a course page.
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return false;
        }

        // Check if the user has the capability to use AI Placement.
        if (!has_capability('aiplacement/callextensions:use', $context, $user->id)) {
            return false;
        }
        if (!self::is_assist_available($context, $user)) {
            return false;
        }

        return true;
    }
}
