<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
/** Native installation and recovery for Course Downloads.
 * @package local_coursedownloads
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
function xmldb_local_coursedownloads_install() {
    \local_coursedownloads\local\legacy_migration::migrate();
}
function xmldb_local_coursedownloads_install_recovery() {
    \local_coursedownloads\local\legacy_migration::migrate();
}
