<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$component = optional_param('component', 'all', PARAM_ALPHANUMEXT);
$filetype = optional_param('filetype', 'all', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$sections = array_values(array_unique(optional_param_array('sections', [], PARAM_INT)));
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($courseid);
require_capability('local/coursefiles:view', $context);
$canmanage = has_capability('local/coursefiles:manage', $context);
$downloadallowed = has_capability('local/coursefiles:download', $context);
$config = \local_coursefiles\course_settings::get($courseid);

$sectionparams = [];
foreach ($sections as $index => $sectionid) {
    $sectionparams['sections[' . $index . ']'] = $sectionid;
}
$params = ['courseid' => $courseid, 'component' => $component, 'filetype' => $filetype] + $sectionparams;
$url = new moodle_url('/local/coursefiles/index.php', $params);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'local_coursefiles'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css(new moodle_url('/local/coursefiles/styles/search_bar.css'));
$PAGE->requires->css(new moodle_url('/local/coursefiles/styles/view.css', ['rev' => get_config('local_coursefiles', 'version')]));

if ($action === 'filtersections') {
    require_sesskey();
    if (!data_submitted()) {
        throw new moodle_exception('invalidrequest', 'error');
    }
    redirect($url);
}

if ($action === 'savesettings') {
    require_capability('local/coursefiles:manage', $context);
    require_sesskey();
    if (!data_submitted()) {
        throw new moodle_exception('invalidrequest', 'error');
    }
    try {
        \local_coursefiles\course_settings::save($courseid,
            optional_param_array('allowedfiletypes', [], PARAM_ALPHA),
            (bool)optional_param('hidestudents', 0, PARAM_BOOL),
            optional_param('whitelist_ext', '', PARAM_RAW_TRIMMED),
            optional_param('blacklist_ext', '', PARAM_RAW_TRIMMED));
        redirect($url, get_string('settingssaved', 'local_coursefiles'), null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (moodle_exception $exception) {
        \core\notification::error($exception->getMessage());
    }
    $config = \local_coursefiles\course_settings::get($courseid);
}
$showpage = $canmanage || empty($config->hidestudents);
$coursefiles = new \local_coursefiles\course_files($courseid, $context, $component, $filetype, $sections);
if ($action === 'download' || $action === 'downloadsections') {
    require_capability('local/coursefiles:download', $context);
    require_sesskey();
    if (!data_submitted()) {
        throw new moodle_exception('invalidrequest', 'error');
    }
    try {
        if ($action === 'downloadsections') {
            $coursefiles->download_sections($sections ?: array_keys($coursefiles->get_sections()));
        } else {
            $coursefiles->download_files(optional_param_array('file', [], PARAM_INT));
        }
    } catch (moodle_exception $exception) {
        \core\notification::error($exception->getMessage());
    }
}
$renderer = $PAGE->get_renderer('local_coursefiles');
echo $OUTPUT->header();
$componenturl = new moodle_url('/local/coursefiles/index.php',
    ['courseid' => $courseid, 'filetype' => $filetype] + $sectionparams);
$filetypeurl = new moodle_url('/local/coursefiles/index.php',
    ['courseid' => $courseid, 'component' => $component] + $sectionparams);
echo $renderer->overview_page($url, $componenturl, $filetypeurl, $coursefiles, $downloadallowed,
    ['canmanage' => $canmanage, 'showpage' => $showpage, 'config' => $config, 'settingsopen' => $action === 'savesettings']);
echo $OUTPUT->footer();
