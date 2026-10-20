<?php
namespace local_coursefiles\components;

use dml_exception;
use local_coursefiles\course_file;
use moodle_url;

class mod_assign extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        switch ($this->file->filearea) {
            case 'introattachment':
            case 'activityattachment':
                return $this->get_standard_file_download_url();
            case 'intro':
                return $this->get_standard_file_download_url(false);
            default:
                return parent::get_file_download_url();
        }
    }

    /**
     * Checks if embedded files have been used
     *
     * @return bool|null
     * @throws dml_exception
     */
    protected function is_file_used() : ?bool {
        // File areas = intro, introattachment.
        if ($this->file->filearea === 'introattachment') {
            return true;
        }
        return parent::is_file_used();
    }
}
