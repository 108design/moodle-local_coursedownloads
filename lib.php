<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();

/** Course link uses the same read-only defaults as the page and archive. */
function local_coursefiles_extend_settings_navigation(settings_navigation $nav, context $context): void {
    global $DB;
    if (!$context instanceof context_course || !has_capability('local/coursefiles:view', $context)) {
        return;
    }
    $course = $DB->get_record('course', ['id' => $context->instanceid], '*', MUST_EXIST);
    $config = \local_coursefiles\course_settings::get($course->id);
    if ((!empty($config->hidestudents) && !has_capability('local/coursefiles:manage', $context))
            || !can_access_course($course)) {
        return;
    }
    if ($node = $nav->get('courseadmin')) {
        $node->add(get_string('linkname', 'local_coursefiles'),
            new moodle_url('/local/coursefiles/index.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM, null, 'local_coursefiles', new pix_icon('i/backup', ''));
    }
}

/** Shared contextual help in the native user navigation. */
function local_coursefiles_render_navbar_output(\renderer_base $renderer): string {
    return \local_coursefiles\local\admin_help::navbar($renderer);
}
