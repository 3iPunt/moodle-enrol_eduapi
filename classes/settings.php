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
 * Edu-API Enrolment plugin.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_eduapi;

use admin_setting_configselect;
use enrol_eduapi\local\converters\enrollment_converter;
use part_of_admin_tree;

/**
 * Edu-API Enrolment plugin settings helper.
 *
 * Replicates enrol_oneroster\settings::add_role_mapping() (one admin_setting_configselect per source
 * role, with an explicit "not mapped" option) for the `RoleTypeEnum` role mapping, and adds the
 * equivalent for the `EnrollmentStatusEnum` action mapping resolved in spec.md. Unlike
 * enrol_oneroster's helper (which resolves its default via `array_search($default, $allroles)` against
 * an array keyed by role id with loose comparison — brittle under PHP 8's stricter string/int
 * comparison rules), this uses a strict `array_search(..., true)` against a `roleid => shortname` map,
 * so the default resolution behaves the same way regardless of PHP version.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class settings {
    /**
     * Add a role mapping setting for one `RoleTypeEnum` value.
     *
     * @param   part_of_admin_tree $settings
     * @param   string $roletype The RoleTypeEnum value (exact case, e.g. 'teachingAssistant')
     * @param   array $roleidstoshortnames Map of roleid (string) => role shortname, plus '0' => notmapped
     * @param   array $displayoptions Map of roleid (string) => display name, plus '0' => "Do not enrol"
     * @param   string|null $defaultshortname The Moodle role shortname to preselect, or null for
     *                                        "Do not enrol"
     */
    public static function add_role_mapping(
        part_of_admin_tree $settings,
        string $roletype,
        array $roleidstoshortnames,
        array $displayoptions,
        ?string $defaultshortname = null
    ): void {
        $default = '0';
        if ($defaultshortname !== null) {
            $foundid = array_search($defaultshortname, $roleidstoshortnames, true);
            if ($foundid !== false) {
                $default = $foundid;
            }
        }

        $identifier = strtolower($roletype);

        $settings->add(new admin_setting_configselect(
            "enrol_eduapi/role_mapping_{$roletype}",
            get_string("settings_rolemapping_{$identifier}", 'enrol_eduapi'),
            '',
            $default,
            $displayoptions
        ));
    }

    /**
     * Add an action mapping setting for one `EnrollmentStatusEnum` value.
     *
     * @param   part_of_admin_tree $settings
     * @param   string $status The EnrollmentStatusEnum value (exact case, e.g. 'withdrawnFailing')
     * @param   string $default One of enrollment_converter::ACTION_* to preselect
     */
    public static function add_enrollmentstatus_mapping(
        part_of_admin_tree $settings,
        string $status,
        string $default
    ): void {
        $options = [
            enrollment_converter::ACTION_ENROL_ACTIVE =>
                get_string('settings_enrollmentstatus_action_enrol_active', 'enrol_eduapi'),
            enrollment_converter::ACTION_ENROL_SUSPENDED =>
                get_string('settings_enrollmentstatus_action_enrol_suspended', 'enrol_eduapi'),
            enrollment_converter::ACTION_UNENROL =>
                get_string('settings_enrollmentstatus_action_unenrol', 'enrol_eduapi'),
            enrollment_converter::ACTION_IGNORE =>
                get_string('settings_enrollmentstatus_action_ignore', 'enrol_eduapi'),
        ];

        $identifier = strtolower($status);

        $settings->add(new admin_setting_configselect(
            "enrol_eduapi/enrollmentstatus_mapping_{$status}",
            get_string("settings_enrollmentstatus_mapping_{$identifier}", 'enrol_eduapi'),
            '',
            $default,
            $options
        ));
    }
}
