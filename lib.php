<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();

/** Course link uses the same read-only defaults as the page and archive. */
function local_coursedownloads_extend_settings_navigation(settings_navigation $nav, context $context): void {
    global $DB;
    if (!$context instanceof context_course || !has_capability('local/coursedownloads:view', $context)) {
        return;
    }
    $course = $DB->get_record('course', ['id' => $context->instanceid], '*', MUST_EXIST);
    $config = \local_coursedownloads\course_settings::get($course->id);
    if ((!empty($config->hidestudents) && !has_capability('local/coursedownloads:manage', $context))
            || !can_access_course($course)) {
        return;
    }
    if ($node = $nav->get('courseadmin')) {
        $node->add(get_string('linkname', 'local_coursedownloads'),
            new moodle_url('/local/coursedownloads/index.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM, null, 'local_coursedownloads', new pix_icon('i/backup', ''));
    }
}

/** Shared contextual help in the native user navigation. */
function local_coursedownloads_render_navbar_output(\renderer_base $renderer): string {
    return \local_coursedownloads\local\admin_help::navbar($renderer);
}
