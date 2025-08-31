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

use core\hook\output\after_http_headers;
use core\hook\output\before_footer_html_generation;

/**
 * AI action persisting class.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_action extends \core\persistent {

    /** @var string The table name. */
    public const TABLE = 'aiplacement_callextensions_aiaction';

    /** @var int Action status: Running */
    public const STATUS_RUNNING = 0;

    /** @var int Action status: Cancelled */
    public const STATUS_CANCELLED = 1;

    /** @var int Action status: Finished */
    public const STATUS_FINISHED = 2;

    /** @var int Action status: Error */
    public const STATUS_ERROR = 3;

    /** @var int Action status: Pending */
    public const STATUS_PENDING = 4;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'userid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'contextid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'actionname' => [
                'type' => PARAM_TEXT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'actiondata' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => "{}",
            ],
            'status' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => self::STATUS_RUNNING,
            ],
            'statustext' => [
                'type' => PARAM_RAW,
                'null' => NULL_ALLOWED,
                'default' => "",
            ],
            'progress' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => 0,
            ],
        ];
    }

    /**
     * Hook to execute before a create.
     *
     * @return void
     */
    protected function before_create() {
        $this->convert_actiondata_to_json();
    }

    /**
     * Hook to execute before an update.
     *
     * @return void
     */
    protected function before_update() {
        $this->convert_actiondata_to_json();
    }

    /**
     * Convert actiondata to JSON if needed.
     *
     * @return void
     */
    private function convert_actiondata_to_json() {
        $actiondata = $this->raw_get('actiondata');
        if ($actiondata !== null && !is_string($actiondata)) {
            $this->raw_set('actiondata', json_encode($actiondata));
        }
    }

    /**
     * Custom getter for actiondata.
     *
     * @return array|null
     */
    protected function get_actiondata() {
        $value = $this->raw_get('actiondata');
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
        }
        return $value;
    }
    /**
     * Get the status as a language string.
     *
     * @return string
     */
    public function get_status_string() {
        $status = $this->raw_get('status');
        switch ($status) {
            case self::STATUS_RUNNING:
                return get_string('action:status_running', 'aiplacement_callextensions');
            case self::STATUS_CANCELLED:
                return get_string('action:status_cancelled', 'aiplacement_callextensions');
            case self::STATUS_FINISHED:
                return get_string('action:status_finished', 'aiplacement_callextensions');
            case self::STATUS_ERROR:
                return get_string('action:status_error', 'aiplacement_callextensions');
            case self::STATUS_PENDING:
                return get_string('action:status_pending', 'aiplacement_callextensions');
            default:
                return get_string('action:status_unknown', 'aiplacement_callextensions');
        }
    }

    /**
     * Set the progress and optionally the status text, then save the record.
     *
     * @param int|null $progress The progress value (0-100) or null to unset.
     * @param string|null $statustext Optional status text to set.
     * @return void
     */
    public function set_progress_status(?int $progress = null, ?string $statustext = null): void {
        if (!is_null($progress)) {
            $progress = max(0, min(100, $progress));
            $this->raw_set('progress', $progress);
        }
        if (!is_null($statustext)) {
            $this->raw_set('statustext', $statustext);
        }
        $this->save();
    }
}
