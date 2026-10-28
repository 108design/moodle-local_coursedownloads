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
 * File documentation for settings.php.
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
if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursedownloads', get_string('pluginname', 'local_coursedownloads'));
    $ADMIN->add('localplugins', $settings);
    if ($ADMIN->fulltree) {
        $groups = [];
        foreach (\local_coursedownloads\course_settings::TYPES as $group) {
            $groups[$group] = get_string('filetype:' . $group, 'local_coursedownloads');
        }
        $label = get_string('allowedfiletypes', 'local_coursedownloads');
        if (!CLI_SCRIPT) {
            $label = html_writer::span(html_writer::span($label, 'text-wrap')
                . html_writer::span(\local_coursedownloads\local\admin_help::icon('filetypes'), 'flex-shrink-0'),
                'd-inline-flex align-items-center mw-100');
        }
        $settings->add(new admin_setting_configmulticheckbox('local_coursedownloads/allowedfiletypes', $label,
            get_string('allowedfiletypes_desc', 'local_coursedownloads'),
            array_fill_keys(\local_coursedownloads\course_settings::DEFAULT_TYPES, 1), $groups));
    }
}
