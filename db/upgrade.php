<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edu-API enrolment plugin upgrade.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_eduapi\local\converters\person_converter;

/**
 * Perform the Edu-API upgrade steps.
 *
 * @param   int $oldversion
 * @return  bool
 */
function xmldb_enrol_eduapi_upgrade(int $oldversion): bool {
    if ($oldversion < 2026083000) {
        // The `user_match_source` admin setting used to store a bare (dot-less) otherIdentifiers
        // identifierType directly (e.g. 'systemId'). Its select now emits a prefixed
        // 'otherIdentifiers.<identifierType>' value instead (see settings.php and
        // person_converter::resolve_source_value(), which no longer accepts the bare form), so an
        // existing bare value is rewritten to the dotted form here rather than being silently
        // orphaned: without this step, Moodle's admin_setting_configselect would show the stored
        // value as unset and pre-select the default, and the next save of the settings page would
        // overwrite it with that default.
        //
        // NOTE: the savepoint below (2026083000) is higher than $plugin->version at the time this
        // step was written (see version.php). Do not bump version.php here: the release that ships
        // this upgrade step must set $plugin->version to 2026083000 or later, otherwise Moodle will
        // never consider this step new enough to run.
        $source = get_config('enrol_eduapi', 'user_match_source');

        if (
            !empty($source)
                && $source !== 'primaryEmail'
                && $source !== 'sourcedId'
                && strpos($source, '.') === false
        ) {
            set_config('user_match_source', person_converter::build_other_identifier_source($source), 'enrol_eduapi');
        }

        upgrade_plugin_savepoint(true, 2026083000, 'enrol', 'eduapi');
    }

    return true;
}
