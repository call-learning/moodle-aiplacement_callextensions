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

use aiplacement_callextensions\utils;
use core\context;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;
use moodle_page;
use renderer_base;

/**
 * Output handler for the glossary assist UI.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_glossary_extension extends base {
    /**
     * Adds any HTML to the page before the footer HTML is generated.
     *
     * @param before_footer_html_generation $hook
     */
    public function before_footer_html_generation(before_footer_html_generation $hook): void {
        $html = '';
        $hook->add_html($html);
    }

    /**
     * Add any HTML to the page after the HTTP headers have been sent.
     *
     * @param after_http_headers $hook
     */
    public function after_http_headers(after_http_headers $hook): void {
        $context = [
            "action" => "generate-glossary-definition",
            "cmid" => $this->context->instanceid,
            'contextid' => $this->context->id,
            "component" => 'mod_glossary',
            "buttontext" => get_string('glossary_generate_definitions', 'aiplacement_callextensions'),
            "buttonlabel" => get_string('glossary_generate_definitions', 'aiplacement_callextensions'),
        ];
        $html = $this->output->render_from_template('aiplacement_callextensions/action_button', $context);
        $hook->add_html($html);
    }

    /**
     * Check if the extension is enabled for the given context.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return utils::generic_preflight_checks($this->context) && utils::is_assist_available($this->context);
    }

    /**
     * Add action form definitions specific to glossary module.
     *
     * @param \MoodleQuickForm $mform The form object.
     * @param string $action The action being performed.
     * @param int|null $step The step number, if applicable.
     * @return void
     */
    public function add_action_form_definitions(\MoodleQuickForm $mform, string $action, ?int $step = null): void {
        switch ($action) {
            case 'generate-glossary-definition':
                switch($step) {
                    case 1:
                        $mform->addElement('text', 'concept', get_string('concept', 'glossary'));
                        $mform->setType('concept', PARAM_TEXT);
                        $mform->addRule('concept', get_string('required'), 'required', null, 'client');
                        $mform->addElement('select', 'wordtype', get_string('wordtype', 'aiplacement_callextensions'), [
                            'noun' => get_string('noun', 'aiplacement_callextensions'),
                            'verb' => get_string('verb', 'aiplacement_callextensions'),
                            'adjective' => get_string('adjective', 'aiplacement_callextensions'),
                        ]);
                        $mform->setType('wordtype', PARAM_TEXT);

                        $mform->addElement('text', 'wordcount', get_string('wordcount', 'aiplacement_callextensions'));
                        $mform->setType('wordcount', PARAM_INT);
                        $mform->addRule('wordcount', get_string('required'), 'required', null, 'client');
                        break;
                    case 2:
                        $mform->addElement('textarea', 'wordlist', get_string('wordlist', 'aiplacement_callextensions'), ['rows' => 10, 'cols' => 50]);
                        $mform->setType('wordlist', PARAM_TEXT);
                        break;
                    case 3:
                        $mform->addElement('html', '<div class="progress-report">' . get_string('progressreport', 'aiplacement_callextensions') . '</div>');
                        break;
                    case 4:
                        $mform->addElement('html', '<div class="results">' . get_string('results', 'aiplacement_callextensions') . '</div>');
                        break;
                    default:
                        throw new \moodle_exception('invalidstep', 'aiplacement_callextensions');
                }
                break;

            case 'improve_definition':
                $mform->addElement('textarea', 'existing_definition',
                    get_string('existing_definition', 'aiplacement_callextensions'),
                    ['rows' => 5, 'cols' => 50]);
                $mform->setType('existing_definition', PARAM_TEXT);
                $mform->addRule('existing_definition', get_string('required'), 'required', null, 'client');
                break;

            default:
                // Champs par défaut pour toutes les actions
                $mform->addElement('textarea', 'prompt', get_string('prompt', 'aiplacement_callextensions'),
                    ['rows' => 4, 'cols' => 50]);
                $mform->setType('prompt', PARAM_TEXT);
                break;
        }
    }

    /**
     * Process the form data for glossary-specific actions.
     *
     * @param object $data The form data.
     * @param string $action The action being performed.
     * @return array The processed result.
     */
    public function process_action_data(object $data, string $action): array {
        switch ($action) {
            case 'generate_definition':
                return $this->process_generate_definition($data);

            case 'improve_definition':
                return $this->process_improve_definition($data);

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
        $concept = trim($data->concept ?? '');
        $context = trim($data->context ?? '');

        if (empty($concept)) {
            return [
                'success' => false,
                'error' => get_string('conceptrequired', 'aiplacement_callextensions')
            ];
        }

        // Construire le prompt pour l'IA
        $prompt = "Générez une définition claire et concise pour le concept suivant : \"{$concept}\"";
        if (!empty($context)) {
            $prompt .= "\n\nContexte : {$context}";
        }

        return [
            'success' => true,
            'prompt' => $prompt,
            'action_type' => 'generate_definition',
            'concept' => $concept,
            'context' => $context
        ];
    }

    /**
     * Process improve definition action.
     *
     * @param object $data The form data.
     * @return array The processed result.
     */
    private function process_improve_definition(object $data): array {
        $existing_definition = trim($data->existing_definition ?? '');

        if (empty($existing_definition)) {
            return [
                'success' => false,
                'error' => get_string('definitionrequired', 'aiplacement_callextensions')
            ];
        }

        // Construire le prompt pour améliorer la définition
        $prompt = "Améliorez la définition suivante en la rendant plus claire, précise et complète :\n\n{$existing_definition}";

        return [
            'success' => true,
            'prompt' => $prompt,
            'action_type' => 'improve_definition',
            'original_definition' => $existing_definition
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
                'error' => get_string('promptrequired', 'aiplacement_callextensions')
            ];
        }

        return [
            'success' => true,
            'prompt' => $prompt,
            'action_type' => $action
        ];
    }
}
