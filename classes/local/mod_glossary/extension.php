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
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;
use local_aixtension\aiactions\convert_text_to_speech;
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
                '512x512' => '512x512',
                '1024x1024' => '1024x1024',
            ]
        );
        $mform->setType('imagesize', PARAM_TEXT);
        $mform->setDefault('imagesize', '512x512');
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

    #[\Override]
    public function actiondata_to_string(ai_action $action): string {
        $data = $action->get('actiondata') ?? [];

        if ($action->get('actionname') === 'glossary_generate_definitions') {
            $wordlist = $data['wordlist'] ?? [];
            $content = get_string('glossary_generate_definitions:wordlistinfo', 'aiplacement_callextensions', implode(', ', $wordlist));
        }
        return $content;
    }
    /**
     * Process generate definition action.
     *
     * @param object $data The form data.
     * @return array The processed result.
     */
    private function process_generate_definition(object $data): array {
        $concept = trim($data->wordlist ?? '');
        $context = trim($data->context ?? '');

        if (empty($concept)) {
            return [
                'success' => false,
                'message' => get_string('conceptrequired', 'aiplacement_callextensions'),
            ];
        }
        $prompt = "Générez une définition claire et concise pour le concept suivant : \"{$concept}\"";
        if (!empty($context)) {
            $prompt .= "\n\nContexte : {$context}";
        }
        $data = [
            'wordlist' => array_map('trim', explode("\n", $concept)),
            'prompt' => $prompt,
        ];
        $this->launch_action('glossary_generate_definitions', $data);
        return [
            'success' => true,
            'data' => $data,
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
        $prompt = trim($data->prompt ?? '');

        if (empty($prompt)) {
            return [
                'success' => false,
                'error' => get_string('promptrequired', 'aiplacement_callextensions'),
            ];
        }

        return [
            'success' => true,
            'prompt' => $prompt,
            'action_type' => $action,
        ];
    }

    #[\Override]
    public function validate_action_data(array $data, array $files, string $actionname): array {
        $errors = [];
        switch ($actionname) {
            case 'glossary_generate_definitions':
                if (empty(trim($data['wordlist'] ?? ''))) {
                    $errors['wordlist'] =
                        get_string('glossary_generate_definitions:wordlistrequired', 'aiplacement_callextensions');
                }
                // Check that there is at least one word in the list.
                $words = array_filter(array_map('trim', explode("\n", $data['wordlist'])));
                if (empty($words)) {
                    $errors['wordlist'] =
                        get_string('glossary_generate_definitions:wordlistatleastone', 'aiplacement_callextensions');
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
        if ($aiaction->get('actionname') !== 'glossary_generate_definitions') {
            return;
        }
        $params = $aiaction->get('actiondata') ?? [];
        $wordlist = $params['wordlist'] ?? '';
        [$imagewidth, $imageheight] = explode('x', $params['imagesize'] ?? '512x512');
        $imagewidth = $imagewidth ?: 512;
        $imageheight = $imageheight ?: 512;

        // Process each word.
        $manager = new \core_ai\manager();
        $glossarycm = get_coursemodule_from_id('glossary', $this->context->instanceid, 0, false, IGNORE_MISSING);
        $glossaryid = $glossarycm->instance;
        $totalwords = count($wordlist);
        $currentindex = 0;
        $textproompt = $params['textprompt'] ?? get_string('glossary_generate_definitions:textpromptdefault', 'aiplacement_callextensions');
        $imageprompt = $params['imageprompt'] ?? get_string('glossary_generate_definitions:imagepromptdefault', 'aiplacement_callextensions');
        foreach ($wordlist as $word) {
            $word = trim($word);
            if (!empty($word)) {
                $aiaction->set('statustext', get_string('glossary_generate_definitions:processingword', 'aiplacement_callextensions', $word));
                $aiaction->save();
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
                $description = '';
                if ($response->get_success()) {
                    $description = $response->get_response_data()['generatedcontent'];
                    if (json_decode($description) !== null) {
                        $description = json_decode($description, true);
                        $description = $OUTPUT->render_from_template(
                            'aiplacement_callextensions/mod_glossary/glossary_entry',
                            $description
                        );
                    }
                } else {
                    $description = "<p>$word</p>";
                }
                $action = new \core_ai\aiactions\generate_image(
                    contextid: $this->context->id,
                    userid: $this->user->id,
                    prompttext: "{$imageprompt}\n\nWORD\n{$word}",
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

                if ($imageurl) {
                    $description .= "<br><img src='{$imageurl->out()}' alt='{$word}' width=\"$imagewidth\" height=\"$imageheight\">";
                }
                if ($audiourl) {
                    $description .= "<hr><audio controls><source src='{$audiourl->out()}' type='audio/mpeg'>Your browser does not support the audio element.</audio>";
                }
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
            $aiaction->set('progress', (int) (($currentindex + 1) / $totalwords * 100));
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
