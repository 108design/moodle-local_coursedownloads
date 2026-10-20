<?php
namespace local_coursefiles\components;

use dml_exception;
use local_coursefiles\course_file;
use moodle_url;

class mod_glossary extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        switch ($this->file->filearea) {
            case 'entry':
            case 'attachment':
                return $this->get_standard_file_download_url();
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
        // File areas = intro, chapter.
        global $DB;
        switch ($this->file->filearea) {
            case 'attachment':
                return true;
            case 'entry':
                $entry = $DB->get_record('glossary_entries', ['id' => $this->file->itemid]);
                return $this->is_embedded_file_used($entry, 'definition', $this->file->filename);
            default:
                return parent::is_file_used();
        }
    }
}
