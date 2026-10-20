<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursefiles;
defined('MOODLE_INTERNAL') || die();

/** Course material inventory; display filters never replace file authorisation. */
class course_files {
    private int $courseid;
    private \context_course $context;
    private string $filtercomponent;
    private string $filterfiletype;
    private array $filtersections;
    private \course_modinfo $coursemodinfo;
    private \stdClass $config;
    private bool $canmanage;
    private ?array $accessible = null;

    public function __construct(int $courseid, \context_course $context, string $component = 'all', string $filetype = 'all',
            array $sections = []) {
        if ((int)$context->instanceid !== $courseid) {
            throw new \coding_exception('Course context mismatch.');
        }
        $this->courseid = $courseid;
        $this->context = $context;
        $this->filtercomponent = $component;
        $this->filterfiletype = $filetype;
        $this->filtersections = array_values(array_unique(array_map('intval', $sections)));
        $this->coursemodinfo = get_fast_modinfo($courseid);
        $this->config = course_settings::get($courseid);
        $this->canmanage = has_capability('local/coursefiles:manage', $context);
    }

    public function get_course_id(): int { return $this->courseid; }
    public function get_filter_component(): string { return $this->filtercomponent; }
    public function get_filter_file_type(): string { return $this->filterfiletype; }
    public function get_filter_sections(): array { return $this->filtersections; }

    /** Hidden-page policy and native course access also protect direct class callers. */
    private function require_access(): void {
        require_capability('local/coursefiles:view', $this->context);
        if (!can_access_course($this->coursemodinfo->get_course())) {
            throw new \required_capability_exception($this->context, 'moodle/course:view', 'nopermissions', '');
        }
        if (!$this->canmanage && !empty($this->config->hidestudents)) {
            throw new \moodle_exception('hiddenforstudents', 'local_coursefiles');
        }
    }

    /** Authorised records, independent of presentation filters. */
    private function accessible_files(): array {
        global $DB;
        $this->require_access();
        if ($this->accessible !== null) {
            return $this->accessible;
        }
        [$sql, $params] = $this->inventory_sql();
        $access = new file_access($this->coursemodinfo);
        $files = [];
        $records = $DB->get_recordset_sql($sql, $params);
        try {
            foreach ($records as $record) {
                if (!$access->can_read($record)) {
                    continue;
                }
                if (!$this->canmanage && !course_settings::permits($this->config, $record)) {
                    continue;
                }
                $files[(int)$record->id] = $record;
            }
        } finally {
            $records->close();
        }
        return $this->accessible = $files;
    }

    /** The actual DML statement, also exercised against the targeted second database driver. */
    protected function inventory_sql(): array {
        global $DB;
        $sql = "SELECT f.*, c.contextlevel, c.instanceid
                  FROM {files} f JOIN {context} c ON c.id = f.contextid
                 WHERE f.filename <> :directory AND f.status = 0
                   AND (c.id = :coursecontext OR " . $DB->sql_like('c.path', ':contextpath') . ")
                   AND (c.contextlevel = :courselevel OR c.contextlevel = :modulelevel)
              ORDER BY f.component, f.filename, f.id";
        $params = ['directory' => '.', 'coursecontext' => $this->context->id,
            'contextpath' => $this->context->path . '/%', 'courselevel' => CONTEXT_COURSE,
            'modulelevel' => CONTEXT_MODULE];
        return [$sql, $params];
    }

    /** Section IDs are native course section IDs; zero denotes material without a section. */
    private function section_for_file(\stdClass $file): int {
        if ((int)$file->contextlevel === CONTEXT_MODULE) {
            $cm = $this->coursemodinfo->cms[$file->instanceid];
            return (int)$this->coursemodinfo->get_section_info($cm->sectionnum)->id;
        }
        return $file->component === 'course' && $file->filearea === 'section' ? (int)$file->itemid : 0;
    }

