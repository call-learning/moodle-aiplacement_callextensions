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
use core\di;

/**
 * AI Placement course assist utils.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extension_factory {
    /**
     * Get the extension instance for a given context.
     *
     * For now, only course module context is supported.
     *
     * @param \context $context The context for which the module info is being requested.
     * @param \stdClass|null $user
     * @return base|null The module extension instance or null if not found.
     * @throws \coding_exception
     */
    public static function create(\context $context, ?\stdClass $user = null): ?local\base {
        global $USER;
        $cm = get_coursemodule_from_id(
            $expectedmodule ?? '',
            $context->instanceid
        );
        if (!$cm) {
            return null;
        }

        // Construct the extension class name based on the module name.
        $extensionclass = "\\aiplacement_callextensions\\local\\mod_{$cm->modname}\\extension";

        if (!class_exists($extensionclass)) {
            return null;
        }
        $extension = di::get_container()->make($extensionclass, [
            'contextid' => $context->id,
            'userid' => intval($user->id ?? $USER->id),
        ]);
        if (!$extension) {
            throw new \coding_exception("Class $extensionclass does not exist.");
        }
        if (!($extension instanceof local\base)) {
            throw new \coding_exception("Class $extensionclass must extend " . local\base::class);
        }
        // Check if the extension is enabled for this context.
        if (!$extension->is_enabled()) {
            return null;
        }
        return $extension;
    }

}

