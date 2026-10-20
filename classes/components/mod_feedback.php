<?php
namespace local_coursefiles\components;

use dml_exception;
use local_coursefiles\course_file;
use moodle_url;

class mod_feedback extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        switch ($this->file->filearea) {
            case 'item':
                return $this->get_standard_file_download_url();
            case 'page_after_submit':
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
        // File areas = intro, item, page_after_submit.
        global $DB;
        switch ($this->file->filearea) {
            case 'item':
                $item = $DB->get_record('feedback_item', ['id' => $this->file->itemid]);
                return $this->is_embedded_file_used($item, 'presentation', $this->file->filename);
            case 'page_after_submit':
                $sql = "SELECT m.*
                          FROM {feedback} m
                          JOIN {course_modules} cm ON cm.instance = m.id
                          JOIN {context} ctx ON ctx.instanceid = cm.id
                         WHERE ctx.id = ?";
                $feedback = $DB->get_record_sql($sql, [$this->file->contextid]);
                return $this->is_embedded_file_used($feedback, 'page_after_submit', $this->file->filename);
            default:
                return parent::is_file_used();
        }
    }
}
