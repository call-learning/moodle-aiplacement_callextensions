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

namespace aiplacement_callextensions\local\mod_glossary;

use aiplacement_callextensions\ai_action;
use aiplacement_callextensions\local\base;
use aiplacement_callextensions\utils;
use core\exception\moodle_exception;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;
use local_aixtension\aiactions\convert_text_to_speech;
use Matrix\Exception;
use moodle_url;

/**
 * Output handler for the glossary assist UI.
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
                'glossary',
                ['mod/glossary:manageentries'],
                ['mod-glossary-view'],
                $hook->renderer->get_page(),
            )
        ) {
            return;
        }

        $context = [
            "cmid" => $this->context->instanceid,
            'contextid' => $this->context->id,
            "actionname" => 'glossary_generate_definitions',
            "component" => 'mod_glossary',
            "buttontext" => get_string('action:glossary_generate_definitions', 'aiplacement_callextensions'),
            "buttonlabel" => get_string('action:glossary_generate_definitions', 'aiplacement_callextensions'),
        ];
        $html = $hook->renderer->render_from_template('aiplacement_callextensions/action_button', $context);
        $hook->add_html($html);
    }

    #[\Override]
    public function add_action_form_definitions(\MoodleQuickForm $mform, string $action): void {
        $mform->addElement(
            'textarea',
            'wordlist',
            get_string('glossary_generate_definitions:wordlist', 'aiplacement_callextensions'),
            ['rows' => 10, 'cols' => 50]
        );
        $mform->setType('wordlist', PARAM_TEXT);
        $mform->addHelpButton('wordlist', 'glossary_generate_definitions:wordlist', 'aiplacement_callextensions');
        // Additional elements for context and optional parameters.
        $mform->addElement(
            'header',
            'textpromptheader',
            get_string('glossary_generate_definitions:textpromptheader', 'aiplacement_callextensions')
        );
        $mform->setExpanded('textpromptheader', false);
        $mform->addElement(
            'textarea',
            'textprompt',
            get_string('glossary_generate_definitions:textprompt', 'aiplacement_callextensions'),
            ['rows' => 3, 'cols' => 50]
        );
        $mform->setType('textprompt', PARAM_TEXT);
        $mform->setDefault(
            'textprompt',
            get_string('glossary_generate_definitions:textpromptdefault', 'aiplacement_callextensions')
        );

        // Additional elements for image generation.
        $mform->addElement(
            'header',
            'imagepromptheader',
            get_string('glossary_generate_definitions:imagepromptheader', 'aiplacement_callextensions')
        );
        $mform->setExpanded('imagepromptheader', false);
        $mform->addElement(
            'textarea',
            'imageprompt',
            get_string('glossary_generate_definitions:imageprompt', 'aiplacement_callextensions'),
            ['rows' => 3, 'cols' => 50]
        );
        $mform->setType('imageprompt', PARAM_TEXT);
        $mform->setDefault(
            'imageprompt',
            get_string('glossary_generate_definitions:imagepromptdefault', 'aiplacement_callextensions')
        );
        $mform->addElement(
            'select',
            'imagesize',
            get_string('glossary_generate_definitions:imagesize', 'aiplacement_callextensions'),
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
            get_string('glossary_generate_definitions:soundparamheader', 'aiplacement_callextensions')
        );
        $mform->setExpanded('soundparamheader', false);
        $mform->addElement(
            'select',
            'voice',
            get_string('glossary_generate_definitions:voice', 'aiplacement_callextensions'),
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
            case 'glossary_generate_definitions':
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
        $wordlist = array_values(array_unique($wordlist));
        $launchdata['wordlist'] = $wordlist;
        $this->launch_action('glossary_generate_definitions', $launchdata);
        return [
            'success' => true,
            'data' => $launchdata,
            'message' => get_string('glossary_generate_definitions:actionstarted', 'aiplacement_callextensions'),
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

        if ($action->get('actionname') === 'glossary_generate_definitions') {
            $wordlist = $data['wordlist'] ?? [];
            $content = get_string(
                'glossary_generate_definitions:wordlistinfo',
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
            case 'glossary_generate_definitions':
                $wordlisterrors = $this->validate_wordlist($data['wordlist'] ?? '');
                if (!empty($wordlisterrors)) {
                    $errors['wordlist'] = join( " ", $wordlisterrors);
                }
                break;
            default:
                break;
        }
        return $errors;
    }

    /**
     * Validate the wordlist input.
     *
     * @param string $wordlist The wordlist input.
     * @return array An array of error messages, empty if valid.
     */
    private function validate_wordlist(string $wordlist): array {
        $errors = [];
        if (empty(trim($wordlist ?? ''))) {
            $errors[] =
                get_string('glossary_generate_definitions:wordlistrequired', 'aiplacement_callextensions');
        }
        // Check that there is at least one word in the list.
        $words = array_filter(array_map('trim', explode("\n", $wordlist)));
        if (empty($words)) {
            $errors[] =
                get_string('glossary_generate_definitions:wordlistatleastone', 'aiplacement_callextensions');
        }
        ['ok' => $ok, 'errors' => $parsingerrors] = $this->parse_wordlist($wordlist);
        $errors = array_merge($errors, $parsingerrors);
        return $errors;
    }

    /**
     * Parse a wordlist in the format: word=french (key1:value1, key2:value2, ...)
     *
     *  Accepts lines like:
     *    word=french
     *    word(def:definition,ex:example)
     *    word=french(def:definition,ex:example)
     *  Values in (...) may be quoted with " to allow commas/parentheses.
     *
     *  Validation checks:
     *   - word must exist, trimmed, and be unique
     *   - at least one of {french, def, ex, ...meta} must be present
     *   - only allowed keys inside (...) (configurable)
     *   - no empty keys or empty values
     *   - balanced trailing (...) with CSV-style items key:value
     *   - optional max length constraints (configurable)
     *   - flags suspicious patterns (e.g., missing colon in meta, stray commas
     *
     * @return array The parsed result with 'ok', 'errors', 'warnings', and 'entries'.
     */
    private function parse_wordlist(string $text, array $options = []): array {
        $opts = array_merge([
            'allowed_keys' => ['def', 'ex', 'pos', 'note'],
            'max_word_len' => 100,
            'max_value_len' => 1000,
            'disallow_empty_values' => true,
        ], $options);

        $errors = [];
        $warnings = [];
        $entries = [];
        $seenwords = [];

        foreach (preg_split('/\R/u', $text) as $lineno => $raw) {
            $line = trim($raw);
            if ($line === '' || str_starts_with(ltrim($line), '#')) continue;

            $result = $this->parse_word($line, $lineno + 1, $opts);

            if (!$result['success']) {
                $errors = array_merge($errors, $result['errors']);
                continue;
            }

            $entry = $result['entry'];
            $keyci = mb_strtolower($entry['word']);

            if (isset($seenwords[$keyci])) {
                $errors[] = get_string('glossary_generate_definitions:duplicateword', 'aiplacement_callextensions', [
                    'word' => $entry['word'], 'line1' => $seenwords[$keyci], 'line2' => $lineno + 1,
                ]);
                continue;
            }

            $seenwords[$keyci] = $lineno + 1;
            $entries[] = $entry;
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'warnings' => $warnings, 'entries' => $entries];
    }


    /**
     * Parse a single word line in the format: word=french (key1:value1, key2:value2, ...)
     *
     * @param string $wordinline The line to parse
     * @param int $lineno The line number for error reporting
     * @param array $options Parsing options
     * @return array Result with 'success', 'entry', and 'errors'
     */
    private function parse_word(string $wordinline, int $lineno, array $options = []): array {
        $options = array_merge([
            'allowed_keys' => ['def', 'ex', 'pos', 'note'],
            'max_word_len' => 100,
            'max_value_len' => 1000,
            'disallow_empty_values' => true,
        ], $options);
        $errors = [];

        $result = $this->extract_words_parent_block($wordinline);
        if ($result === null) {
            return [
                'success' => false,
                'errors' => [get_string('glossary_generate_definitions:unbalancedparentheses', 'aiplacement_callextensions', $lineno)],
                'entry' => null
            ];
        }
        [$head, $parentinside] = $result;
        [$word, $french] = strpos($head, '=') !== false ? explode('=', $head, 2) : [$head, null];
        $word = trim($word);
        $french = $french !== null ? trim($french) : null;

        if ($word === '' || mb_strlen($word) > $options['max_word_len']) {
            $errors[] = get_string('glossary_generate_definitions:wordtoolong', 'aiplacement_callextensions', ['line' => $lineno]);
        }

        $meta = [];
        if ($parentinside) {
            foreach (str_getcsv($parentinside, ',', '"', '\\') as $item) {
                [$k, $v] = strpos($item, ':') !== false ? explode(':', $item, 2) : [null, null];
                $k = trim($k);
                $v = trim($v);

                if (!$k || !in_array($k, $options['allowed_keys'], true) || ($options['disallow_empty_values'] && $v === '')) {
                    $errors[] = get_string('glossary_generate_definitions:invalidmetakey', 'aiplacement_callextensions', ['line' => $lineno]);
                    continue;
                }
                $meta[$k] = $v;
            }
        }

        if ($french !== null && (mb_strlen($french) > $options['max_value_len'] || ($options['disallow_empty_values'] && $french === ''))) {
            $errors[] = get_string('glossary_generate_definitions:frenchvaluetoolong', 'aiplacement_callextensions', ['line' => $lineno]);
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'entry' => null];
        }

        return [
            'success' => true,
            'errors' => [],
            'entry' => [
                'word' => $word,
                'french' => $french ?: null, // For now we will ignore the french translation.
                'meta' => $meta,
                '_line' => $lineno,
            ]
        ];
    }
    /**
     * Extract a single trailing "(...)" block if present, honoring quotes.
     *
     * @param string $line The line to parse
     * @return array|null Array with [head, parentinside] or null if unbalanced quotes/parentheses
     */
    function extract_words_parent_block(string $line): ?array {
        $line = rtrim($line);
        $lastclose = strrpos($line, ')');
        if ($lastclose === false) return [$line, null];

        $inquotes = $escape = false;
        $stack = $lastopen = 0;

        for ($i = 0, $len = strlen($line); $i < $len; $i++) {
            $ch = $line[$i];
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') $escape = true;
            elseif ($ch === '"') $inquotes = !$inquotes;
            elseif (!$inquotes) {
                if ($ch === '(') {
                    if ($stack === 0) $lastopen = $i;
                    $stack++;
                } elseif ($ch === ')' && $stack > 0) {
                    $stack--;
                    if ($stack === 0 && rtrim(substr($line, $i + 1)) === '') {
                        return [rtrim(substr($line, 0, $lastopen)), substr($line, $lastopen + 1, $i - $lastopen - 1)];
                    }
                }
            }
        }

        if ($inquotes) return null; // Return null instead of throwing exception
        return [$line, null];
    }

    #[\Override]
    public function execute_action(ai_action $aiaction): void {
        global $DB, $OUTPUT;
        if ($aiaction->get('actionname') !== 'glossary_generate_definitions') {
            return;
        }
        $params = $aiaction->get('actiondata') ?? [];
        $wordlist = $params['wordlist'] ?? '';
        [$imagewidth, $imageheight] = explode('x', $params['imagesize'] ?? '256x256');
        $imagewidth = $imagewidth ?: 256;
        $imageheight = $imageheight ?: 256;

        // Process each word.
        $manager = new \core_ai\manager();
        $glossarycm = get_coursemodule_from_id('glossary', $this->context->instanceid, 0, false, IGNORE_MISSING);
        $glossaryid = $glossarycm->instance;
        $totalwords = count($wordlist);
        $currentindex = 0;
        $textproompt =
            $params['textprompt'] ?? get_string('glossary_generate_definitions:textpromptdefault', 'aiplacement_callextensions');
        $imageprompt =
            $params['imageprompt'] ?? get_string('glossary_generate_definitions:imagepromptdefault', 'aiplacement_callextensions');
        foreach ($wordlist as $lineno => $wordfromlist) {
            // Word is maybe a bit of a complex structure so let's parse it properly.
            ['success' => $ok, 'errors' => $parsingerrors, 'entry' => $entry] = $this->parse_word($wordfromlist, $lineno + 1);
            if ($ok && $entry) {
                $word = $entry['word'];
                $aiaction->set_progress_status(
                    statustext: get_string(
                        'glossary_generate_definitions:processingword',
                        'aiplacement_callextensions',
                        $word
                    )
                );
                // Create a new glossary entry.
                $entryobj = new \stdClass();
                $entryobj->concept = trim($word);
                $entryobj->definition = "";
                $entryobj->definitionformat = FORMAT_HTML;
                $entryobj->glossaryid = $glossaryid;
                $entryobj->userid = $this->user->id;
                $entryobj->timecreated = time();
                $entryobj->timemodified = time();
                $entryid = $DB->insert_record('glossary_entries', $entryobj);
                $imageurl = null;
                $audiourl = null;

                $action = new \core_ai\aiactions\generate_text(
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
                            'glossary_generate_definitions:errorprocessingword',
                            'aiplacement_callextensions',
                            $word
                        )
                    );
                    continue;
                }
                // Here we try to be agnostic to exact model used... but if we use the openai_extension model this
                // should work better as dall-e 3 does not give good results for image generation.
                $action = new \core_ai\aiactions\generate_image(
                    contextid: $this->context->id,
                    userid: $this->user->id,
                    prompttext: "{$imageprompt}" . get_string(
                        'glossary_generate_definitions:imagepromptword',
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
                    $imageurl = $this->copy_file($entryid, $draftfile);
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
                    $audiourl = $this->copy_file($entryid, $draftfile);
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
                        $audiourlexample = $this->copy_file($entryid, $draftfile);
                        $data['audiourlexample'] = $audiourlexample;
                    }
                }

                $data['imageurl'] = $imageurl ? $imageurl->out(false) : '';
                $data['audiourl'] = $audiourl ? $audiourl->out(false) : '';
                $data['imagewidth'] = $imagewidth;
                $data['imageheight'] = $imageheight;
                $data['word'] = $word;
                $description = $OUTPUT->render_from_template(
                    'aiplacement_callextensions/mod_glossary/glossary_entry',
                    $data
                );
                $description = file_rewrite_pluginfile_urls(
                    $description,
                    'pluginfile.php',
                    $this->context->id,
                    'mod_glossary',
                    'entry',
                    $entryid,
                );
                $DB->set_field('glossary_entries', 'definition', $description, ['id' => $entryid]);
            }
            $aiaction->set_progress_status((int) (($currentindex + 1) / $totalwords * 100));
            $aiaction->save();
        }
    }

    /**
     * Add a file to a glossary entry.
     *
     * @param int $entryid The ID of the glossary entry.
     * @param \stored_file $draftfile The draft file to add.
     * @return moodle_url The URL of the added file.
     */
    protected function copy_file(int $entryid, \stored_file $draftfile): moodle_url {
        global $DB;
        // Copy the file to the glossary permanent area.
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $this->context->id,
            'component' => 'mod_glossary',
            'filearea' => 'entry',
            'itemid' => $entryid,
            'filepath' => '/',
            'filename' => $draftfile->get_filename(),
        ];
        $imagefile = $fs->create_file_from_storedfile($fileinfo, $draftfile);

        $url = moodle_url::make_pluginfile_url(
            $imagefile->get_contextid(),
            $imagefile->get_component(),
            $imagefile->get_filearea(),
            $imagefile->get_itemid(),
            $imagefile->get_filepath(),
            $imagefile->get_filename(),
            false                     // Do not force download of the file.
        );
        return $url;
    }
}
