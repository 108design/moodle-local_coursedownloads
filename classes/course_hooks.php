<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursefiles;
defined('MOODLE_INTERNAL') || die();

/** Native course-form integration; unrelated/API course updates retain the stored settings. */
final class course_hooks {
    public static function definition(\core_course\hook\after_form_definition $hook): void {
        $course = $hook->formwrapper->get_course();
        if (empty($course->id) || !has_capability('local/coursefiles:manage', \context_course::instance($course->id))) {
            return;
        }
        $form = $hook->mform;
        $groups = [];
        foreach (course_settings::TYPES as $type) {
            $groups[$type] = get_string('filetype:' . $type, 'local_coursefiles');
        }
        $form->addElement('header', 'local_coursefiles_header', get_string('local_coursefiles', 'local_coursefiles'));
        $form->addElement('select', 'local_coursefiles_filetypes', get_string('allowedfiletypes', 'local_coursefiles'),
            $groups, ['multiple' => 'multiple']);
        $form->setDefault('local_coursefiles_filetypes', explode(',', course_settings::get($course->id)->filetypes));
        $form->addHelpButton('local_coursefiles_filetypes', 'allowedfiletypes', 'local_coursefiles');
        $form->addElement('hidden', 'local_coursefiles_present', 1);
        $form->setType('local_coursefiles_present', PARAM_BOOL);
    }

    public static function submitted(\core_course\hook\after_form_submission $hook): void {
        $data = $hook->get_data();
        if ($hook->isnewcourse || empty($data->local_coursefiles_present) || empty($data->id)) {
            return;
        }
        require_capability('local/coursefiles:manage', \context_course::instance($data->id));
        $config = course_settings::get($data->id);
        course_settings::save($data->id, (array)($data->local_coursefiles_filetypes ?? []),
            (bool)$config->hidestudents, $config->whitelist_ext, $config->blacklist_ext);
    }
}
