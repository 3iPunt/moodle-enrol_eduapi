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

/**
 * Perform the Edu-API upgrade steps.
 *
 * No upgrade steps yet: the initial schema (enrol_eduapi_user_map) is
 * declared directly in db/install.xml because this is a brand new plugin,
 * not an incremental change to a plugin already in production. This
 * function is left ready to receive future upgrade steps.
 *
 * @param   float $oldversion
 * @return  bool
 */
function xmldb_enrol_eduapi_upgrade($oldversion) {
    return true;
}
