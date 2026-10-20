<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursefiles', get_string('pluginname', 'local_coursefiles'));
    $ADMIN->add('localplugins', $settings);
    if ($ADMIN->fulltree) {
        $groups = [];
        foreach (\local_coursefiles\course_settings::TYPES as $group) {
            $groups[$group] = get_string('filetype:' . $group, 'local_coursefiles');
        }
        $label = get_string('allowedfiletypes', 'local_coursefiles');
        if (!CLI_SCRIPT) {
            $label = html_writer::span(html_writer::span($label, 'text-wrap')
                . html_writer::span(\local_coursefiles\local\admin_help::icon('filetypes'), 'flex-shrink-0'),
                'd-inline-flex align-items-center mw-100');
        }
        $settings->add(new admin_setting_configmulticheckbox('local_coursefiles/allowedfiletypes', $label,
            get_string('allowedfiletypes_desc', 'local_coursefiles'),
            array_fill_keys(\local_coursefiles\course_settings::DEFAULT_TYPES, 1), $groups));
    }
}
