<?php
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
