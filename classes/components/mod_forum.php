<?php
namespace local_coursefiles\components;

use dml_exception;
use local_coursefiles\course_file;
use moodle_url;

class mod_forum extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        switch ($this->file->filearea) {
            case 'post':
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
        // File areas = intro, post.
        global $DB;
        switch ($this->file->filearea) {
            case 'post':
                $post = $DB->get_record('forum_posts', ['id' => $this->file->itemid]);
                return $this->is_embedded_file_used($post, 'message', $this->file->filename);
            case 'attachment':
                return true;
            default:
                return parent::is_file_used();
        }
    }
}
