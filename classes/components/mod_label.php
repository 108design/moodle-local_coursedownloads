<?php
namespace local_coursefiles\components;

use local_coursefiles\course_file;
use moodle_exception;
use moodle_url;

class mod_label extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        return $this->get_standard_file_download_url(false);
    }

    /**
     * Try to get the url for the component (module or course).
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_component_url() : ?moodle_url {
        global $DB;
        $sql = "SELECT cm.*
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                 WHERE ctx.id = ?";
        $mod = $DB->get_record_sql($sql, [$this->file->contextid]);
        return new moodle_url('/course/view.php', ['id' => $mod->course, 'sectionid' => $mod->section]);
    }
}
