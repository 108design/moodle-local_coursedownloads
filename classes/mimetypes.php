<?php
namespace local_coursedownloads;

use coding_exception;
use lang_string;

class mimetypes {
    /**
     * Mapping of file types to possible mime types.
     * @var array
     */
    static protected $mimetypes = array(
        'document' => array('application/epub+zip', 'application/msword', 'application/pdf',
            'application/postscript', 'application/vnd.ms-%', 'application/vnd.oasis.opendocument%',
            'application/vnd.openxmlformats-officedocument%', 'application/vnd.sun.xml%',
            'application/x-digidoc', 'application/xhtml+xml', 'application/x-javascript',
            'application/x-latex', 'application/xml', 'application/x-ms%', 'application/x-tex%',
            'document%', 'spreadsheet', 'text/%'),
        'image' => array('image/%'),
        'audio' => array('audio/%'),
        'video' => array('video/%'),
        'archive' => array('application/zip', 'application/x-tar', 'application/g-zip',
            'application/x-rar-compressed', 'application/x-7z-compressed', 'application/vnd.moodle.backup'),
        'hvp' => array('application/zip.h5p'),
    );

    /**
     * mimetypes constructor.
     */
    public function __construct() {
        self::check_config_mimetypes();
    }

    /**
     * Try to get the name of the file type in the user's lang
     *
     * @param string $mimetype
     * @return lang_string|string
     * @throws coding_exception
     */
    public static function get_file_type_translation(string $mimetype) {
        foreach (self::$mimetypes as $name => $types) {
            foreach ($types as $mime) {
                if ($mime === $mimetype ||
                    (substr($mime, -1) === '%' && strncmp($mime, $mimetype, strlen($mime) - 1) === 0)) {
                    return get_string('filetype:' . $name, 'local_coursedownloads');
                }
            }
        }

        return $mimetype;
    }

    /**
     * Get allowed file type groups for a course (per-course, then global, then all).
     * @param int|null $courseid
     * @return array
     */
    public static function get_allowed_filetypes($courseid = null): array {
        return $courseid ? explode(',', course_settings::get((int)$courseid)->filetypes) : course_settings::defaults();
    }

    /**
     * Getter for the full MIME classification map, independent of course policy.
     * @param int|null $courseid
     * @return array|string[][]
     */
    public static function get_mime_types($courseid = null): array {
        self::check_config_mimetypes();
        return self::$mimetypes;
    }

    /** Classification never depends on the groups permitted by a particular course. */
    public static function group_for(string $mimetype): string {
        foreach (self::get_mime_types() as $group => $types) {
            foreach ($types as $pattern) {
                if ($pattern === $mimetype || (substr($pattern, -1) === '%'
                        && strncmp($pattern, $mimetype, strlen($pattern) - 1) === 0)) {
                    return $group;
                }
            }
        }
        return 'other';
    }

    /**
     * Check if the predefined list of mimetypes should be overridden.
     */
    public static function check_config_mimetypes() {
        global $CFG;

        if (isset($CFG->filemimetypes)) {
            self::$mimetypes = $CFG->filemimetypes;
        }
    }
}
