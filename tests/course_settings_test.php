<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursefiles;
defined('MOODLE_INTERNAL') || die();

/** Pure configuration cases do not require database-reset fixtures. */
final class course_settings_test extends \PHPUnit\Framework\TestCase {
    public function test_extensions_are_literal_normalised_and_unique(): void {
        $this->assertSame(['pdf', 'jpg', '7z', 'h5p'], course_settings::extensions(' .PDF,jpg,pdf,7z,H5P, '));
        $this->assertSame([], course_settings::extensions(' , , '));
    }

    public function test_query_fragments_and_wildcards_are_rejected(): void {
        foreach (["pdf')) OR 1=1 -- x", '%pdf', '*.pdf', 'tar.gz', "pdf\nzip", str_repeat('a', 33)] as $value) {
            try {
                course_settings::extensions($value);
                $this->fail('Invalid extension was accepted.');
            } catch (\moodle_exception $exception) {
                $this->assertSame('invalidextensions', $exception->errorcode);
            }
        }
    }

    public function test_invalid_historic_values_do_not_enter_policy(): void {
        $this->assertSame(['pdf'], course_settings::extensions("pdf,%.exe,pdf')) OR 1=1", false));
    }

    public function test_historic_blacklist_keeps_entries_beyond_the_new_save_limit(): void {
        $values = array_map(fn($number) => 'ext' . $number, range(1, 101));
        $values[] = str_repeat('a', 33);
        $historic = implode(',', $values);
        $this->assertSame($values, course_settings::extensions($historic, false));
        $config = (object)['filetypes' => 'document', 'whitelist_ext' => '', 'blacklist_ext' => $historic];
        $this->assertFalse(course_settings::permits($config,
            (object)['filename' => 'protected.ext101', 'mimetype' => 'application/pdf']));
        $this->expectException(\moodle_exception::class);
        course_settings::extensions($historic);
    }

    public function test_classification_is_independent_of_allowed_course_groups(): void {
        $this->assertSame('document', mimetypes::group_for('application/pdf'));
        $this->assertSame('image', mimetypes::group_for('image/png'));
        $this->assertSame('other', mimetypes::group_for('application/x-coursefiles-test'));
    }

    public function test_blacklist_wins_over_whitelist_and_group(): void {
        $config = (object)['filetypes' => 'document', 'whitelist_ext' => 'pdf,bin', 'blacklist_ext' => 'pdf'];
        $this->assertFalse(course_settings::permits($config, (object)['filename' => 'a.pdf', 'mimetype' => 'application/pdf']));
        $this->assertTrue(course_settings::permits($config, (object)['filename' => 'b.bin',
            'mimetype' => 'application/x-coursefiles-test']));
    }
}
