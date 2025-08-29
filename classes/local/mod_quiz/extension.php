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

namespace aiplacement_callextensions\local\mod_quiz;


use aiplacement_callextensions\ai_action;
use aiplacement_callextensions\local\base;
use context_course;
use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;
use core_php_time_limit;
use core_tag_tag;
use qformat_gift;
use question_bank;

/**
 * Output handler for the quiz assist UI.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class extension extends base {
    /** @var \stdClass The quiz instance. */
    protected \stdClass $quiz;

    /** @var \stdClass The course module instance. */
    protected \stdClass $cm;

    /**
     * The quiz instance.
     *
     * @param int $contextid
     * @param int $userid
     * @throws \coding_exception
     */
    public function __construct(int $contextid, int $userid) {
        global $DB;
        parent::__construct($contextid, $userid);
        $this->cm = get_coursemodule_from_id('quiz', $this->context->instanceid, 0, false, MUST_EXIST);
        $this->quiz = $DB->get_record('quiz', ['id' => $this->cm->instance], '*', MUST_EXIST);
    }

    #[\Override]
    public function before_footer_html_generation(before_footer_html_generation $hook): void {
        $html = '';
        $hook->add_html($html);
    }

    #[\Override]
    public function after_http_headers(after_http_headers $hook): void {
        if (!has_capability('mod/quiz:manage', $this->context)) {
            return;
        }
        $context = [
            "cmid" => $this->context->instanceid,
            'contextid' => $this->context->id,
            "actionname" => 'quiz_generate_questions',
            "component" => 'mod_quiz',
            "buttontext" => get_string('action:quiz_generate_questions', 'aiplacement_callextensions'),
            "buttonlabel" => get_string('action:quiz_generate_questions', 'aiplacement_callextensions'),
        ];
        $html = $hook->renderer->render_from_template('aiplacement_callextensions/action_button', $context);
        $hook->add_html($html);
    }

    #[\Override]
    public function add_action_form_definitions(\MoodleQuickForm $mform, string $action): void {
        $mform->addElement(
            'textarea',
            'qcontext',
            get_string('quiz_generate_questions:context', 'aiplacement_callextensions'),
            ['rows' => 10, 'cols' => 50]
        );
        $mform->setType('qcontext', PARAM_TEXT);
        $mform->addHelpButton('qcontext', 'quiz_generate_questions:context', 'aiplacement_callextensions');
        // Additional elements for context and optional parameters.
        $mform->addElement(
            'header',
            'additionaloptionsheader',
            get_string('quiz_generate_questions:additionaloptionsheader', 'aiplacement_callextensions')
        );
        // Add number of quesitons to generate.
        $mform->addElement(
            'text',
            'numquestions',
            get_string('quiz_generate_questions:numquestions', 'aiplacement_callextensions'),
            ['size' => 5]
        );
        $mform->setType('numquestions', PARAM_INT);
        $mform->setDefault('numquestions', 5);
        $mform->addHelpButton('numquestions', 'quiz_generate_questions:numquestions', 'aiplacement_callextensions');
        // Add question difficulty.
        $mform->addElement(
            'select',
            'difficulty',
            get_string('quiz_generate_questions:difficulty', 'aiplacement_callextensions'),
            [
                'easy' => get_string('quiz_generate_questions:difficulty:easy', 'aiplacement_callextensions'),
                'medium' => get_string('quiz_generate_questions:difficulty:medium', 'aiplacement_callextensions'),
                'hard' => get_string('quiz_generate_questions:difficulty:hard', 'aiplacement_callextensions'),
            ]
        );

        $mform->setExpanded('textpromptheader', false);
        $mform->addElement(
            'textarea',
            'textprompt',
            get_string('quiz_generate_questions:textprompt', 'aiplacement_callextensions'),
            ['rows' => 3, 'cols' => 50]
        );
        $mform->setType('textprompt', PARAM_TEXT);
        $mform->setDefault(
            'textprompt',
            get_string('quiz_generate_questions:textpromptdefault', 'aiplacement_callextensions')
        );
        // Add the question category selector.
        $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
            array('contexts' => array($this->context)));

    }

    #[\Override]
    public function process_action_data(object $data, string $action): array {
        switch ($action) {
            case 'quiz_generate_questions':
                return $this->process_generate_questions($data);
            default:
                return $this->process_default_action($data, $action);
        }
    }

    #[\Override]
    public function actiondata_to_string(ai_action $action): string {
        $data = $action->get('actiondata') ?? [];

        if ($action->get('actionname') === 'quiz_generate_questions') {
            $context = $data['qcontext'] ?? "";
            $numberofquestions = $data['numquestions'] ?? 5;
            $content = get_string(
                'quiz_generate_questions:contextinfo',
                'aiplacement_callextensions',
                ['count' => $numberofquestions, 'context' => $context]
            );
        }
        return $content;
    }
    /**
     * Process generate definition action.
     *
     * @param object $data The form data.
     * @return array The processed result.
     */
    private function process_generate_questions(object $data): array {
        $context = trim($data->qcontext ?? '');

        if (empty($context)) {
            return [
                'success' => false,
                'message' => get_string('quiz_generate_questions:contextrequired', 'aiplacement_callextensions'),
            ];
        }
        $datakeys = [
            'qcontext', 'textprompt', 'difficulty', 'numquestions','category'
        ];
        $categoryfromtree = explode(",", $data->category) ?? [1];
        $data->category = (int) array_shift($categoryfromtree); // Get the category id (first item in the list).
        $launchdata = array_intersect_key((array) $data, array_flip($datakeys));
        $this->launch_action('quiz_generate_questions', $launchdata);
        return [
            'success' => true,
            'data' => $data,
            'message' => get_string('quiz_generate_questions:actionstarted', 'aiplacement_callextensions'),
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
        return [
            'success' => true,
            'action_type' => $action,
        ];
    }

    #[\Override]
    public function validate_action_data(array $data, array $files, string $actionname): array {
        $errors = [];
        switch ($actionname) {
            case 'quiz_generate_questions':
                if (empty(trim($data['qcontext'] ?? ''))) {
                    $errors['qcontext'] =
                        get_string('quiz_generate_questions:qcontextrequired', 'aiplacement_callextensions');
                }
                break;
            default:
                break;
        }
        return $errors;
    }

    #[\Override]
    public function execute_action(ai_action $aiaction): void {
        if ($aiaction->get('actionname') !== 'quiz_generate_questions') {
            return;
        }
        $params = $aiaction->get('actiondata') ?? [];
        $context = $params['qcontext'] ?? '';
        // Process each word.
        $manager = new \core_ai\manager();
        $currentindex = 0;
        $textprompt =
            $params['textprompt'] ?? get_string('quiz_generate_questions:textpromptdefault', 'aiplacement_callextensions');
        $numberofquestions = $params['numquestions'] ?? 5;
        $difficulty = $params['difficulty'] ?? 'medium';
        // Get default question category for quiz.
        for ($index = 0; $index < $numberofquestions; $index++) {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->context->id,
                userid: $this->user->id,
                prompttext: "{$textprompt}\n\nCONTEXT\n{$context}\n\nDIFFICULTY\n {$difficulty}",
            );
            $response = $manager->process_action($action);
            if ($response->get_success()) {
                $gift = $response->get_response_data()['generatedcontent'];
                $this->parse_and_add_question($gift, $params['category']);
            }

            $aiaction->set('progress', (int) (($currentindex + 1) / $numberofquestions * 100));
            $aiaction->save();
        }
    }

    /**
     * Parse a GIFT question and add it to the database.
     *
     * @param string $giftresult The GIFT formatted question string.
     * @param \stdClass $defaultcategoryid The default question category id.
     * @return bool True on success, false on failure.
     */
    protected function parse_and_add_question(string $giftresult, int $defaultcategoryid): bool {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/question/engine/lib.php');
        require_once($CFG->dirroot . '/question/type/questiontypebase.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/gift/format.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $gradeoptionsfull = question_bank::fraction_options_full();

        $lines = preg_split('/[\\n\\r]/', str_replace(["\r\n", "\r"], "\n", $giftresult));
        $importer = new qformat_gift();
        $question = $importer->readquestion($lines);
        if (!empty($question->fraction) and (is_array($question->fraction))) {
            $fractions = $question->fraction;
            $invalidfractions = [];
            foreach ($fractions as $key => $fraction) {
                $newfraction = match_grade_options($gradeoptionsfull, $fraction);
                if ($newfraction === false) {
                    $invalidfractions[] = $fraction;
                } else {
                    $fractions[$key] = $newfraction;
                }
            }
            if ($invalidfractions) {
                return false;
            } else {
                $question->fraction = $fractions;
            }
        }
        $transaction = $DB->start_delegated_transaction();
        core_php_time_limit::raise();
        $question->context = $this->context;
        $question->category = $defaultcategoryid;
        $question->stamp = make_unique_id_code();  // Set the unique code (not to be changed).
        $question->createdby = $USER->id;
        $question->timecreated = time();
        $question->modifiedby = $USER->id;
        $question->timemodified = time();
        if (isset($question->idnumber)) {
            if ((string) $question->idnumber === '') {
                // Id number not really set. Get rid of it.
                unset($question->idnumber);
            } else {
                if (
                    $DB->record_exists(
                        'question_bank_entries',
                        ['idnumber' => $question->idnumber, 'questioncategoryid' => $question->category]
                    )
                ) {
                    unset($question->idnumber);
                }
            }
        }

        $fileoptions = [
            'subdirs' => true,
            'maxfiles' => -1,
            'maxbytes' => 0,
        ];

        $question->id = $DB->insert_record('question', $question);
        // Create a bank entry for each question imported.
        $questionbankentry = new \stdClass();
        $questionbankentry->questioncategoryid = $question->category;
        $questionbankentry->idnumber = $question->idnumber ?? null;
        $questionbankentry->ownerid = $question->createdby;
        $questionbankentry->id = $DB->insert_record('question_bank_entries', $questionbankentry);
        // Create a version for each question imported.
        $questionversion = new \stdClass();
        $questionversion->questionbankentryid = $questionbankentry->id;
        $questionversion->questionid = $question->id;
        $questionversion->version = 1;
        $questionversion->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $questionversion->id = $DB->insert_record('question_versions', $questionversion);

        if (isset($question->questiontextitemid)) {
            $question->questiontext = file_save_draft_area_files(
                $question->questiontextitemid,
                $this->context->id,
                'question',
                'questiontext',
                $question->id,
                $fileoptions,
                $question->questiontext
            );
            file_clear_draft_area($question->questiontextitemid);
        } else if (isset($question->questiontextfiles)) {
            foreach ($question->questiontextfiles as $file) {
                question_bank::get_qtype($question->qtype)->import_file(
                    $this->context,
                    'question',
                    'questiontext',
                    $question->id,
                    $file
                );
            }
        }
        if (isset($question->generalfeedbackitemid)) {
            $question->generalfeedback = file_save_draft_area_files(
                $question->generalfeedbackitemid,
                $this->context->id,
                'question',
                'generalfeedback',
                $question->id,
                $fileoptions,
                $question->generalfeedback
            );
            file_clear_draft_area($question->generalfeedbackitemid);
        } else if (isset($question->generalfeedbackfiles)) {
            foreach ($question->generalfeedbackfiles as $file) {
                question_bank::get_qtype($question->qtype)->import_file(
                    $this->context,
                    'question',
                    'generalfeedback',
                    $question->id,
                    $file
                );
            }
        }
        $DB->update_record('question', $question);

        $this->questionids[] = $question->id;
        $result = question_bank::get_qtype($question->qtype)->save_question_options($question);
        $event = \core\event\question_created::create_from_question_instance($question, $this->context);
        $event->trigger();

        if (core_tag_tag::is_enabled('core_question', 'question')) {
            // Is the current context we're importing in a course context?
            $importingcontext = $this->context;
            $importingcoursecontext = $importingcontext->get_course_context(false);
            $isimportingcontextcourseoractivity = !empty($importingcoursecontext);

            if (!empty($question->coursetags)) {
                if ($isimportingcontextcourseoractivity) {
                    $mergedtags = array_merge($question->coursetags, $question->tags);

                    core_tag_tag::set_item_tags(
                        'core_question',
                        'question',
                        $question->id,
                        $question->context,
                        $mergedtags
                    );
                } else {
                    core_tag_tag::set_item_tags(
                        'core_question',
                        'question',
                        $question->id,
                        context_course::instance($this->course->id),
                        $question->coursetags
                    );

                    if (!empty($question->tags)) {
                        core_tag_tag::set_item_tags(
                            'core_question',
                            'question',
                            $question->id,
                            $importingcontext,
                            $question->tags
                        );
                    }
                }
            } else if (!empty($question->tags)) {
                core_tag_tag::set_item_tags(
                    'core_question',
                    'question',
                    $question->id,
                    $question->context,
                    $question->tags
                );
            }
        }

        if (!empty($result->error)) {
            $DB->force_transaction_rollback();
            return false;
        }
        if (!question_has_capability_on($question, 'use')) {
            return false;
        }
        quiz_add_quiz_question($question->id, $this->quiz);
        $transaction->allow_commit();
        return true;
    }
}
