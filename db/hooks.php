<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
$callbacks = [
    ['hook' => \core_course\hook\after_form_definition::class,
        'callback' => [\local_coursefiles\course_hooks::class, 'definition']],
    ['hook' => \core_course\hook\after_form_submission::class,
        'callback' => [\local_coursefiles\course_hooks::class, 'submitted']],
    ['hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_coursefiles\local\admin_help::class, 'prepare']],
    ['hook' => \core\hook\output\before_standard_footer_html_generation::class,
        'callback' => [\local_coursefiles\local\admin_help::class, 'footer']],
];
