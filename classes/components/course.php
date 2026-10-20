<?php
namespace local_coursedownloads\components;

use dml_exception;
use local_coursedownloads\course_file;
use moodle_exception;
use moodle_url;

class course extends course_file {
    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_file_download_url() : ?moodle_url {
        switch ($this->file->filearea) {
            case 'section':
                return $this->get_standard_file_download_url();
            case 'legacy':
                return new moodle_url('/file.php/' . $this->courseid . $this->file->filepath . $this->file->filename);
            case 'overviewfiles':
            case 'summary':
                return $this->get_standard_file_download_url(false);
            default:
                return parent::get_file_download_url();
        }
    }

    /**
     * Try to get the url for the component (module or course).
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_component_url() : ?moodle_url {
        if ($this->file->component === 'contentbank') {
            return new moodle_url('/contentbank/index.php', ['contextid' => $this->file->contextid]);
        }
        if ($this->file->filearea === 'section') {
            return new moodle_url('/course/view.php', ['id' => $this->courseid, 'sectionid' => $this->file->itemid]);
        }
        return new moodle_url('/course/info.php', ['id' => $this->courseid]);
    }

    /**
     * Checks if embedded files have been used
     *
     * @return bool|null
     * @throws dml_exception
     */
    protected function is_file_used() : ?bool {
        global $DB;
        switch ($this->file->filearea) {
            case 'section':
                $section = $DB->get_record('course_sections', ['id' => $this->file->itemid]);
                return $this->is_embedded_file_used($section, 'summary', $this->file->filename);
            case 'overviewfiles':
                return true;
            case 'summary':
                $course = $DB->get_record('course', ['id' => $this->courseid]);
                return $this->is_embedded_file_used($course, 'summary', $this->file->filename);
            default:
                return parent::is_file_used();
        }
    }
}
