<?php
// This file is part of Moodle - https://moodle.org/
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursedownloads\output;
defined('MOODLE_INTERNAL') || die();

/** Rendering only; access checks and permitted export grouping remain in course_files. */
class renderer extends \plugin_renderer_base {
    /** Flatten array parameters for Moodle4.5 URL compatibility. */
    private function filter_url(\local_coursedownloads\course_files $files, array $replace = []): \moodle_url {
        $params = ['courseid' => $files->get_course_id(), 'component' => $files->get_filter_component(),
            'filetype' => $files->get_filter_file_type()];
        $sections = $replace['sections'] ?? $files->get_filter_sections();
        unset($replace['sections']);
        $params = array_replace($params, $replace);
        foreach ($sections as $index => $id) { $params['sections[' . $index . ']'] = $id; }
        return new \moodle_url('/local/coursedownloads/index.php', $params);
    }

    private function filters(\local_coursedownloads\course_files $files, array $types): string {
        $fields = [];
        $values = ['component' => $files->get_filter_component(), 'filetype' => $files->get_filter_file_type()];
        foreach (['component' => $files->get_components(), 'filetype' => $types] as $key => $options) {
            $label = get_string('filterlabel_' . $key, 'local_coursedownloads');
            $id = 'coursedownloads-' . $key;
            $control = search_bar::labelled_control(\html_writer::select($options, $key, $values[$key], false,
                ['id' => $id, 'class' => 'custom-select form-select', 'aria-label' => $label]), $label, $id);
            $chips = '';
            if ($values[$key] !== 'all') {
                $chips = $this->chip($this->filter_url($files, [$key => 'all']), $label,
                    $options[$values[$key]] ?? $values[$key], ['data-search-bar-remove' => $key,
                        'data-search-bar-default' => 'all']);
            }
            $fields[] = search_bar::field($control, $key, $chips, $label);
        }
        $selected = $files->get_filter_sections();
        $sections = $files->get_sections();
        $summary = $selected ? get_string('sectionsselected', 'local_coursedownloads', count($selected))
            : get_string('allsections', 'local_coursedownloads');
        $label = get_string('filterlabel_sections', 'local_coursedownloads');
        $choices = \html_writer::link($this->filter_url($files, ['sections' => []]),
            get_string('allsections', 'local_coursedownloads'), ['class' => 'btn btn-link p-0 mb-2',
                'data-coursedownloads-clear-sections' => '1']);
        $chips = '';
        foreach ($sections as $id => $name) {
            $checked = in_array($id, $selected, true);
            $choices .= \html_writer::tag('label', \html_writer::empty_tag('input', ['type' => 'checkbox',
                'name' => 'sections[]', 'value' => $id, 'checked' => $checked ? 'checked' : null]) . s($name));
            if ($checked) {
                $remaining = array_values(array_diff($selected, [$id]));
                $chips .= $this->chip($this->filter_url($files, ['sections' => $remaining]), $label, $name,
                    ['data-coursedownloads-remove-section' => (string)$id]);
            }
        }
        $control = \html_writer::tag('details', \html_writer::tag('summary', \html_writer::span(s($summary), '', [
            'id' => 'coursedownloads-section-value', 'data-coursedownloads-section-summary' => '1',
        ]), ['aria-labelledby' => 'coursedownloads-sections-label coursedownloads-section-value']) . \html_writer::div($choices, 'local-plugin-search-bar-tag-options'), [
            'class' => 'local-plugin-search-bar-tags', 'id' => 'coursedownloads-sections',
        ]);
        $control = search_bar::labelled_control($control, $label, 'coursedownloads-sections', false);
        $fields[] = \html_writer::div(search_bar::field($control, 'sections', $chips, $label),
            'local-coursedownloads-section-filter-field');
        $base = new \moodle_url('/local/coursedownloads/index.php', ['courseid' => $files->get_course_id()]);
        $active = $selected || $values['component'] !== 'all' || $values['filetype'] !== 'all';
        $actions = \html_writer::tag('button', get_string('applyfilters', 'local_coursedownloads'), [
            'type' => 'submit', 'class' => 'btn btn-secondary', 'data-search-bar-submit' => '1',
        ]) . \html_writer::link($base, get_string('resetfilters', 'local_coursedownloads'), [
            'class' => 'btn btn-secondary', 'data-search-bar-reset' => '1', 'hidden' => $active ? null : 'hidden',
        ]);
        return search_bar::render(['filter' => ['label' => get_string('filtertitle', 'local_coursedownloads'),
            'fields' => $fields]], ['method' => 'get', 'action' => $base->out_omit_querystring(),
            'id' => 'coursedownloads-filter', 'class' => 'local-plugin-search-bar-manager', 'data-search-bar' => '1',
            'aria-label' => get_string('filtertitle', 'local_coursedownloads'),
            'data-search-bar-loading' => get_string('filtersloading', 'local_coursedownloads'),
            'data-search-bar-error' => get_string('filterserror', 'local_coursedownloads'),
            'data-coursedownloads-all-sections' => get_string('allsections', 'local_coursedownloads'),
            'data-coursedownloads-selected-format' => get_string('sectionsselected', 'local_coursedownloads', '__COUNT__'),
        ], $actions, \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid',
            'value' => $files->get_course_id()]));
    }

    private function chip(\moodle_url $url, string $label, string $text, array $attributes): string {
        return \html_writer::link($url,
            \html_writer::span(s($text), 'local-plugin-search-bar-chip-label')
                . \html_writer::span('×', 'local-plugin-search-bar-chip-remove', ['aria-hidden' => 'true']),
            ['class' => 'badge', 'aria-label' => get_string('removefilter', 'local_coursedownloads', $label . ': ' . $text)]
                + $attributes);
    }

    public function overview_page(\moodle_url $url, \moodle_url $componenturl, \moodle_url $filetypeurl,
            \local_coursedownloads\course_files $coursedownloads, bool $downloadallowed, array $settings = []): string {
        $config = $settings['config'];
        $canmanage = $settings['canmanage'];
        $showpage = $settings['showpage'];
        $types = explode(',', $config->filetypes);
        $options = [];
        foreach (\local_coursedownloads\course_settings::TYPES as $type) {
            $options[] = ['value' => $type, 'label' => get_string('filetype:' . $type, 'local_coursedownloads'),
                'selected' => in_array($type, $types, true)];
        }
        $files = $showpage ? $coursedownloads->get_file_list() : [];
        foreach ($files as $file) {
            $file->filecomponent = \local_coursedownloads\course_files::get_component_translation($file->filecomponent);
            $file->selectlabel = get_string('selectfile', 'local_coursedownloads', $file->filename);
        }
        $filtertypes = \local_coursedownloads\course_files::get_file_types($canmanage ? null : $types);
        if ($config->whitelist_ext !== '') { $filtertypes['whitelist'] = get_string('filetype:whitelist', 'local_coursedownloads'); }
        $export = $showpage && $downloadallowed ? $coursedownloads->get_section_export_summary() : ['files' => 0, 'sections' => 0];
        $params = [];
        foreach ($this->filter_url($coursedownloads)->params() as $name => $value) { $params[] = ['name' => $name, 'value' => $value]; }
        return $this->render_from_template('local_coursedownloads/view', [
            'productlogourl' => $this->output->image_url('adminbrand', 'local_coursedownloads')->out(false),
            'url' => $url->out(false), 'sesskey' => sesskey(), 'courseid' => $coursedownloads->get_course_id(),
            'posturl' => (new \moodle_url('/local/coursedownloads/index.php'))->out(false), 'filterparams' => $params,
            'filterbar' => $showpage ? $this->filters($coursedownloads, $filtertypes) : '',
            'resultsummary' => get_string('resultsummary', 'local_coursedownloads', (object)['files' => count($files),
                'sections' => count(array_unique(array_column($files, 'sectionid')))]),
            'exportsummary' => get_string('exportsummary', 'local_coursedownloads', (object)$export),
            'sectiondownloadlabel' => get_string('downloadsectioncount', 'local_coursedownloads', $export['sections']),
            'hassectionexport' => $export['sections'] > 0,
            'files' => $files, 'files_exist' => count($files) > 0, 'download_allowed' => $downloadallowed,
            'canmanage' => $canmanage, 'showpage' => $showpage, 'hidestudents' => !empty($config->hidestudents),
            'pdfworkspacehint' => $canmanage && has_capability('moodle/site:config', \context_system::instance())
                && !get_config('mod_pdfworkspace', 'version'),
            'pdfworkspaceurl' => 'https://github.com/108design/moodle-mod_pdfworkspace',
            'settingsopen' => !empty($settings['settingsopen']),
            'visibilitystatus' => get_string(empty($config->hidestudents) ? 'visibilityopen' : 'visibilityclosed', 'local_coursedownloads'),
            'typeoptions' => $options, 'whitelist_ext' => $config->whitelist_ext, 'blacklist_ext' => $config->blacklist_ext,
            'typeshelp' => $this->output->help_icon('allowedfiletypes', 'local_coursedownloads'),
            'visibilityhelp' => $canmanage ? \local_coursedownloads\local\admin_help::icon('visibility') : '',
            'exceptionshelp' => $canmanage ? \local_coursedownloads\local\admin_help::icon('exceptions') : '',
            'sectionshelp' => $showpage ? \local_coursedownloads\local\admin_help::icon('sections') : '',
        ]);
    }
}
