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
 * File documentation for classes/file_access.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later

namespace local_coursedownloads;

defined('MOODLE_INTERNAL') || die();

/** Native, file-specific read rules shared by the overview and archive creation. */
final class file_access {
    /** @var \course_modinfo Current user's course information. */
    private $modinfo;
    /** @var array Request-local records, including missing records. */
    private array $records = [];

    public function __construct(\course_modinfo $modinfo) {
        $this->modinfo = $modinfo;
    }

    /** Request-local lookup; never infer ownership from unrelated numeric IDs. */
    private function record(string $table, array $conditions) {
        global $DB;
        $key = $table . ':' . json_encode($conditions);
        if (!array_key_exists($key, $this->records)) {
            $this->records[$key] = $DB->get_record($table, $conditions);
        }
        return $this->records[$key];
    }

    /** Only registered originals with the current native original-download permission. */
    private function can_download_pdf_original(\stdClass $file, \cm_info $cm, \context_module $context): bool {
        // Older schemas without stable document identities are deliberately unsupported.
        if ((int)get_config('mod_pdfworkspace', 'version') < 2026100500
                || $file->filearea !== 'content' || (int)$file->itemid !== 0
                || $file->mimetype !== 'application/pdf'
                || strtolower(pathinfo($file->filename, PATHINFO_EXTENSION)) !== 'pdf'
                || !has_capability('mod/pdfworkspace:view', $context)) {
            return false;
        }
        $activity = $this->record('pdfworkspace', ['id' => $cm->instance, 'course' => $cm->course]);
        // Match PDF Workspace's original menu, independently of combined/workspace export permissions.
        if (!$activity || (empty($activity->useprint) && !has_capability('mod/pdfworkspace:printdocument', $context))) {
            return false;
        }
        return (bool)$this->record('pdfworkspace_documents', ['pdfworkspaceid' => $activity->id,
            'filepath' => $file->filepath, 'filename' => $file->filename, 'contenthash' => $file->contenthash]);
    }

