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
 * File documentation for classes/components/mod_pdfworkspace.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursedownloads\components;

defined('MOODLE_INTERNAL') || die();

/** Optional PDF Workspace originals; authorisation is shared with both archive paths. */
class mod_pdfworkspace extends \local_coursedownloads\course_file {
    protected function get_file_download_url(): ?\moodle_url {
        if (!has_capability('local/coursedownloads:download', \context_course::instance($this->courseid))) {
            return null;
        }
        return new \moodle_url('/local/coursedownloads/download.php',
            ['courseid' => $this->courseid, 'fileid' => $this->file->id]);
    }

    protected function is_file_used(): ?bool {
        return true; // The access policy has verified a registered original document.
    }
}
