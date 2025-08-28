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

/**
 * AI Placement call extensions placement class.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends \core_ai\placement {

    #[\Override]
    public function get_action_list(): array {
        return [
            \core_ai\aiactions\summarise_text::class,
            \core_ai\aiactions\generate_text::class,
            \core_ai\aiactions\generate_image::class,
            \local_aixtension\aiactions\convert_text_to_speech::class,
        ];
    }

}
