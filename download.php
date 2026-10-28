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
 * File documentation for download.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$fileid = required_param('fileid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/coursedownloads/download.php', ['courseid' => $courseid, 'fileid' => $fileid]));
$files = new \local_coursedownloads\course_files($courseid, $context);
$file = $files->get_pdf_original($fileid);
\core\session\manager::write_close();
send_stored_file($file, 0, 0, true);
