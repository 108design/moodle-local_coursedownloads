<p align="center">
  <img src="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/branding/logo.svg" alt="Course Downloads" width="125" height="125">
</p>

# Course Downloads for Moodle

A simple Moodle local plugin that allows students
and other course participants to view, filter and download
the course files that are available to them.

<details>
<summary>Screenshots</summary>

<table>
<tr>
<td width="50%" valign="top"><a href="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/screenshots/01-course-settings.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/screenshots/01-course-settings.jpg" alt="Course download permissions" width="100%"></a><br><strong>Course download permissions</strong><br>Choose download visibility, permitted file groups and extension rules in the course.</td>
<td width="50%" valign="top"><a href="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/screenshots/02-filters-and-downloads.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/screenshots/02-filters-and-downloads.jpg" alt="Filtered materials and section archives" width="100%"></a><br><strong>Filtered materials and section archives</strong><br>Combine filters, select individual files or download separate section archives.</td>
</tr>
<tr>
<td width="50%" valign="top"><a href="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/screenshots/03-admin-settings.jpg"><img src="https://raw.githubusercontent.com/108design/moodle-local_coursedownloads/main/docs/screenshots/03-admin-settings.jpg" alt="Website defaults" width="100%"></a><br><strong>Website defaults</strong><br>Set the default permitted file groups in site administration.</td>
<td width="50%"></td>
</tr>
</table>

</details>

For Moodle 4.5–5.3.

## What it does

- Lists supported course materials that the current user can access.
- Filters the list by file source, file type and one or more course sections.
- Downloads selected sections in one ZIP containing a separate ZIP for each section.
- Downloads selected files together as a ZIP archive, preserving duplicate filenames with a number.
- Provides per-course visibility, allowed file groups and extension whitelist/blacklist settings.

Native Moodle activity, group and file permissions always apply. Private assignment submissions,
internal question files and unsupported internal file areas are excluded.

## PDF Workspace originals

If PDF Workspace 1.5.10 or later is installed, Course Downloads also lists registered original PDFs
that the current user may view and download. The activity must allow original downloads or the
user must have `mod/pdfworkspace:printdocument`. Course Downloads management does not bypass this rule.
Direct links, selected-file ZIPs and section ZIPs recheck access. Comment attachments, annotated
exports and combined workspace exports are excluded. A document does not need to be selected for
a whole-workspace export to permit its original download. PDF Workspace is optional.

## Download

