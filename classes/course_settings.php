<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later

namespace local_coursedownloads;

defined('MOODLE_INTERNAL') || die();

/** Shared, read-only defaults and validated course configuration. */
final class course_settings {
    /** Default material groups for new courses. */
    public const DEFAULT_TYPES = ['document', 'image', 'audio'];

    /** All configurable groups, including unclassified MIME types. */
    public const TYPES = ['document', 'image', 'audio', 'video', 'archive', 'hvp', 'other'];

    /** Return validated groups, falling back to the website defaults for an empty selection. */
    public static function types(array $types): array {
        $types = array_values(array_intersect(self::TYPES, $types));
        return $types ?: self::defaults();
    }

    /** Website defaults, independently of whether a course has been visited. */
    public static function defaults(): array {
        $value = get_config('local_coursedownloads', 'allowedfiletypes');
        $types = is_string($value) ? explode(',', $value) : [];
        return array_values(array_intersect(self::TYPES, $types)) ?: self::DEFAULT_TYPES;
    }

    /** Missing rows are never persisted by a read operation. */
    public static function get(int $courseid): \stdClass {
        global $DB;
        $config = $DB->get_record('local_coursedownloads_cfg', ['courseid' => $courseid]);
        if (!$config) {
            $config = (object)['courseid' => $courseid, 'filetypes' => implode(',', self::defaults()),
                'hidestudents' => 1, 'whitelist_ext' => '', 'blacklist_ext' => ''];
        }
        $config->filetypes = implode(',', self::types(explode(',', $config->filetypes ?? '')));
        $config->whitelist_ext = implode(',', self::extensions($config->whitelist_ext ?? '', false));
        $config->blacklist_ext = implode(',', self::extensions($config->blacklist_ext ?? '', false));
        return $config;
    }

    /** Parse literal filename extensions; invalid historic values fail closed. */
    public static function extensions(string $value, bool $strict = true): array {
        $extensions = [];
        foreach (explode(',', strtolower($value)) as $extension) {
            $extension = ltrim(trim($extension), '.');
            if ($extension === '') {
                continue;
            }
            if (!preg_match('/\A[a-z0-9][a-z0-9_+\-]*\z/D', $extension)
                    || ($strict && strlen($extension) > 32)) {
                if ($strict) {
                    throw new \moodle_exception('invalidextensions', 'local_coursedownloads');
                }
                continue;
            }
            $extensions[$extension] = $extension;
        }
        if ($strict && count($extensions) > 100) {
            throw new \moodle_exception('invalidextensions', 'local_coursedownloads');
        }
        return array_values($extensions);
    }

    /** Explicit configuration writes require the native course management capability. */
    public static function save(int $courseid, array $types, bool $hidden, string $white, string $black): void {
        global $DB;
        require_capability('local/coursedownloads:manage', \context_course::instance($courseid));
        $config = self::get($courseid);
        $config->filetypes = implode(',', self::types($types));
        $config->hidestudents = (int)$hidden;
        $config->whitelist_ext = implode(',', self::extensions($white));
        $config->blacklist_ext = implode(',', self::extensions($black));
        if (!empty($config->id)) {
            $DB->update_record('local_coursedownloads_cfg', $config);
        } else {
            // Serialize first saves, including submissions from separate browser tabs.
            $factory = \core\lock\lock_config::get_lock_factory('local_coursedownloads');
            $lock = $factory->get_lock('course-' . $courseid, 10);
            if (!$lock) {
                throw new \moodle_exception('locktimeout', 'error');
            }
            try {
                $existing = $DB->get_record('local_coursedownloads_cfg', ['courseid' => $courseid]);
                if ($existing) {
                    $config->id = $existing->id;
                    $DB->update_record('local_coursedownloads_cfg', $config);
                } else {
                    $DB->insert_record('local_coursedownloads_cfg', $config);
                }
            } finally {
                $lock->release();
            }
        }
    }

    /** Course settings restrict material visibility for users without management rights. */
    public static function permits(\stdClass $config, \stdClass $file): bool {
        $extension = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
        if (in_array($extension, self::extensions($config->blacklist_ext, false), true)) {
            return false;
        }
        return in_array(mimetypes::group_for($file->mimetype), explode(',', $config->filetypes), true)
            || in_array($extension, self::extensions($config->whitelist_ext, false), true);
    }
}
