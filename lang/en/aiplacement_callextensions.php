<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     aiplacement_callextensions
 * @category    string
 * @copyright   2025 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action:glossary_generate_definitions'] = 'Generate definition';
$string['action:status_cancelled'] = 'Cancelled';
$string['action:status_error'] = 'Error';
$string['action:status_finished'] = 'Finished';
$string['action:status_pending'] = 'Pending';
$string['action:status_running'] = 'Running';
$string['action:status_unknown'] = 'Unknown';
$string['actiondialog:cancel'] = 'Cancel action';
$string['actiondialog:cancelled'] = 'Action cancelled {$a}';
$string['actiondialog:completed'] = 'Action completed {$a}';
$string['actiondialog:status'] = 'Status: {$a}';
$string['actiondialog:title'] = 'AI Action : {$a}';
$string['actionstatuscancelled'] = 'The action has been cancelled by the user.';
$string['actionstatuserror'] = 'The action has failed with an error.';
$string['actionstatusfinished'] = 'The action has been completed successfully.';
$string['aiplacement/callextensions:use'] = 'Use AI Placement call extensions';
$string['glossary_generate_definitions'] = 'Generate definitions, images and sound for a list of words';
$string['glossary_generate_definitions:actionstarted'] = 'The action has been started';
$string['glossary_generate_definitions:imageprompt'] = 'Image prompt for image generation';
$string['glossary_generate_definitions:imagepromptdefault'] = 'Create a simple, clear and engaging image
 that illustrates the meaning of the word. The image should be suitable for an educational context. They should not contain text.';
$string['glossary_generate_definitions:imagepromptheader'] = 'Image prompt settings';
$string['glossary_generate_definitions:imagesize'] = 'Image size';
$string['glossary_generate_definitions:processingword'] = 'Processing word: {$a}';
$string['glossary_generate_definitions:soundparamheader'] = 'Sound generation settings';
$string['glossary_generate_definitions:textprompt'] = 'Text prompt for definition generation';
$string['glossary_generate_definitions:textpromptdefault'] = '
Given a single English word, produce:
1) A concise, learner-friendly definition in English.
2) A French translation.
3) One simple example sentence in English.

OUTPUT FORMAT
Return ONLY a single JSON object with these keys:
- "word": the input word (string)
- "definition_en": the definition in English (string, 12–30 words, no jargon)
- "translation_fr": the French translation of the word (string; if noun, include correct gender/article)
- "gender_or_article": the English gender/article tag for nouns (string; "m", "f", "m/f", "n/a" for non-nouns, adv. for adverbs)
- "example_en": a short, natural English example sentence using the word (string)

RULES
- Output valid JSON only. No markdown, no comments, no extra text.
- Escape all quotes properly for JSON.
- Keep it concise and clear for learners (A2–B1 level).
- If the word is not a noun, set "gender_or_article" to "n/a" and leave the span content as "n/a".
- Do not invent multiple senses; pick the most common educational sense.
- Avoid idioms, slang, and rare usages.';
$string['glossary_generate_definitions:textpromptheader'] = 'Text prompt settings';
$string['glossary_generate_definitions:voice'] = 'Voice for sound generation';
$string['glossary_generate_definitions:wordlist'] = 'Word list';
$string['glossary_generate_definitions:wordlist_help'] = 'A list of words to generate definitions, images and sound for.';
$string['glossary_generate_definitions:wordlistinfo'] = 'List of words: {$a}';
$string['pleasewait'] = 'Please wait...';
$string['pluginname'] = 'AI Extension';
$string['privacy:metadata'] = 'The AI Placement Call Extensions plugin does not store any personal data.';
$string['progressfor'] = 'Progress for {$a}';
