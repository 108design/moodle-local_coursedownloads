<?php
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
$PAGE->set_url(new moodle_url('/local/coursefiles/download.php', ['courseid' => $courseid, 'fileid' => $fileid]));
$files = new \local_coursefiles\course_files($courseid, $context);
$file = $files->get_pdf_original($fileid);
\core\session\manager::write_close();
send_stored_file($file, 0, 0, true);
