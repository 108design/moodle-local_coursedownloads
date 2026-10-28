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
 */
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursedownloads\local;

defined('MOODLE_INTERNAL') || die();

/** One-time, recoverable adoption of the former Course Files installation.
 * @package local_coursedownloads
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class legacy_migration {
    private const OLD = 'local_coursefiles';
    private const NEW = 'local_coursedownloads';
    private const OLD_TABLE = 'local_coursefiles_coursecfg';
    private const NEW_TABLE = 'local_coursedownloads_cfg';
    private const FIELDS = ['courseid', 'filetypes', 'hidestudents', 'whitelist_ext', 'blacklist_ext'];

    /** Called by both native installation and installation recovery. */
    public static function migrate(): void {
        global $CFG, $DB;
        if (is_file($CFG->dirroot . '/local/coursefiles/version.php')) {
            throw new \moodle_exception('migrationlegacycode', self::NEW);
        }
        $dbman = $DB->get_manager();
        $oldtable = new \xmldb_table(self::OLD_TABLE);
        $hasoldtable = $dbman->table_exists($oldtable);
        $oldversion = get_config(self::OLD, 'version');
        if (!$oldversion && !$hasoldtable) {
            return;
        }
        if (!$oldversion || $DB->record_exists('files', ['component' => self::OLD])) {
            throw new \moodle_exception('migrationforeignstate', self::NEW);
        }
        if ($hasoldtable && array_diff(self::FIELDS, array_keys($DB->get_columns(self::OLD_TABLE)))) {
            throw new \moodle_exception('migrationschema', self::NEW);
        }
        $oldrows = $hasoldtable ? $DB->get_records(self::OLD_TABLE, null, 'courseid') : [];
        $oldconfig = (array)get_config(self::OLD);
        foreach (self::legacy_globals() as $name => $value) {
            if (array_key_exists($name, $oldconfig) && (string)$oldconfig[$name] !== (string)$value) {
                throw new \moodle_exception('migrationconflict', self::NEW);
            }
            $oldconfig[$name] = $value;
        }
        $transaction = $DB->start_delegated_transaction();
        self::copy_rows($oldrows);
        foreach ($oldconfig as $name => $value) {
            if (in_array($name, ['version', 'installrunning', 'upgraderunning'], true)) {
                continue;
            }
            $existing = get_config(self::NEW, $name);
            if ($existing !== false && (string)$existing !== (string)$value) {
                throw new \moodle_exception('migrationconflict', self::NEW);
            }
            set_config($name, $value, self::NEW);
        }
        // Core clones every explicit role/context override, including prevents and prohibits.
        update_capabilities(self::NEW);
        foreach (['view', 'download', 'manage'] as $action) {
            $old = 'local/coursefiles:' . $action;
            $new = 'local/coursedownloads:' . $action;
            if ($DB->record_exists('capabilities', ['name' => $old])
                    && self::permissions($old) !== self::permissions($new)) {
                throw new \moodle_exception('migrationpermissions', self::NEW);
            }
        }
        $oldtheme = get_config('theme_boost_union', 'footersuppressstandardfooter_local_coursefiles');
        if ($oldtheme !== false) {
            $newtheme = get_config('theme_boost_union', 'footersuppressstandardfooter_local_coursedownloads');
            if ($newtheme !== false && (string)$newtheme !== (string)$oldtheme) {
                throw new \moodle_exception('migrationconflict', self::NEW);
            }
            set_config('footersuppressstandardfooter_local_coursedownloads', $oldtheme, 'theme_boost_union');
        }
        self::verify_rows($oldrows);
        set_config('migratedfrom', $oldversion, self::NEW);
        $transaction->allow_commit();

        // DDL is deliberately outside the transaction: MySQL cannot roll it back.
        // The verified new data and old definitions remain available if recovery is needed here.
        if ($hasoldtable) {
            self::verify_rows($DB->get_records(self::OLD_TABLE, null, 'courseid'));
            $dbman->drop_table($oldtable);
        }
        $transaction = $DB->start_delegated_transaction();
        capabilities_cleanup(self::OLD, []);
        unset_all_config_for_plugin(self::OLD);
        if ($oldtheme !== false) {
            unset_config('footersuppressstandardfooter_local_coursefiles', 'theme_boost_union');
        }
        $transaction->allow_commit();
        accesslib_reset_role_cache();
    }

    /** Preserve old-style core config before Core's legacy-plugin cleanup removes it. */
    private static function legacy_globals(): array {
        global $DB;
        $prefix = self::OLD . '_';
        $result = [];
        $like = $DB->sql_like('name', ':prefix');
        $params = ['prefix' => $DB->sql_like_escape($prefix) . '%'];
        foreach ($DB->get_records_select('config', $like, $params) as $row) {
            $result[substr($row->name, strlen($prefix))] = $row->value;
        }
        return $result;
    }

    /** Adopt complete course rows without normalising historical policy values. */
    private static function copy_rows(array $oldrows): void {
        global $DB;
        foreach ($oldrows as $oldrow) {
            $existing = $DB->get_record(self::NEW_TABLE, ['courseid' => $oldrow->courseid]);
            if ($existing && self::row($existing) !== self::row($oldrow)) {
                throw new \moodle_exception('migrationconflict', self::NEW);
            }
            if (!$existing) {
                $data = (object)array_intersect_key((array)$oldrow, array_flip(self::FIELDS));
                $DB->insert_record(self::NEW_TABLE, $data);
            }
        }
    }

    /** Compare actual values without changing historically valid extension lists or NULLs. */
    private static function row(\stdClass $row): array {
        $result = [];
        foreach (self::FIELDS as $field) {
            $result[$field] = $row->$field === null ? null : (string)$row->$field;
        }
        return $result;
    }

    /** Every legacy row must have an identical replacement before legacy cleanup. */
    private static function verify_rows(array $rows): void {
        global $DB;
        foreach ($rows as $row) {
            $new = $DB->get_record(self::NEW_TABLE, ['courseid' => $row->courseid]);
            if (!$new || self::row($new) !== self::row($row)) {
                throw new \moodle_exception('migrationconflict', self::NEW);
            }
        }
    }

    /** Effective explicit grants by role/context; IDs and timestamps are not permission semantics. */
    private static function permissions(string $capability): array {
        global $DB;
        $result = [];
        foreach ($DB->get_records('role_capabilities', ['capability' => $capability]) as $row) {
            if ((int)$row->permission !== CAP_INHERIT) {
                $result[$row->roleid . ':' . $row->contextid] = (int)$row->permission;
            }
        }
        ksort($result);
        return $result;
    }
}
