<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursedownloads;
defined('MOODLE_INTERNAL') || die();

/** Native course-form integration; unrelated/API course updates retain the stored settings. */
final class course_hooks {
    public static function definition(\core_course\hook\after_form_definition $hook): void {
        $course = $hook->formwrapper->get_course();
        if (empty($course->id) || !has_capability('local/coursedownloads:manage', \context_course::instance($course->id))) {
            return;
        }
        $form = $hook->mform;
        $groups = [];
        foreach (course_settings::TYPES as $type) {
            $groups[$type] = get_string('filetype:' . $type, 'local_coursedownloads');
        }
        $form->addElement('header', 'local_coursedownloads_header', get_string('local_coursedownloads', 'local_coursedownloads'));
        $form->addElement('select', 'local_coursedownloads_filetypes', get_string('allowedfiletypes', 'local_coursedownloads'),
            $groups, ['multiple' => 'multiple']);
        $form->setDefault('local_coursedownloads_filetypes', explode(',', course_settings::get($course->id)->filetypes));
        $form->addHelpButton('local_coursedownloads_filetypes', 'allowedfiletypes', 'local_coursedownloads');
        $form->addElement('hidden', 'local_coursedownloads_present', 1);
        $form->setType('local_coursedownloads_present', PARAM_BOOL);
    }

    public static function submitted(\core_course\hook\after_form_submission $hook): void {
        $data = $hook->get_data();
        if ($hook->isnewcourse || empty($data->local_coursedownloads_present) || empty($data->id)) {
            return;
        }
        require_capability('local/coursedownloads:manage', \context_course::instance($data->id));
        $config = course_settings::get($data->id);
        course_settings::save($data->id, (array)($data->local_coursedownloads_filetypes ?? []),
            (bool)$config->hidestudents, $config->whitelist_ext, $config->blacklist_ext);
    }
}