Download the installation ZIP from the [latest release](https://github.com/108design/moodle-local_coursedownloads/releases/latest).

## Quick install

Use Moodle’s ZIP plugin installer, or copy the plugin files into `local/coursedownloads` inside
your Moodle web root. In Moodle 5.1 and later the web root is the `public` directory.
Complete the installation or upgrade through **Site administration > Notifications**.

## Upgrading from Course Files

The component has changed from local_coursefiles to local_coursedownloads.
Back up your database and the old plugin first. Remove the old local/coursefiles code
folder **without uninstalling Course Files**, then install Course Downloads in
local/coursedownloads and complete Moodle's native installation. The one-time migration
preserves existing course settings, website defaults and role permission overrides.
Do not uninstall the old plugin before this migration: Moodle uninstallation removes its settings.
If the new installation reports a conflict, retain the backups and resolve it before continuing.
The old and new code folders must not be installed together. Course materials remain in their
original Moodle file areas. Course Downloads uses the new URLs under local/coursedownloads.

## Settings and permissions

Open **Course Downloads** in the course navigation. Users with `local/coursedownloads:manage` can configure
the course; the default editing teacher and manager roles have this permission. Downloads are
hidden from other users until a course manager enables them. View and ZIP download permissions
can be assigned separately with `local/coursedownloads:view` and `local/coursedownloads:download`.

Website administrators can set default file groups under **Site administration > Plugins >
Local plugins > Course Downloads**. Existing courses can override the groups on Course Downloads or in
their course settings. An empty selection uses the website defaults.

Enter filename extensions separated by commas, for example `pdf,jpg,h5p`. The whitelist adds
extensions to the permitted groups; the blacklist takes precedence. These settings affect users
without management permission and never grant access to otherwise protected files.

Use the **Filter files** area to combine file source, file type and course sections. Section choices use
checkboxes; the default is **All course sections**. Filters update the list immediately, and active
criteria can be removed individually or cleared with **Reset filters**. If automatic updates are
unavailable, use **Apply filters**.

Tick individual files and use the primary **Download selected files as ZIP** button. Its count
reflects the currently marked files. The separate **Download sections as separate archives** area previews
permitted files and nonempty section packages for the same filters, then provides one ZIP per section
inside a single download. File checkboxes do not affect section packages. Section exports apply
Moodle access rights and the configured file groups, whitelist and blacklist, including for course
managers. Empty sections are omitted. **Course-wide files** contains material without a section.

Managers can expand **Manage course download permissions** to edit the existing course settings.
The visibility status remains visible while these settings are collapsed.

Use the information icon in the top navigation for the page help. Consequential settings also
link directly to their help topics.

## Changelog
- Version 2026102007 — 4.0.2
  - Added the Course Downloads product logo to headings, plugin administration and documentation.
- Version 2026102006 — 4.0.1
  - Renamed to Course Downloads (local_coursedownloads).
  - Migrates existing Course Files course settings and permission overrides.
- Version 2026102005 — 4.0
  - Stable release of the renewed Course Files for Moodle 4.5–5.3.
  - Same functionality as 3.07.
- Version 2026102004 — 3.07
  - Stable release for Moodle 4.5–5.3 with section downloads and optional PDF Workspace originals.
  - Discreet administrator guidance when PDF Workspace is not installed.
- Version 2026102003 — 3.06 beta
  - Include permitted PDF Workspace originals in the list and both ZIP download types.
  - Recheck original-download permissions when following direct file links.
- Version 2026102002 — 3.05 beta
  - Compact, wrapping filter controls with clear file-source labels.
  - Clearer count labels and consistent section selector styling.
- Version 2026102001 — 3.04 beta
  - Unified live filters with removable criteria and an explicit reset.
  - Distinct file and section downloads with current selection and package counts.
  - Collapsible course permissions and clearer inline guidance.
- Version 2026102000 — 3.03 beta
  - Filter course materials by one or more sections.
  - Download permitted materials as one ZIP per selected section in a single download.
  - Apply native section visibility consistently to the file list and all ZIP downloads.
- Version 2026101900 — 3.02 beta
  - Shared native file access checks for the overview and ZIP downloads.
  - Validated extension settings and consistent course defaults.
  - Native settings integration and upgrade support for older installations.
  - Corrected file filters, individual download links, selection and accessible contextual help.
- Version 2025070300
  - Compatibility with Moodle 5.0
  - Settings per course: show/hide, show/hide file types, whitelist/blacklist
- Version 2022080600
  - Compatibility with Moodle 4.0
- Version 2022081200
  - Add plugin's own capabilities
  - Handle courses with no files
- Version 2022082400
  - Fix required Moodle version (3.5+)
  - Add link icon for Moodle 3.x

## About

2025070300 by: Andreas Giesen <andreas@108design.com>

Developed by: Kacper Rokicki <k.k.rokicki@gmail.com>

Based on "List course files" plugin: https://moodle.org/plugins/local_listcoursefiles

Current repository: https://github.com/108design/moodle-local_coursedownloads

Original upstream repository: https://github.com/k-rokicki/moodle-local_coursefiles

## License

GNU General Public License v3 or later; see [LICENSE](LICENSE). The original authors and
upstream attribution remain applicable. This software is provided without warranty under the GPL.

Available free of charge under the terms of the applicable license.
