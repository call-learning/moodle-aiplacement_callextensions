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

namespace aiplacement_callextensions\local\helpers;


/**
 * Helper class for word list management.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wordlist_helper {
    /**
     * Validate the wordlist input.
     *
     * @param string $wordlist The wordlist input.
     * @return array An array of error messages, empty if valid.
     */
    public static function validate_wordlist(string $wordlist): array {
        $errors = [];
        if (empty(trim($wordlist))) {
            $errors[] = get_string('wordlistrequired', 'aiplacement_callextensions');
        }
        $words = array_filter(array_map('trim', explode("\n", $wordlist)));
        if (empty($words)) {
            $errors[] = get_string('wordlistatleastone', 'aiplacement_callextensions');
        }
        ['ok' => $ok, 'errors' => $parsingerrors] = self::parse_wordlist($wordlist);
        return array_merge($errors, $parsingerrors);
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
    private static function parse_wordlist(string $text, array $options = []): array {
        $opts = array_merge([
            'allowed_keys' => ['def', 'ex', 'pos', 'note'],
            'max_word_len' => 100,
            'max_value_len' => 1000,
            'disallow_empty_values' => true,
        ], $options);

        $errors = [];
        $entries = [];
        $seenwords = [];
        $allwords = preg_split('/\R/u', $text);
        natsort($allwords);
        foreach ($allwords as $lineno => $raw) {
            $line = trim($raw);
            if ($line === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            $result = self::parse_word($line, $lineno + 1, $opts);
            if (!$result['success']) {
                $errors = array_merge($errors, $result['errors']);
                continue;
            }

            $entry = $result['entry'];
            $keyci = mb_strtolower($entry['word']);

            if (isset($seenwords[$keyci])) {
                $errors[] = get_string('duplicateword', 'aiplacement_callextensions', [
                    'word' => $entry['word'], 'line1' => $seenwords[$keyci], 'line2' => $lineno + 1,
                ]);
                continue;
            }

            $seenwords[$keyci] = $lineno + 1;
            $entries[] = $entry;
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'entries' => $entries];
    }

    /**
     * Parse a single word line in the format: word=french (key1:value1, key2:value2, ...)
     *
     * @param string $wordinline The line to parse
     * @param int $lineno The line number for error reporting
     * @param array $options Parsing options
     * @return array Result with 'success', 'entry', and 'errors'
     */
    public static function parse_word(string $wordinline, int $lineno, array $options = []): array {
        $options = array_merge([
            'allowed_keys' => ['def', 'ex', 'pos', 'note'],
            'max_word_len' => 100,
            'max_value_len' => 1000,
            'disallow_empty_values' => true,
        ], $options);
        $errors = [];

        $result = self::extract_words_parent_block($wordinline);
        if ($result === null) {
            return [
                'success' => false,
                'errors' => [get_string('unbalancedparentheses', 'aiplacement_callextensions', $lineno)],
                'entry' => null,
            ];
        }
        [$head, $parentinside] = $result;
        [$word, $french] = strpos($head, '=') !== false ? explode('=', $head, 2) : [$head, null];
        $word = trim($word);
        $french = $french !== null ? trim($french) : null;

        if ($word === '' || mb_strlen($word) > $options['max_word_len']) {
            $errors[] = get_string('wordtoolong', 'aiplacement_callextensions', ['line' => $lineno]);
        }

        $meta = [];
        if ($parentinside) {
            foreach (str_getcsv($parentinside, ',', '"', '\\') as $item) {
                [$k, $v] = strpos($item, ':') !== false ? explode(':', $item, 2) : [null, null];
                $k = trim($k);
                $v = trim($v);

                if (!$k || !in_array($k, $options['allowed_keys'], true) || ($options['disallow_empty_values'] && $v === '')) {
                    $errors[] = get_string('invalidmetakey', 'aiplacement_callextensions', ['line' => $lineno]);
                    continue;
                }
                $meta[$k] = $v;
            }
        }

        if (
            $french !== null &&
            (mb_strlen($french) > $options['max_value_len'] || ($options['disallow_empty_values'] && $french === ''))
        ) {
            $errors[] = get_string('frenchvaluetoolong', 'aiplacement_callextensions', ['line' => $lineno]);
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'entry' => null];
        }

        return [
            'success' => true,
            'errors' => [],
            'entry' => [
                'word' => $word,
                'french' => $french ?: null,
                'meta' => $meta,
                '_line' => $lineno,
            ],
        ];
    }

    /**
     * Extract a single trailing "(...)" block if present, honoring quotes.
     *
     * @param string $line The line to parse
     * @return array|null Array with [head, parentinside] or null if unbalanced quotes/parentheses
     */
    private static function extract_words_parent_block(string $line): ?array {
        $line = rtrim($line);
        $lastclose = strrpos($line, ')');
        if ($lastclose === false) {
            return [$line, null];
        }

        $inquotes = $escape = false;
        $stack = $lastopen = 0;

        for ($i = 0, $len = strlen($line); $i < $len; $i++) {
            $ch = $line[$i];
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($ch === '\\') {
                $escape = true;
            } else if ($ch === '"') {
                $inquotes = !$inquotes;
            } else if (!$inquotes) {
                if ($ch === '(') {
                    if ($stack === 0) {
                        $lastopen = $i;
                    }
                    $stack++;
                } else if ($ch === ')' && $stack > 0) {
                    $stack--;
                    if ($stack === 0 && rtrim(substr($line, $i + 1)) === '') {
                        return [rtrim(substr($line, 0, $lastopen)), substr($line, $lastopen + 1, $i - $lastopen - 1)];
                    }
                }
            }
        }

        if ($inquotes) {
            return null;
        }
        return [$line, null];
    }
}
