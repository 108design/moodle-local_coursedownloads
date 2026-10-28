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
 * File documentation for classes/course_hooks.php.
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
