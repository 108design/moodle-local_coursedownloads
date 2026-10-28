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
 * File documentation for db/hooks.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
$callbacks = [
    ['hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_coursedownloads\local\admin_branding::class, 'prepare']],
    ['hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => [\local_coursedownloads\local\admin_branding::class, 'head']],
    ['hook' => \core_course\hook\after_form_definition::class,
        'callback' => [\local_coursedownloads\course_hooks::class, 'definition']],
    ['hook' => \core_course\hook\after_form_submission::class,
        'callback' => [\local_coursedownloads\course_hooks::class, 'submitted']],
    ['hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_coursedownloads\local\admin_help::class, 'prepare']],
    ['hook' => \core\hook\output\before_standard_footer_html_generation::class,
        'callback' => [\local_coursedownloads\local\admin_help::class, 'footer']],
];