    /** Unknown/internal file areas are excluded, even for configuration managers. */
    public function can_read(\stdClass $file): bool {
        global $CFG;
        $course = $this->modinfo->get_course();
        $context = \context::instance_by_id($file->contextid, IGNORE_MISSING);
        if (!$context || $file->filename === '.' || !empty($file->status)) {
            return false;
        }
        if ($context->contextlevel === CONTEXT_COURSE) {
            if ((int)$context->instanceid !== (int)$course->id) {
                return false;
            }
            if ($file->component === 'contentbank' && $file->filearea === 'public') {
                $record = $this->record('contentbank_content', ['id' => $file->itemid, 'contextid' => $context->id]);
                if (!$record || !class_exists($record->contenttype . '\\content')) {
                    return false;
                }
                $class = $record->contenttype . '\\content';
                $content = new $class($record);
                return $content->is_view_allowed() && $content->get_content_type_instance()->can_download($content);
            }
            if ($file->component !== 'course') {
                return false;
            }
            if (in_array($file->filearea, ['summary', 'overviewfiles'], true)) {
                return (int)$file->itemid === 0;
            }
            if ($file->filearea === 'section') {
                $section = $this->record('course_sections', ['id' => $file->itemid, 'course' => $course->id]);
                return $section && $this->modinfo->get_section_info($section->section)->uservisible;
            }
            return $file->filearea === 'legacy' && has_capability('moodle/course:managefiles', $context);
        }
        if ($context->contextlevel !== CONTEXT_MODULE || !isset($this->modinfo->cms[$context->instanceid])) {
            return false;
        }
        $cm = $this->modinfo->cms[$context->instanceid];
        if ($file->component !== 'mod_' . $cm->modname || !$cm->uservisible
                || !$this->modinfo->get_section_info($cm->sectionnum)->uservisible) {
            return false;
        }
        require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
        if ($cm->modname === 'pdfworkspace') {
            return $this->can_download_pdf_original($file, $cm, $context);
        }
        if ($cm->modname === 'assign') {
            if (!in_array($file->filearea, ['intro', 'introattachment', 'activityattachment'], true)
                    || !has_capability('mod/assign:view', $context) || (int)$file->itemid !== 0) {
                return false;
            }
            // Since Moodle 5.1 the native callback exposes introductory attachments independently.
            if ($file->filearea === 'introattachment' && (int)$CFG->version >= 2025100600) {
                return true;
            }
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            return (new \assign($context, $cm, $course))->show_intro();
        }
        if ($file->filearea === 'intro') {
            return (int)$file->itemid === 0 && plugin_supports('mod', $cm->modname, FEATURE_MOD_INTRO, true);
        }
        switch ($cm->modname) {
            case 'resource':
            case 'folder':
            case 'page':
                return $file->filearea === 'content' && (int)$file->itemid === 0
                    && has_capability('mod/' . $cm->modname . ':view', $context);
            case 'label':
                return false; // Its material is the generic intro area above.
            case 'book':
                $chapter = $this->record('book_chapters', ['id' => $file->itemid, 'bookid' => $cm->instance]);
                return $file->filearea === 'chapter' && $chapter && has_capability('mod/book:read', $context)
                    && (!$chapter->hidden || has_capability('mod/book:viewhiddenchapters', $context));
            case 'forum':
                if (!in_array($file->filearea, ['post', 'attachment'], true)) {
                    return false;
                }
                $post = $this->record('forum_posts', ['id' => $file->itemid]);
                $discussion = $post ? $this->record('forum_discussions', ['id' => $post->discussion,
                    'forum' => $cm->instance]) : false;
                $forum = $this->record('forum', ['id' => $cm->instance]);
                if (!$post || !$discussion || !$forum) {
                    return false;
                }
                if ($discussion->groupid > 0 && groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS
                        && !groups_is_member($discussion->groupid)
                        && !has_capability('moodle/site:accessallgroups', $context)) {
                    return false;
                }
                return forum_user_can_see_post($forum, $discussion, $post, null, $cm);
            case 'glossary':
                if (!in_array($file->filearea, ['entry', 'attachment'], true)
                        || !has_capability('mod/glossary:view', $context)) {
                    return false;
                }
                $entry = $this->record('glossary_entries', ['id' => $file->itemid]);
                $glossary = $this->record('glossary', ['id' => $cm->instance]);
                return $entry && $glossary && (int)$entry->glossaryid === (int)$cm->instance
                    && (!$glossary->defaultapproval || $entry->approved
                        || has_capability('mod/glossary:approve', $context));
            case 'data':
                if ($file->filearea !== 'content') {
                    return false;
                }
                $content = $this->record('data_content', ['id' => $file->itemid]);
                $field = $content ? $this->record('data_fields', ['id' => $content->fieldid,
                    'dataid' => $cm->instance]) : false;
                $record = $content ? $this->record('data_records', ['id' => $content->recordid,
                    'dataid' => $cm->instance]) : false;
                $data = $this->record('data', ['id' => $cm->instance]);
                if (!$field || !$record || !$data || !has_capability('mod/data:viewentry', $context)) {
                    return false;
                }
                if ($data->approval && !$record->approved && !data_isowner($record)
                        && !has_capability('mod/data:approve', $context)) {
                    return false;
                }
                if ($record->groupid && groups_get_activity_groupmode($cm, $course) == SEPARATEGROUPS
                        && !has_capability('moodle/site:accessallgroups', $context) && !groups_is_member($record->groupid)) {
                    return false;
                }
                $fieldobject = data_get_field($field, $data);
                return $fieldobject->file_ok(ltrim($file->filepath, '/') . $file->filename);
            case 'feedback':
                if (!has_capability('mod/feedback:view', $context) && !has_capability('mod/feedback:complete', $context)) {
                    return false;
                }
                if ($file->filearea === 'page_after_submit') {
                    return (int)$file->itemid === 0;
                }
                return $file->filearea === 'item' && (bool)$this->record('feedback_item',
                    ['id' => $file->itemid, 'feedback' => $cm->instance]);
            case 'h5pactivity':
                return $file->filearea === 'package' && (int)$file->itemid === 0;
            default:
                return false;
        }
    }
}
