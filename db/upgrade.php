<?php
/**
 * Upgrade code for local_coursefiles.
 *
 * @package    local_coursefiles
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_coursefiles upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_coursefiles_upgrade($oldversion) {
    global $DB;
    if ($oldversion < 2026101900) {
        $manager = $DB->get_manager();
        $definition = new xmldb_file(__DIR__ . '/install.xml');
        if (!$definition->loadXMLStructure()) {
            throw new upgrade_exception('local_coursefiles', 2026101900, 'Invalid installation schema.');
        }
        $table = $definition->getStructure()->getTable('local_coursefiles_coursecfg');
        if (!$manager->table_exists($table)) {
            $manager->create_table($table);
        } else {
            foreach ($table->getFields() as $field) {
                if (!$manager->field_exists($table, $field)) {
                    $manager->add_field($table, $field);
                }
            }
            $manager->change_field_default($table, $table->getField('hidestudents'));
        }
        upgrade_plugin_savepoint(true, 2026101900, 'local', 'coursefiles');
    }
    return true;
}
