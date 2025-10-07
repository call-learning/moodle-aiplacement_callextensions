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


use moodle_url;
use stored_file;

/**
 * Helper class for wordlist kind of modules.
 *
 * @package    aiplacement_callextensions
 * @copyright  2025 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_helper {
    /**
     * Add a file to a book entry.
     *
     * @param int $entryid The ID of the book entry.
     * @param stored_file $draftfile The draft file to add.
     * @param int $contextid The context ID where the file will be stored.
     * @param string $component The component name (e.g., 'mod_book').
     * @param string $filearea The file area name (e.g., 'chapter').
     * @return moodle_url The URL of the added file.
     */
    public static function copy_file(
        int $entryid,
        stored_file $draftfile,
        int $contextid,
        string $component,
        string $filearea
    ): moodle_url {
        // Copy the file to the book permanent area.
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $contextid,
            'component' => $component,
            'filearea' => $filearea,
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
            false // Do not force download of the file.
        );
        return $url;
    }
}