    /** Only names of sections visible to the current user may appear in the selector. */
    public function get_sections(): array {
        global $CFG;
        $this->require_access();
        require_once($CFG->dirroot . '/course/lib.php');
        $sections = [];
        foreach ($this->coursemodinfo->get_section_info_all() as $section) {
            if ($section->uservisible) {
                $sections[(int)$section->id] = html_entity_decode(strip_tags(
                    get_section_name($this->coursemodinfo->get_course(), $section)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }
        foreach ($this->accessible_files() as $file) {
            if ($this->section_for_file($file) === 0) {
                $sections[0] = get_string('coursewidefiles', 'local_coursefiles');
                break;
            }
        }
        return $sections;
    }

    /** Apply the selected section, component and type only after authorisation. */
    private function filtered_records(bool $ignorefilters): array {
        $files = $this->accessible_files();
        if ($ignorefilters) {
            return $files;
        }
        $sections = $this->filtersections ? $this->get_sections() : [];
        return array_filter($files, function($file) use ($sections) {
            if ($this->filtersections && (!isset($sections[$this->section_for_file($file)])
                    || !in_array($this->section_for_file($file), $this->filtersections, true))) {
                return false;
            }
            if ($this->filtercomponent !== 'all' && $file->component !== $this->filtercomponent) {
                return false;
            }
            if ($this->filterfiletype === 'all') {
                return true;
            }
            if ($this->filterfiletype === 'whitelist') {
                return in_array(strtolower(pathinfo($file->filename, PATHINFO_EXTENSION)),
                    course_settings::extensions($this->config->whitelist_ext, false), true);
            }
            return mimetypes::group_for($file->mimetype) === $this->filterfiletype;
        });
    }

    public function get_file_list(bool $ignorefilters = false): array {
        $files = [];
        $sections = $this->get_sections();
        foreach ($this->filtered_records($ignorefilters) as $record) {
            $file = course_file::create($record);
            $file->sectionid = $this->section_for_file($record);
            $file->sectionname = $sections[$file->sectionid] ?? '';
            $file->restrictedbypolicy = !empty($this->config->hidestudents)
                || !course_settings::permits($this->config, $record);
            $files[] = $file;
        }
        return $files;
    }

    public function get_components(): array {
        $components = [];
        foreach ($this->accessible_files() as $record) {
            $components[$record->component] = self::get_component_translation($record->component);
        }
        asort($components, SORT_NATURAL | SORT_FLAG_CASE);
        return ['all' => get_string('allcomponents', 'local_coursefiles')] + $components;
    }

    /** Reload current role, policy and native section/activity state before every export. */
    private function refresh_access(): void {
        require_capability('local/coursefiles:download', $this->context);
        $this->accessible = null;
        $this->coursemodinfo = get_fast_modinfo($this->courseid);
        $this->config = course_settings::get($this->courseid);
        $this->canmanage = has_capability('local/coursefiles:manage', $this->context);
    }

    /** Recheck original-download rights when following a PDF Workspace link from the overview. */
    public function get_pdf_original(int $fileid): \stored_file {
        $this->refresh_access();
        $allowed = $this->filtered_records(false);
        if (!isset($allowed[$fileid]) || $allowed[$fileid]->component !== 'mod_pdfworkspace') {
            throw new \moodle_exception('invalidselection', 'local_coursefiles');
        }
        $file = get_file_storage()->get_file_by_id($fileid);
        if (!$file || $file->is_directory()) {
            throw new \moodle_exception('invalidselection', 'local_coursefiles');
        }
        return $file;
    }

    /** One archive implementation preserves authorisation checks and duplicate filenames. */
    private function archive_records(array $records, string $archive): string {
        $storage = get_file_storage();
        $archivefiles = [];
        foreach ($records as $record) {
            $storedfile = $storage->get_file_by_id($record->id);
            if (!$storedfile || $storedfile->is_directory()) {
                throw new \moodle_exception('invalidselection', 'local_coursefiles');
            }
            $archivefiles[$this->get_unique_file_name($storedfile->get_filename(), $archivefiles)] = $storedfile;
        }
        if (!get_file_packer('application/zip')->archive_to_pathname($archivefiles, $archive)) {
            throw new \moodle_exception('archivefailed', 'local_coursefiles');
        }
        return $archive;
    }

    /** Refresh authorisation immediately before zipping; reject the entire tampered selection. */
    public function create_archive(array $fileids): string {
        $this->refresh_access();
        $allowed = $this->filtered_records(false);
        $ids = array_values(array_unique(array_map('intval', $fileids)));
        if (!$ids) {
            throw new \moodle_exception('nofileselected', 'local_coursefiles');
        }
        if (array_diff($ids, array_keys($allowed))) {
            throw new \moodle_exception('invalidselection', 'local_coursefiles');
        }
        $records = [];
        foreach ($ids as $id) {
            $records[] = $allowed[$id];
        }
        return $this->archive_records($records, make_request_directory() . '/coursefiles.zip');
    }

    /** Course-policy-permitted records, shared by the visible export count and actual section ZIPs. */
    private function section_groups(array $ids): array {
        $groups = [];
        foreach ($this->filtered_records(false) as $file) {
            $sectionid = $this->section_for_file($file);
            // A manager's overview may show excluded files, but section exports follow course policy for everyone.
            if (in_array($sectionid, $ids, true) && course_settings::permits($this->config, $file)) {
                $groups[$sectionid][] = $file;
            }
        }
        return $groups;
    }

    /** Read-only export preview for the current filters; an empty section filter explicitly means all. */
    public function get_section_export_summary(): array {
        $ids = $this->filtersections ?: array_keys($this->get_sections());
        $groups = $this->section_groups($ids);
        return ['sections' => count($groups), 'files' => array_sum(array_map('count', $groups))];
    }

    /** Export permitted material as one inner ZIP per selected section, in one outer ZIP. */
    public function create_section_archive(array $sectionids): string {
        $this->refresh_access();
        $ids = array_values(array_unique(array_map('intval', $sectionids)));
        if (!$ids) {
            throw new \moodle_exception('nosectionselected', 'local_coursefiles');
        }
        $sections = $this->get_sections();
        if (array_diff($ids, array_keys($sections))) {
            throw new \moodle_exception('invalidsections', 'local_coursefiles');
        }
        $groups = $this->section_groups($ids);
        if (!$groups) {
            throw new \moodle_exception('nosectionfiles', 'local_coursefiles');
        }
        $directory = make_request_directory();
        $archives = [];
        foreach (array_intersect_key($sections, array_flip($ids)) as $id => $name) {
            if (empty($groups[$id])) {
                continue;
            }
            $filename = $this->get_unique_file_name($name . '.zip', $archives);
            $archives[$filename] = $this->archive_records($groups[$id], $directory . '/section-' . $id . '.zip');
        }
        $archive = $directory . '/sections.zip';
        if (!get_file_packer('application/zip')->archive_to_pathname($archives, $archive)) {
            throw new \moodle_exception('archivefailed', 'local_coursefiles');
        }
        return $archive;
    }

    /** HTTP transport uses the same tested archive path as all internal callers. */
    public function download_files(array $fileids): void {
        $archive = $this->create_archive($fileids);
        $filename = clean_filename($this->coursemodinfo->get_course()->fullname . '.zip');
        \core\session\manager::write_close();
        send_temp_file($archive, $filename);
    }

    public function download_sections(array $sectionids): void {
        $archive = $this->create_section_archive($sectionids);
        $filename = clean_filename($this->coursemodinfo->get_course()->fullname . '-sections.zip');
        \core\session\manager::write_close();
        send_temp_file($archive, $filename);
    }

    protected function get_unique_file_name(string $filename, array $existingfiles): string {
        $name = clean_filename($filename);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $suffix = $extension !== '' ? '.' . $extension : '';
        $stem = $suffix !== '' ? substr($name, 0, -strlen($suffix)) : $name;
        for ($index = 1; isset($existingfiles[$name]); $index++) {
            $name = $stem . '(' . $index . ')' . $suffix;
        }
        return $name;
    }

    public static function get_file_types(?array $allowedtypes = null): array {
        $types = ['all' => get_string('filetype:all', 'local_coursefiles')];
        foreach (course_settings::TYPES as $type) {
            if ($allowedtypes === null || in_array($type, $allowedtypes, true)) {
                $types[$type] = get_string('filetype:' . $type, 'local_coursefiles');
            }
        }
        return $types;
    }

    public static function get_component_translation(string $component): string {
        if ($component === 'course') { return get_string('sourcecourse', 'local_coursefiles'); }
        if ($component === 'contentbank') { return get_string('contentbank', 'contentbank'); }
        if (strpos($component, 'mod_') === 0) { return get_string('pluginname', $component); }
        return $component;
    }
}
