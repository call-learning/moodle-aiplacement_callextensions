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

namespace aiplacement_callextensions\local\mod_book;

use aiplacement_callextensions\ai_action;
use aiplacement_callextensions\local\base;
use aiplacement_callextensions\local\helpers\module_helper;
use aiplacement_callextensions\local\helpers\wordlist_helper;
use aiplacement_callextensions\utils;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;
use core_ai\aiactions\generate_image;
use core_ai\aiactions\generate_text;
use core_ai\manager;
use local_aixtension\aiactions\convert_text_to_speech;
use MoodleQuickForm;
use stdClass;

/**
 * Output handler for the book assist UI.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extension extends base {
    #[\Override]
    public function before_footer_html_generation(before_footer_html_generation $hook): void {
        $html = '';
        $hook->add_html($html);
    }

    #[\Override]
    public function after_http_headers(after_http_headers $hook): void {
        if (
            !utils::preflight_checks_for_module(
                $this->context,
                'book',
                ['mod/book:edit'],
                ['mod-book-view'],
                $hook->renderer->get_page(),
            )
        ) {
            return;
        }

        $context = [
            "cmid" => $this->context->instanceid,
            'contextid' => $this->context->id,
            "actionname" => 'book_generate_definitions',
            "component" => 'mod_book',
            "buttontext" => get_string('action:book_generate_definitions', 'aiplacement_callextensions'),
            "buttonlabel" => get_string('action:book_generate_definitions', 'aiplacement_callextensions'),
        ];
        $html = $hook->renderer->render_from_template('aiplacement_callextensions/action_button', $context);
        $hook->add_html($html);
    }

    #[\Override]
    public function add_action_form_definitions(MoodleQuickForm $mform, string $action): void {
        $mform->addElement(
            'textarea',
            'wordlist',
            get_string('book_generate_definitions:wordlist', 'aiplacement_callextensions'),
            ['rows' => 10, 'cols' => 50]
        );
        $mform->setType('wordlist', PARAM_TEXT);
        $mform->addHelpButton('wordlist', 'book_generate_definitions:wordlist', 'aiplacement_callextensions');
        // Additional elements for context and optional parameters.
        $mform->addElement(
            'header',
            'textpromptheader',
            get_string('book_generate_definitions:textpromptheader', 'aiplacement_callextensions')
        );
        $mform->setExpanded('textpromptheader', false);
        $mform->addElement(
            'textarea',
            'textprompt',
            get_string('book_generate_definitions:textprompt', 'aiplacement_callextensions'),
            ['rows' => 3, 'cols' => 50]
        );
        $mform->setType('textprompt', PARAM_TEXT);
        $mform->setDefault(
            'textprompt',
            get_string('book_generate_definitions:textpromptdefault', 'aiplacement_callextensions')
        );

        // Additional elements for image generation.
        $mform->addElement(
            'header',
            'imagepromptheader',
            get_string('book_generate_definitions:imagepromptheader', 'aiplacement_callextensions')
        );
        $mform->setExpanded('imagepromptheader', false);
        $mform->addElement(
            'textarea',
            'imageprompt',
            get_string('book_generate_definitions:imageprompt', 'aiplacement_callextensions'),
            ['rows' => 3, 'cols' => 50]
        );
        $mform->setType('imageprompt', PARAM_TEXT);
        $mform->setDefault(
            'imageprompt',
            get_string('book_generate_definitions:imagepromptdefault', 'aiplacement_callextensions')
        );
        $mform->addElement(
            'select',
            'imagesize',
            get_string('book_generate_definitions:imagesize', 'aiplacement_callextensions'),
            [
                '128x128' => '128x128',
                '256x256' => '256x256',
                '512x512' => '512x512',
                '1024x1024' => '1024x1024',
            ]
        );
        $mform->setType('imagesize', PARAM_TEXT);
        $mform->setDefault('imagesize', '256x256');
        $mform->addElement(
            'header',
            'soundparamheader',
            get_string('book_generate_definitions:soundparamheader', 'aiplacement_callextensions')
        );
        $mform->setExpanded('soundparamheader', false);
        $mform->addElement(
            'select',
            'voice',
            get_string('book_generate_definitions:voice', 'aiplacement_callextensions'),
            [
                'alloy' => 'Alloy',
                'ash' => 'Ash',
                'ballad' => 'Ballad',
                'coral' => 'Coral',
                'echo' => 'Echo',
                'fable' => 'Fable',
                'nova' => 'Nova',
                'onyx' => 'Onyx',
                'sage' => 'Sage',
                'shimmer' => 'Shimmer',
            ]
        );
        $mform->setType('soundparam', PARAM_TEXT);
        $mform->setDefault('voice', 'alloy');
    }

    #[\Override]
    public function process_action_data(object $data, string $action): array {
        switch ($action) {
            case 'book_generate_definitions':
                return $this->process_generate_definition($data);
            default:
                return $this->process_default_action($data, $action);
        }
    }

    /**
     * Process generate definition action.
     *
     * @param object $data The form data.
     * @return array The processed result.
     */
    private function process_generate_definition(object $data): array {
        $wordlist = trim($data->wordlist ?? '');

        if (empty($wordlist)) {
            return [
                'success' => false,
                'message' => get_string('conceptrequired', 'aiplacement_callextensions'),
            ];
        }
        $datakeys = [
            'wordlist',
            'imagesize',
            'imageprompt',
            'textprompt',
            'voice',
        ];
        $launchdata = array_intersect_key((array) $data, array_flip($datakeys));
        $wordlist = array_filter(array_map('trim', explode("\n", $launchdata['wordlist'])));
        natsort($wordlist);
        $wordlist = array_values(array_unique($wordlist));
        $launchdata['wordlist'] = $wordlist;
        $this->launch_action('book_generate_definitions', $launchdata);
        return [
            'success' => true,
            'data' => $launchdata,
            'message' => get_string('book_generate_definitions:actionstarted', 'aiplacement_callextensions'),
        ];
    }

    /**
     * Process default action.
     *
     * @param object $data The form data.
     * @param string $action The action being performed.
     * @return array The processed result.
     */
    private function process_default_action(object $data, string $action): array {
        if (empty($data)) {
            return [
                'success' => false,
                'error' => get_string('promptrequired', 'aiplacement_callextensions'),
            ];
        }

        return [
            'success' => true,
            'action_type' => $action,
        ];
    }

    #[\Override]
    public function actiondata_to_string(ai_action $action): string {
        $data = $action->get('actiondata') ?? [];

        if ($action->get('actionname') === 'book_generate_definitions') {
            $wordlist = $data['wordlist'] ?? [];
            $content = get_string(
                'book_generate_definitions:wordlistinfo',
                'aiplacement_callextensions',
                implode(
                    ', ',
                    $wordlist
                )
            );
        }
        return $content;
    }

    #[\Override]
    public function validate_action_data(array $data, array $files, string $actionname): array {
        $errors = [];
        switch ($actionname) {
            case 'book_generate_definitions':
                $wordlisterrors = wordlist_helper::validate_wordlist($data['wordlist'] ?? '');
                if (!empty($wordlisterrors)) {
                    $errors['wordlist'] = join(" ", $wordlisterrors);
                }
                break;
            default:
                break;
        }
        return $errors;
    }

    #[\Override]
    public function execute_action(ai_action $aiaction): void {
        global $DB, $OUTPUT;
        if ($aiaction->get('actionname') !== 'book_generate_definitions') {
            return;
        }
        $params = $aiaction->get('actiondata') ?? [];
        $wordlist = $params['wordlist'] ?? '';
        [$imagewidth, $imageheight] = explode('x', $params['imagesize'] ?? '256x256');
        $imagewidth = $imagewidth ?: 256;
        $imageheight = $imageheight ?: 256;

        // Process each word.
        $manager = new manager();
        $bookcm = get_coursemodule_from_id('book', $this->context->instanceid, 0, false, IGNORE_MISSING);
        $bookid = $bookcm->instance;
        $totalwords = count($wordlist);
        $currentindex = 0;
        $textproompt =
            $params['textprompt'] ?? get_string('book_generate_definitions:textpromptdefault', 'aiplacement_callextensions');
        $imageprompt =
            $params['imageprompt'] ?? get_string('book_generate_definitions:imagepromptdefault', 'aiplacement_callextensions');
        $pagenum = $DB->get_field_sql('SELECT MAX(pagenum) FROM {book_chapters} WHERE bookid = ?', [$bookid]) + 1;
        foreach ($wordlist as $lineno => $wordfromlist) {
            // Word is maybe a bit of a complex structure so let's parse it properly.
            ['success' => $ok, 'errors' => $parsingerrors, 'entry' => $entry] =
                wordlist_helper::parse_word($wordfromlist, $lineno + 1);
            if ($ok && $entry) {
                $word = $entry['word'];
                $aiaction->set_progress_status(
                    statustext: get_string(
                        'book_generate_definitions:processingword',
                        'aiplacement_callextensions',
                        $word
                    )
                );
                // Create a new book entry.
                $entryobj = new stdClass();
                $entryobj->concept = trim($word);
                $entryobj->bookid = $bookid;
                $entryobj->pagenum = $pagenum;
                $entryobj->title = trim($word);
                $entryobj->content = ''; // We will update it later.
                $entryobj->contentformat = FORMAT_HTML;
                $entryobj->hidden = 0;
                $entryobj->timecreated = time();
                $entryobj->timemodified = time();
                $chapterid = $DB->insert_record('book_chapters', $entryobj);
                $imageurl = null;
                $audiourl = null;

                $action = new generate_text(
                    contextid: $this->context->id,
                    userid: $this->user->id,
                    prompttext: "{$textproompt}\n\nWORD\n{$word}",
                );
                $response = $manager->process_action($action);
                $data = [];
                if ($response->get_success()) {
                    $description = $response->get_response_data()['generatedcontent'];
                    if (json_decode($description) !== null) {
                        $data = json_decode($description, true);
                    }
                } else {
                    $aiaction->set_progress_status(
                        statustext: get_string(
                            'book_generate_definitions:errorprocessingword',
                            'aiplacement_callextensions',
                            $word
                        )
                    );
                    continue;
                }
                // Here we try to be agnostic to exact model used... but if we use the openai_extension model this
                // should work better as dall-e 3 does not give good results for image generation.
                $action = new generate_image(
                    contextid: $this->context->id,
                    userid: $this->user->id,
                    prompttext: "{$imageprompt}" . get_string(
                        'book_generate_definitions:imagepromptword',
                        'aiplacement_callextensions',
                        $word
                    ),
                    quality: $params['quality'] ?? 'standard',
                    aspectratio: $params['aspectratio'] ?? 'square',
                    numimages: 1,
                    style: $params['style'] ?? 'natural',
                );
                $response = $manager->process_action($action);
                if ($response->get_success()) {
                    $draftfile = $response->get_response_data()['draftfile'];
                    $imageurl = module_helper::copy_file(
                        $chapterid,
                        $draftfile,
                        $this->context->id,
                        'mod_book',
                        'chapter'
                    );
                }
                $action = new convert_text_to_speech(
                    contextid: $this->context->id,
                    userid: $this->user->id,
                    texttoread: "{$word}",
                    voice: $params['voice'] ?? null,
                    format: $params['format'] ?? null,
                );
                $response = $manager->process_action($action);
                if ($response->get_success()) {
                    $draftfile = $response->get_response_data()['draftfile'];
                    $audiourl = module_helper::copy_file(
                        $chapterid,
                        $draftfile,
                        $this->context->id,
                        'mod_book',
                        'chapter'
                    );
                }
                if (!empty($entry['meta']['ex'])) {
                    $example = $entry['meta']['ex']; // Example provided in the wordlist.
                    $data['example_en'] = $example;
                } else {
                    $example = $data['example_en'] ?? '';
                }
                if (!empty($example)) {
                    $action = new convert_text_to_speech(
                        contextid: $this->context->id,
                        userid: $this->user->id,
                        texttoread: "{$example}",
                        voice: $params['voice'] ?? null,
                        format: $params['format'] ?? null,
                    );
                    $response = $manager->process_action($action);
                    if ($response->get_success()) {
                        $draftfile = $response->get_response_data()['draftfile'];
                        $audiourlexample = module_helper::copy_file(
                            $chapterid,
                            $draftfile,
                            $this->context->id,
                            'mod_book',
                            'chapter'
                        );
                        $data['audiourlexample'] = $audiourlexample;
                    }
                }

                $data['imageurl'] = $imageurl ? $imageurl->out(false) : '';
                $data['audiourl'] = $audiourl ? $audiourl->out(false) : '';
                $data['imagewidth'] = $imagewidth;
                $data['imageheight'] = $imageheight;
                $data['word'] = $word;
                $description = $OUTPUT->render_from_template(
                    'aiplacement_callextensions/mod_book/book_chapter',
                    $data
                );
                $description = file_rewrite_pluginfile_urls(
                    $description,
                    'pluginfile.php',
                    $this->context->id,
                    'mod_book',
                    'chapter',
                    $chapterid,
                );
                $DB->set_field('book_chapters', 'content', $description, ['id' => $chapterid]);
            }
            $aiaction->set_progress_status((int) (($currentindex + 1) / $totalwords * 100));
            $aiaction->save();
        }
    }
}
