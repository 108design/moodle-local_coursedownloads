<?php
// Copyright (C) 2026 Andreas Giesen. SPDX-License-Identifier: GPL-3.0-or-later
namespace local_coursefiles\local;

defined('MOODLE_INTERNAL') || die();

/** Contextual settings help, following the standalone Frontpage panel contract. */
final class admin_help {
    private static function topics(): array {
        $topics = ['overview', 'downloads', 'sections'];
        if (self::can_manage()) {
            $topics = array_merge($topics, ['visibility', 'filetypes', 'exceptions']);
        }
        return $topics;
    }

    private static function can_manage(): bool {
        global $PAGE;
        return $PAGE->context instanceof \context_course
            ? has_capability('local/coursefiles:manage', $PAGE->context)
            : has_capability('moodle/site:config', \context_system::instance());
    }

    private static function relevant_page(): bool {
        global $PAGE;
        $path = $PAGE->url->get_path();
        if (str_ends_with($path, '/local/coursefiles/index.php') && $PAGE->context instanceof \context_course) {
            return has_capability('local/coursefiles:view', $PAGE->context);
        }
        return str_ends_with($path, '/admin/settings.php')
            && $PAGE->url->get_param('section') === 'local_coursefiles'
            && has_capability('moodle/site:config', \context_system::instance());
    }
    private static bool $prepared = false;

    /** Register only the permitted settings page before its header is rendered. */
    public static function prepare(\core\hook\output\before_http_headers $hook): void {
        global $CFG, $PAGE;
        if (self::$prepared || !self::relevant_page()) {
            return;
        }
        $PAGE->add_body_class('local-coursefiles-admin-help-page');
        self::$prepared = true;
        $PAGE->requires->css(new \moodle_url('/local/coursefiles/styles/admin_help.css', [
            'rev' => get_config('local_coursefiles', 'version'),
        ]));
        $module = (int)$CFG->version < 2025041400 ? 'admin_help_legacy' : 'admin_help';
        $PAGE->requires->js_call_amd('local_coursefiles/admin_help_form', 'init');
        $PAGE->requires->js_call_amd('local_coursefiles/' . $module, 'init', [[
            'topic' => optional_param('coursefileshelp', '', PARAM_ALPHA),
        ]]);
    }

    /** Native popover with concise guidance and a direct link to the extended topic. */
    public static function icon(string $key): string {
        global $CFG, $PAGE;
        if (!in_array($key, self::topics(), true)) {
            throw new \coding_exception('Unknown Administration help topic.');
        }
        $url = str_ends_with($PAGE->url->get_path(), '/local/coursefiles/index.php')
            ? new \moodle_url($PAGE->url)
            : new \moodle_url('/admin/settings.php', ['section' => 'local_coursefiles']);
        $url->param('coursefileshelp', $key);
        $help = new \help_icon($key, 'local_coursefiles', $url->out(false));
        $renderer = $PAGE->get_renderer('core');
        $data = (array)$help->export_for_template($renderer);
        $data['completedoclink'] = \html_writer::link($url, get_string('adminhelpmore', 'local_coursefiles'));
        $data['legacy'] = (int)$CFG->version < 2025041400;
        $data['infoicon'] = (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo';
        return $renderer->render_from_template('local_coursefiles/settings_help_icon', $data);
    }

    /** Always available in the native Topnav user area on the registered page. */
    public static function navbar(\renderer_base $renderer): string {
        global $CFG;
        if (!self::$prepared) {
            return '';
        }
        $label = get_string('adminhelp', 'local_coursefiles');
        return \html_writer::tag('button', $renderer->pix_icon(
            (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo', ''), [
                'type' => 'button', 'class' => 'nav-link icon-no-margin local-coursefiles-help-navbar',
                'data-coursefiles-help-open' => '', 'aria-label' => $label, 'title' => $label,
                'aria-controls' => 'local-coursefiles-context-help', 'aria-expanded' => 'false',
            ]);
    }

    /** Same header, topic chooser and article layout as the Frontpage reference. */
    public static function render(): string {
        global $PAGE;
        if (!self::$prepared) {
            return '';
        }
        $title = get_string('adminhelp', 'local_coursefiles');
        $close = \html_writer::tag('button', \html_writer::span('&#215;', '', ['aria-hidden' => 'true']), [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm local-coursefiles-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-coursefiles-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::topics() as $topic) {
            $label = get_string('adminhelp_' . $topic, 'local_coursefiles');
            $options .= \html_writer::tag('option', $label, ['value' => $topic]);
            $articles .= \html_writer::tag('article', \html_writer::tag('h3', $label, ['class' => 'h5', 'tabindex' => '-1'])
                . get_string('adminhelp_' . $topic . '_article', 'local_coursefiles'), [
                    'data-coursefiles-help-article' => $topic, 'hidden' => 'hidden',
                ]);
        }
        $choice = \html_writer::tag('label', get_string('adminhelptopics', 'local_coursefiles'), [
            'for' => 'local-coursefiles-help-topic', 'class' => 'form-label',
        ]) . \html_writer::tag('select', $options, [
            'id' => 'local-coursefiles-help-topic', 'class' => 'form-select custom-select',
        ]);
        // Shared help-panel identity: no link or action; close remains beside the topic selector.
        $brand = \html_writer::tag('span', \html_writer::empty_tag('img', [
            'src' => $PAGE->get_renderer('core')->image_url('helpbrand', 'local_coursefiles')->out(false),
            'alt' => '', 'width' => 25, 'height' => 24,
        ]), [
            'class' => 'local-coursefiles-help-brand', 'role' => 'img', 'aria-label' => '108design',
            'title' => '108design', 'tabindex' => '0', 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ]);
        $header = \html_writer::tag('header', \html_writer::tag('h2', $title, [
            'id' => 'local-coursefiles-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
        ]) . \html_writer::div($choice, 'local-coursefiles-help-choices') . $brand . $close,
            ['class' => 'local-coursefiles-help-header local-coursefiles-help-header-branded']);
        return \html_writer::tag('aside', $header
            . \html_writer::div($articles, 'local-coursefiles-help-content', ['tabindex' => '0']), [
                'id' => 'local-coursefiles-context-help', 'class' => 'local-coursefiles-context-help',
                'aria-labelledby' => 'local-coursefiles-context-help-title', 'hidden' => 'hidden',
                'data-coursefiles-help-url' => $PAGE->url->out(false),
            ]);
    }

    /** Append the registered panel without changing the page's controls. */
    public static function footer(\core\hook\output\before_standard_footer_html_generation $hook): void {
        $hook->add_html(self::render());
    }
}
