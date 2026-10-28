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
 * File documentation for classes/components/mod_resource.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_coursedownloads\components;

use dml_exception;
use local_coursedownloads\course_file;
use moodle_url;

class mod_resource extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        if ($this->file->filearea === 'content') {
            return $this->get_standard_file_download_url();
        }
        return parent::get_file_download_url();
    }

    /**
     * Checks if embedded files have been used
     *
     * @return bool|null
     * @throws dml_exception
     */
    protected function is_file_used() : ?bool {
        // File areas = intro, chapter.
        if ($this->file->filearea === 'content') {
            return true;
        }
        return parent::is_file_used();
    }
}
