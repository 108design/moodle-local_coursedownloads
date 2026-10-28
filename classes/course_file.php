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
 * File documentation for classes/course_file.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_coursedownloads;

use coding_exception;
use core_component;
use dml_exception;
use moodle_exception;
use moodle_url;
use stdClass;

class course_file {
    /**
     * Indicates if the file is visible to students (set by course_files).
     * @var bool
     */
    public $studentvisible = false;

    /**
     * Indicates if the file is visible to students (type+visibility, set by course_files).
     * @var bool
     */
    public $studentcansee = true;

    /** @var bool Restricted by course policy, not an impersonated participant's rights. */
    public $restrictedbypolicy = false;

    /**
     * @var stdClass
     */
    protected $file;

    /**
     * @var int
     */
    protected $courseid = 0;

    /**
     * @var int
     */
    public $fileid = 0;

    /**
     * @var string Friendly readable size of the file (MB or kB as appropriate).
     */
    public $filesize = '';

    /**
     * @var string
     */
    public $filetype = '';

    /**
     * @var false|string
     */
    public $fileurl = false;

    /**
     * @var string
     */
    public $filename = '';

    /** @var string Accessible selection label supplied by the renderer. */
    public string $selectlabel = '';

    /** @var int Native section ID; zero means course-wide material. */
    public int $sectionid = 0;

    /** @var string Plain section name for the current user. */
    public string $sectionname = '';

    /**
     * @var false|string A link to the page where the file is used.
     */
    public $filecomponenturl = false;

    /**
     * @var string
     */
    public $filecomponent;

    /**
     * @var bool
     */
    public $fileused;

    /**
     * Creates an object of this class or an appropriate subclass.
     * @param stdClass $file
     * @return course_file
     * @throws coding_exception|dml_exception|moodle_exception
     */
    public static function create(stdClass $file) : course_file {
        $classname = '\local_coursedownloads\components\\' . $file->component;
        if (class_exists($classname)) {
            return new $classname($file);
        }
        return new course_file($file);
    }

    /**
     * course_file constructor.
     * @param stdClass $file
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(stdClass $file) {
        $this->courseid = \context::instance_by_id($file->contextid)->get_course_context()->instanceid;
        $this->file = $file;
        $this->fileid = $file->id;
        $this->filesize = display_size($file->filesize);
        $this->filetype = mimetypes::get_file_type_translation($file->mimetype);

        $fileurl = $this->get_file_download_url();
        $this->fileurl = ($fileurl) ? $fileurl->out(false) : false;
        $this->filename = $this->get_displayed_filename();

        $componenturl = $this->get_component_url();
        $this->filecomponenturl = ($componenturl) ? $componenturl->out(false) : false;
        $this->filecomponent = $file->component;

        $isused = $this->is_file_used();
        $this->fileused = $isused === true;
    }

    /**
     * Getter for file
     * @return stdClass
     */
    public function get_file() : stdClass {
        return $this->file;
    }

    /**
     * Getter for filename
     * @return string
     */
    protected function get_displayed_filename() : string {
        return $this->file->filename;
    }

    /**
     * Try to get the download url for a file.
     *
     * @return null|moodle_url
     */
    protected function get_file_download_url() : ?moodle_url {
        if ($this->file->filearea == 'intro') {
            return $this->get_standard_file_download_url(false);
        }
        return null;
    }

    /**
     * Get the standard download url for a file.
     *
     * Most pluginfile urls are constructed the same way.
     *
     * @param bool $insertitemid
     * @return moodle_url
     */
    protected function get_standard_file_download_url(bool $insertitemid = true) : moodle_url {
        $file = $this->file;
        return moodle_url::make_pluginfile_url($file->contextid, $file->component, $file->filearea,
            $insertitemid ? $file->itemid : null,
            $file->filepath, $file->filename, false);
    }

    /**
     * Try to get the url for the component (module or course).
     *
     * @return null|moodle_url
     * @throws moodle_exception
     */
    protected function get_component_url() : ?moodle_url {
        if ($this->file->contextlevel == CONTEXT_MODULE) {
            $coursemodinfo = get_fast_modinfo($this->courseid);
            if (!empty($coursemodinfo->cms[$this->file->instanceid])) {
                return $coursemodinfo->cms[$this->file->instanceid]->url;
            }
        }
        return null;
    }

    /**
     * Checks if embedded files have been used
     *
     * @return bool|null
     * @throws dml_exception
     */
    protected function is_file_used() : ?bool {
        global $DB;
        $component = strpos($this->file->component, 'mod_') === 0 ? 'mod' : $this->file->component;
        switch ($component) {
            case 'mod': // Course module.
                $modname = str_replace('mod_', '', $this->file->component);
                if (!array_key_exists($modname, core_component::get_plugin_list('mod'))) {
                    return null;
                }
                if ($this->file->filearea === 'intro') {
                    $sql = "SELECT m.*
                              FROM {context} ctx
                              JOIN {course_modules} cm ON cm.id = ctx.instanceid
                              JOIN {{$modname}} m ON m.id = cm.instance
                             WHERE ctx.id = ?";
                    $mod = $DB->get_record_sql($sql, [$this->file->contextid]);
                    return $this->is_embedded_file_used($mod, 'intro', $this->file->filename);
                }
                break;
            case 'question':
                $question = $DB->get_record('question', ['id' => $this->file->itemid]);
                return $this->is_embedded_file_used($question, $this->file->filearea, $this->file->filename);
            case 'qtype_essay':
                $question = $DB->get_record('qtype_essay_options', ['questionid' => $this->file->itemid]);
                return $this->is_embedded_file_used($question, 'graderinfo', $this->file->filename);
        }
        return null;
    }

    /**
     * Test if a file is embedded in text
     *
     * @param stdClass|false $record
     * @param string $field
     * @param string $filename
     * @return bool|null
     */
    protected function is_embedded_file_used($record, string $field, string $filename) : ?bool {
        if ($record && property_exists($record, $field)) {
            return is_int(strpos($record->$field, '@@PLUGINFILE@@/' . rawurlencode($filename)));
        }
        return null;
    }
}
