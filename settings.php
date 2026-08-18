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
 * Edu-API Enrolment plugin settings.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use enrol_eduapi\local\converters\enrollment_converter;
use enrol_eduapi\local\converters\offering_converter;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'enrol_eduapi',
        '',
        get_string('pluginname_desc', 'enrol_eduapi')
    ));

    // Connection settings: OAuth2 Client Credentials Grant token URL, API root, client id/secret,
    // and the collection page size. No OAuth version selector (Edu-API only defines CCG) and no
    // spec version selector (only v1p0 exists so far).
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/connection',
        get_string('settings_connection_settings', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'enrol_eduapi/token_url',
        get_string('settings_connection_token_url', 'enrol_eduapi'),
        get_string('settings_connection_token_url_desc', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'enrol_eduapi/root_url',
        get_string('settings_connection_root_url', 'enrol_eduapi'),
        get_string('settings_connection_root_url_desc', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'enrol_eduapi/clientid',
        get_string('settings_connection_clientid', 'enrol_eduapi'),
        get_string('settings_connection_clientid_desc', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'enrol_eduapi/secret',
        get_string('settings_connection_secret', 'enrol_eduapi'),
        get_string('settings_connection_secret_desc', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'enrol_eduapi/pagesize',
        get_string('settings_connection_pagesize', 'enrol_eduapi'),
        get_string('settings_connection_pagesize_desc', 'enrol_eduapi'),
        100,
        PARAM_INT,
        4
    ));

    // Test connection.
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/testconnection',
        get_string('settings_testconnection', 'enrol_eduapi'),
        get_string('settings_testconnection_detail', 'enrol_eduapi')
    ));

    $settings->add(new admin_setting_heading(
        'enrol_eduapi/testconnection_action',
        '',
        html_writer::link(
            new moodle_url('/enrol/eduapi/testconnection.php'),
            get_string('settings_testconnection_link', 'enrol_eduapi')
        )
    ));

    // Offering level: which Edu-API offering entity becomes the Moodle course.
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/offering',
        get_string('settings_offering', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configselect(
        'enrol_eduapi/offering_level',
        get_string('settings_offering_level', 'enrol_eduapi'),
        get_string('settings_offering_level_desc', 'enrol_eduapi'),
        offering_converter::LEVEL_COURSE_OFFERING,
        [
            offering_converter::LEVEL_COURSE_OFFERING => get_string('settings_offering_level_courseoffering', 'enrol_eduapi'),
            offering_converter::LEVEL_COMPONENT_OFFERING => get_string('settings_offering_level_componentoffering', 'enrol_eduapi'),
        ]
    ));

    // Resolved (Cliente) — sin migración: if offering_level changed after courses were already synced
    // under the previous level, offering_converter::detect_offering_level_change() (development phase
    // 5's sync) sets this flag; this page then shows the warning next to the selector until it is
    // cleared (which only happens automatically once every synced course matches the current level
    // again, i.e. never for a genuine change — the warning is a permanent notice of the mismatch).
    if (get_config('enrol_eduapi', offering_converter::CONFIG_CHANGED_WARNING)) {
        $settings->add(new admin_setting_heading(
            'enrol_eduapi/offering_level_changed_warning',
            '',
            $OUTPUT->notification(get_string('settings_offering_level_changed_warning', 'enrol_eduapi'), 'warning')
        ));
    }

    $settings->add(new admin_setting_configcheckbox(
        'enrol_eduapi/sync_groups',
        get_string('settings_sync_groups', 'enrol_eduapi'),
        get_string('settings_sync_groups_desc', 'enrol_eduapi'),
        0
    ));

    // User matching: which Moodle field is matched against which Edu-API Person attribute, and what
    // to do when no match is found.
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/usermatching',
        get_string('settings_usermatching', 'enrol_eduapi'),
        ''
    ));

    $settings->add(new admin_setting_configselect(
        'enrol_eduapi/user_match_moodlefield',
        get_string('settings_user_match_moodlefield', 'enrol_eduapi'),
        get_string('settings_user_match_moodlefield_desc', 'enrol_eduapi'),
        'email',
        [
            'email' => get_string('email'),
            'idnumber' => get_string('idnumber'),
            'username' => get_string('username'),
        ]
    ));

    // IdentifierTypeEnum values, per references/enumeration.md in the eduapi-v1p0 skill, plus the two
    // Person-specific shortcuts (primaryEmail, sourcedId) that are not otherIdentifiers entries.
    $identifiertypes = [
        'systemId', 'productId', 'userName', 'accountId', 'emailAddress', 'nationalIdentityNumber',
        'isbn', 'issn', 'lisSourcedId', 'oneRosterSourcedId', 'sisSourcedId', 'ltiContextId',
        'ltiDeploymentId', 'ltiToolId', 'ltiPlatformId', 'ltiUserId', 'identifier',
    ];
    $usermatchsourceoptions = [
        'primaryEmail' => get_string('settings_user_match_source_primaryemail', 'enrol_eduapi'),
        'sourcedId' => get_string('settings_user_match_source_sourcedid', 'enrol_eduapi'),
    ];
    foreach ($identifiertypes as $identifiertype) {
        $usermatchsourceoptions[$identifiertype] = get_string(
            'settings_user_match_source_otheridentifier',
            'enrol_eduapi',
            $identifiertype
        );
    }

    $settings->add(new admin_setting_configselect(
        'enrol_eduapi/user_match_source',
        get_string('settings_user_match_source', 'enrol_eduapi'),
        get_string('settings_user_match_source_desc', 'enrol_eduapi'),
        'primaryEmail',
        $usermatchsourceoptions
    ));

    $settings->add(new admin_setting_configselect(
        'enrol_eduapi/create_unmatched_users',
        get_string('settings_create_unmatched_users', 'enrol_eduapi'),
        get_string('settings_create_unmatched_users_desc', 'enrol_eduapi'),
        0,
        [
            0 => get_string('settings_create_unmatched_users_skip', 'enrol_eduapi'),
            1 => get_string('settings_create_unmatched_users_create', 'enrol_eduapi'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'enrol_eduapi/shortname_attribute',
        get_string('settings_shortname_attribute', 'enrol_eduapi'),
        get_string('settings_shortname_attribute_desc', 'enrol_eduapi'),
        'sourcedId',
        [
            'sourcedId' => get_string('settings_shortname_attribute_sourcedid', 'enrol_eduapi'),
            'primaryCode' => get_string('settings_shortname_attribute_primarycode', 'enrol_eduapi'),
        ]
    ));

    // Role mapping: one admin_setting_configselect per RoleTypeEnum value, each offering every Moodle
    // course role plus an explicit "No matricular" (not mapped) option.
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/rolemapping',
        get_string('settings_rolemapping', 'enrol_eduapi'),
        get_string('settings_rolemapping_generic_desc', 'enrol_eduapi')
    ));

    $allroles = get_all_roles();
    $roleidstoshortnames = ['0' => 'notmapped'] + array_combine(
        array_map(fn($role) => (string) $role->id, $allroles),
        array_map(fn($role) => $role->shortname, $allroles)
    );

    $courseroles = get_all_roles(context_course::instance(SITEID));
    $courserolenames = role_get_names(context_course::instance(SITEID), ROLENAME_ALIAS, true);
    $roledisplayoptions = ['0' => get_string('settings_rolemapping_notmapped', 'enrol_eduapi')] + array_combine(
        array_map(fn($role) => (string) $role->id, $courseroles),
        $courserolenames
    );

    // RoleTypeEnum values per spec.md / references/enumeration.md, with the defaults resolved in
    // spec.md: student -> student, teacher -> editingteacher, administrator -> manager, all other
    // values (without an obvious Moodle counterpart) default to "No matricular".
    $roletypedefaults = [
        'member' => null,
        'chair' => null,
        'staff' => null,
        'student' => 'student',
        'administrator' => 'manager',
        'aide' => null,
        'guardian' => null,
        'parent' => null,
        'proctor' => null,
        'relative' => null,
        'teacher' => 'editingteacher',
        'advisor' => null,
        'teachingAssistant' => null,
    ];
    foreach ($roletypedefaults as $roletype => $defaultshortname) {
        \enrol_eduapi\settings::add_role_mapping(
            $settings,
            $roletype,
            $roleidstoshortnames,
            $roledisplayoptions,
            $defaultshortname
        );
    }

    // EnrollmentStatusEnum mapping: one admin_setting_configselect per value, each with 4 actions.
    // Defaults are the "initial proposal, not validated against real provider data" from spec.md.
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/enrollmentstatusmapping',
        get_string('settings_enrollmentstatus', 'enrol_eduapi'),
        get_string('settings_enrollmentstatus_generic_desc', 'enrol_eduapi')
    ));

    $enrollmentstatusdefaults = [
        'enrolled' => enrollment_converter::ACTION_ENROL_ACTIVE,
        'registered' => enrollment_converter::ACTION_ENROL_ACTIVE,
        'accepted' => enrollment_converter::ACTION_ENROL_ACTIVE,
        'pending' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'deferred' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'onHold' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'suspended' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'onLeave' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'interruption' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'finished' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'withdrawnPassing' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'withdrawnFailing' => enrollment_converter::ACTION_ENROL_SUSPENDED,
        'withdrawn' => enrollment_converter::ACTION_UNENROL,
        'dropped' => enrollment_converter::ACTION_UNENROL,
        'cancelled' => enrollment_converter::ACTION_UNENROL,
        'revoked' => enrollment_converter::ACTION_UNENROL,
        'declined' => enrollment_converter::ACTION_UNENROL,
    ];
    foreach ($enrollmentstatusdefaults as $status => $default) {
        \enrol_eduapi\settings::add_enrollmentstatus_mapping($settings, $status, $default);
    }

    // Data to synchronise: organizations and academic session in scope, plus the record-status/course
    // preservation switches.
    $settings->add(new admin_setting_heading(
        'enrol_eduapi/datasync',
        get_string('settings_datasync', 'enrol_eduapi'),
        ''
    ));

    $availableorganizations = [];
    if ($availableorganizationsjson = get_config('enrol_eduapi', 'availableorganizations')) {
        $availableorganizations = (array) json_decode($availableorganizationsjson);
    }
    if (empty($availableorganizations)) {
        $availableorganizations[''] = get_string('none', 'admin');
    }

    $settings->add(new admin_setting_configmultiselect(
        'enrol_eduapi/datasync_organizations',
        get_string('settings_datasync_organizations', 'enrol_eduapi'),
        get_string('settings_datasync_organizations_desc', 'enrol_eduapi'),
        [],
        $availableorganizations
    ));

    $availablesessions = [];
    if ($availablesessionsjson = get_config('enrol_eduapi', 'availableacademicsessions')) {
        $availablesessions = (array) json_decode($availablesessionsjson);
    }
    if (empty($availablesessions)) {
        $availablesessions[''] = get_string('none', 'admin');
    }

    $settings->add(new admin_setting_configselect(
        'enrol_eduapi/datasync_academic_session',
        get_string('settings_datasync_academic_session', 'enrol_eduapi'),
        get_string('settings_datasync_academic_session_desc', 'enrol_eduapi'),
        '',
        $availablesessions
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_eduapi/exclude_inactive',
        get_string('settings_exclude_inactive', 'enrol_eduapi'),
        get_string('settings_exclude_inactive_desc', 'enrol_eduapi'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'enrol_eduapi/keep_existing_courses',
        get_string('settings_keep_existing_courses', 'enrol_eduapi'),
        get_string('settings_keep_existing_courses_desc', 'enrol_eduapi'),
        1
    ));
}

if ($hassiteconfig) {
    $ADMIN->add(
        'enrolments',
        new admin_externalpage(
            'enrol_eduapi/testconnection',
            get_string('test_eduapi_connection', 'enrol_eduapi'),
            new moodle_url('/enrol/eduapi/testconnection.php'),
            'moodle/site:config',
            true
        )
    );
}
