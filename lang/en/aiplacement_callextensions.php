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
$string['action:quiz_generate_questions'] = 'Generate questions';
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
$string['actionstatusstarting'] = 'Starting...';
$string['aiplacement/callextensions:use'] = 'Use AI Placement call extensions';
$string['glossary_generate_definitions'] = 'Generate definitions, images and sound for a list of words';
$string['glossary_generate_definitions:actionstarted'] = 'The action has been started';
$string['glossary_generate_definitions:definitionexample'] = 'Example: ';
$string['glossary_generate_definitions:definitionitem'] = 'Definition: ';
$string['glossary_generate_definitions:frenchdefinitionitem'] = 'Français: ';
$string['glossary_generate_definitions:imageprompt'] = 'Image prompt for image generation';
$string['glossary_generate_definitions:imagepromptdefault'] = 'Create a simple, clear and engaging image
 that illustrates the meaning of the word. The image should be suitable for an educational context. Important : the image should NOT
 contain any text.';
$string['glossary_generate_definitions:imagepromptheader'] = 'Image prompt settings';
$string['glossary_generate_definitions:imagesize'] = 'Image size';
$string['glossary_generate_definitions:listenpronunciation'] = 'Listen to the pronunciation:';
$string['glossary_generate_definitions:listenpronunciationexample'] = 'Listen to the example:';
$string['glossary_generate_definitions:noaudio'] = 'Your browser does not support the audio element.';
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
$string['quiz_generate_questions'] = 'Generate questions for a quiz and import them in a gift format';
$string['quiz_generate_questions:actionstarted'] = 'The action has been started';
$string['quiz_generate_questions:additionaloptionsheader'] = 'Additional options';
$string['quiz_generate_questions:context'] = 'The context for the questions';
$string['quiz_generate_questions:context_help'] = 'A description of the context of the question.';
$string['quiz_generate_questions:contextdefault'] = 'Generate a set of questions suitable for a B1 level English learner in the context of a business English course focusing on communication skills in a corporate environment.';
$string['quiz_generate_questions:contextheader'] = 'Context settings';
$string['quiz_generate_questions:contextrequired'] = 'Context is required';
$string['quiz_generate_questions:difficulty'] = 'Question difficulty';
$string['quiz_generate_questions:difficulty:easy'] = 'Easy';
$string['quiz_generate_questions:difficulty:hard'] = 'Hard';
$string['quiz_generate_questions:difficulty:medium'] = 'Medium';
$string['quiz_generate_questions:difficulty_help'] = 'The difficulty of the questions to generate.';
$string['quiz_generate_questions:numquestions'] = 'Number of questions to generate';
$string['quiz_generate_questions:numquestions_help'] = 'The number of questions to generate.';
$string['quiz_generate_questions:processingquestion'] = 'Processing question: {$a}';
$string['quiz_generate_questions:qcontextrequired'] = 'You must provide a context for the questions.';
$string['quiz_generate_questions:questioncategory'] = 'Question category';
$string['quiz_generate_questions:questioncategory_help'] = 'The category in the question bank where the questions will be created.';
$string['quiz_generate_questions:questiontype'] = 'Question type';
$string['quiz_generate_questions:questiontype_help'] = 'The type of questions to generate.';
$string['quiz_generate_questions:quiztitle'] = 'Quiz title';
$string['quiz_generate_questions:quiztitle_help'] = 'The title of the quiz for which the questions will be generated.';
$string['quiz_generate_questions:textprompt'] = 'Text prompt for question generation';
$string['quiz_generate_questions:textpromptdefault'] = 'You are an expert educational content creator. Generate a SINGLE question for that quiz.
OUTPUT FORMAT

Return the question in a GIFT format, suitable for import in Moodle.
Examples of GIFT Format for different question types:
::Q1:: 1+1=2 {T}
::Q2:: What\'s between orange and green in the spectrum? 
{ =yellow # right; good! ~red # wrong, it\'s yellow ~blue # wrong, it\'s yellow }
::Q3:: Two plus {=two =2} equals four.
::Q4:: Which animal eats which food? { =cat -> cat food =dog -> dog food }
::Q5:: What is a number from 1 to 5? {#3:2}
::Q6:: What is a number from 1 to 5? {#1..5}
::Q7:: When was Ulysses S. Grant born? {#
    =1822:0      # Correct! Full credit.
    =%50%1822:2  # He was born in 1822. Half credit for being close.
}

RULES
- Do not include any extra text, only the GIFT format and no comments.
- For multiple choice questions, provide 4 choices, with one correct answer and three plausible distractors.
- For true/false questions, provide a statement that is clearly true or false.
- For short answer questions, provide a question that can be answered with a single word or a short phrase.
- Ensure the questions are clear, concise, and free of ambiguity.
- Avoid using proper nouns or very specific knowledge that may not be known to all learners.
- Ensure the questions are relevant to the quiz title and appropriate for the specified difficulty level.
- Do not use the same question reptitively ensuring variety in the questions generated.';
$string['quiz_generate_questions:voicetext'] = 'Voice for sound generation';
