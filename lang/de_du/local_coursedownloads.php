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
 * File documentation for lang/de/local_coursedownloads.php.
 *
 * @package   local_coursedownloads
 * @copyright 2025 Andreas Giesen <andreas@108design.com>
 * @copyright based on work by 2022 Kacper Rokicki <k.k.rokicki@gmail.com>
 * @copyright based on work by 2016 Martin Gauk (@innoCampus, TU Berlin)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// SPDX-License-Identifier: GPL-3.0-or-later

$string['nofileselected'] = 'Du hast keine Datei ausgewählt.';
$string['hiddenforstudents'] = 'Downloads sind für dich in diesem Kurs nicht verfügbar.';
$string['adminhelp_overview_article'] = '<p>Kurs-Downloads sammelt unterstützte Kursmaterialien, auf die du zugreifen darfst. Unter Dateien filtern kannst du Dateiquelle, Dateityp und Kursabschnitte kombinieren. Eine leere Liste kann bedeuten, dass Filter oder Kursvorgaben die vorhandenen Materialien ausschließen.</p><p>Private Aufgabenabgaben, interne Fragendateien und nicht unterstützte Dateibereiche gehören nicht zu dieser Übersicht. Die ursprüngliche Aktivität bleibt der maßgebliche Ort für ihre Inhalte.</p><p>Bei installiertem PDF Workspace werden registrierte Original-PDFs angezeigt, wenn du die Aktivität sehen und das Original herunterladen darfst. Das Original muss für Teilnehmer/innen freigegeben sein oder deine Rolle benötigt die Berechtigung zum Originaldownload. Diese Prüfung gilt auch für direkte Dateilinks und beide ZIP-Downloadarten. Kommentardateien und erzeugte PDF-Exporte werden nicht übernommen. Die Auswahl für einen Workspace-Gesamtexport ist davon unabhängig.</p>';
$string['nosectionselected'] = 'Wähle mindestens einen Abschnitt zum Herunterladen aus.';
$string['invalidsections'] = 'Mindestens ein ausgewählter Abschnitt ist nicht mehr verfügbar. Lade die Seite neu und wähle erneut aus.';
$string['sections_help'] = 'Lädt alle passenden erlaubten Dateien aus der aktuellen Filterauswahl. Du erhältst ein ZIP je nichtleerem Abschnitt in einem gemeinsamen Download; die Dateihäkchen werden nicht verwendet.';
$string['adminhelp_sections_article'] = '<p>Im Bereich <strong>Dateien filtern</strong> kannst du Dateiquelle, Dateityp und <strong>Dateien aus folgenden Kursabschnitten</strong> gemeinsam eingrenzen. Öffne die Abschnittsauswahl und aktiviere die gewünschten Einträge. Standard ist <strong>Alle Kursabschnitte</strong>. Änderungen aktualisieren die Liste sofort. Mit den Kreuzen an den aktiven Filtern entfernst du einzelne Kriterien; <strong>Filter zurücksetzen</strong> entfernt alle Dateifilter und behält den Kurs bei. Falls die automatische Aktualisierung nicht verfügbar ist, verwendest du <strong>Filter anwenden</strong>.</p><p>Unter <strong>Abschnitte als separate Archive herunterladen</strong> siehst du die Zahl der passenden, erlaubten Dateien und Abschnittspakete. <strong>Abschnitts-ZIPs herunterladen</strong> erstellt einen gemeinsamen Download mit einem eigenen ZIP pro nichtleerem Abschnitt. Die Dateihäkchen der Liste gehören zum separaten Datei-Download und werden hierfür nicht verwendet. Auch bei Alle Kursabschnitte wird derselbe Filterumfang verwendet.</p><p>Abschnittspakete berücksichtigen deine aktuellen Moodle-Zugriffsrechte sowie erlaubte Dateigruppen, Whitelist und Blacklist. Das gilt auch für Verwaltungsberechtigte, deren Übersicht ausgeschlossene Dateien zusätzlich anzeigen kann. Die Paketvorschau zählt nur tatsächlich erlaubte Dateien. Ausgeblendete oder eingeschränkte Abschnitte werden nur angeboten, wenn du darauf zugreifen darfst. Materialien ohne Abschnitt findest du unter <strong>Kursweite Dateien</strong>.</p>';
$string['downloadsintro'] = 'Finde Kursmaterialien und lade einzelne Dateien oder ganze Abschnittspakete herunter.';
$string['filterserror'] = 'Die Filter konnten nicht angewendet werden. Die bisherige Liste bleibt sichtbar. Verwende Filter anwenden, um es erneut zu versuchen.';
$string['filechoicehint'] = 'Markiere die gewünschten Dateien mit den Häkchen in der Liste.';
$string['pdfworkspacehint'] = 'Mit dem optionalen PDF Workspace kannst du mehrere PDFs in einer Aktivität sammeln, kommentieren und deren Originaldownload freigeben. Erlaubte Originale erscheinen automatisch auch hier.';
$string['migrationlegacycode'] = 'Entferne den alten Codeordner local/coursefiles, ohne Course Files zu deinstallieren. Wiederhole danach die Installation von Kurs-Downloads.';
$string['migrationforeignstate'] = 'Die alte Course-Files-Installation enthält unbekannte Daten. Bewahre deine Sicherungen auf und kläre die Migration, bevor du fortfährst.';
$string['migrationschema'] = 'Die alte Course-Files-Einstellungstabelle hat eine nicht unterstützte Struktur. Bewahre deine Sicherungen auf und kläre die Migration, bevor du fortfährst.';
$string['migrationconflict'] = 'Die Einstellungen von Course Files und Kurs-Downloads widersprechen sich. Alte Einstellungen wurden nicht entfernt. Löse den Konflikt und wiederhole die Installation.';
$string['migrationpermissions'] = 'Die Rechteüberschreibungen von Course Files konnten nicht exakt übernommen werden. Bewahre deine Sicherungen auf und kläre die Migration, bevor du fortfährst.';
