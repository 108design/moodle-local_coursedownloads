<?php
namespace local_coursefiles\components;

use dml_exception;
use local_coursefiles\course_file;

class mod_h5pactivity extends course_file {
    /** Native package URL includes the revision slot (zero is accepted by Moodle). */
    protected function get_file_download_url(): ?\moodle_url {
        if ($this->file->filearea === 'package') {
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
        // File areas = intro, package.
        if ($this->file->filearea === 'package') {
            return true;
        }
        return parent::is_file_used();
    }
}
