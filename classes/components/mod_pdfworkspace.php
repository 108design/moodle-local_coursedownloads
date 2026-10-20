<?php
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
