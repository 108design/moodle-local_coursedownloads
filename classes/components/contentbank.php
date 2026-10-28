<?php
/**
 * This file is part of Moodle - https://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <https://www.gnu.org/licenses/>.
 *
 * File documentation for classes/components/contentbank.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_coursedownloads\components;

use dml_exception;
use file_storage;
use local_coursedownloads\course_file;
use moodle_exception;
use moodle_url;
use stored_file;

class contentbank extends course_file {
    /**
     * Show the name of the file as it appears in content bank
     *
     * @return string
     * @throws dml_exception
     */
    protected function get_displayed_filename() : string {
        global $DB;
        $cb = $DB->get_record('contentbank_content', ['id' => $this->file->itemid]);
        return $cb->name;
    }

    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        return $this->get_standard_file_download_url();
    }

    /**
     * Try to get the url for the component (module or course).
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_component_url() : ?moodle_url {
        return new moodle_url('/contentbank/index.php', ['contextid' => $this->file->contextid]);
    }

    /**
     * Not checking content bank
     *
     * @return null
     * @throws moodle_exception
     */
    protected function is_file_used() : ?bool {
        $fs = new file_storage();
        $f = new stored_file($fs, $this->file);
        return $fs->get_references_count_by_storedfile($f) > 1;
    }
}
