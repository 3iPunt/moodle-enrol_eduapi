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
 * Strings for component 'enrol_eduapi', language 'en'.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['configurationcorrect'] = 'Connection successfully tested.';
$string['connectionfailed'] = 'Could not connect to the Edu-API provider: {$a}';
$string['connectionpartial'] = 'Authentication succeeded, but fetching organizations/academic sessions failed: {$a}';
$string['fullsync'] = 'Edu-API full synchronisation';
$string['missingrequiredconfig'] = 'One or more required configuration values were not found. Please ensure that you have correctly configured the \'{$a}\' setting.';
$string['offeringnotfound'] = 'No offering found with sourcedId \'{$a}\'.';
$string['offeringorganizationmismatch'] = 'The offering \'{$a}\' does not belong to the given organization.';
$string['pluginname'] = 'Edu-API';
$string['pluginname_desc'] = 'Moodle supports the 1EdTech Edu-API v1p0 specification for synchronising organizations, courses, users and enrolments.';
$string['test_eduapi_connection'] = 'Test Edu-API connection';

// Privacy API.
$string['privacy:mappingpath'] = 'Edu-API';
$string['privacy:metadata:enrol_eduapi_user_map'] = 'A link between an Edu-API person identifier and a Moodle user.';
$string['privacy:metadata:enrol_eduapi_user_map:parentid'] = 'The Edu-API sourcedId of the Person.';
$string['privacy:metadata:enrol_eduapi_user_map:mappedid'] = 'The id of the linked Moodle user.';

// Connection settings.
$string['settings_connection_settings'] = 'Connection settings';
$string['settings_connection_token_url'] = 'OAuth2 token URL';
$string['settings_connection_token_url_desc'] = 'The URL of the provider\'s OAuth2 Client Credentials Grant token endpoint.';
$string['settings_connection_root_url'] = 'Edu-API root URL';
$string['settings_connection_root_url_desc'] = 'The provider\'s Edu-API v1p0 API root, including any vendor-specific path. Used exactly as entered — no path is appended automatically.';
$string['settings_connection_clientid'] = 'Client ID';
$string['settings_connection_clientid_desc'] = 'The OAuth2 client ID issued by the provider.';
$string['settings_connection_secret'] = 'Client secret';
$string['settings_connection_secret_desc'] = 'The OAuth2 client secret issued by the provider.';
$string['settings_connection_pagesize'] = 'Page size';
$string['settings_connection_pagesize_desc'] = 'The number of records to request per page (the \'limit\' query parameter) when fetching a collection.';

// Test connection.
$string['settings_testconnection'] = 'Test connection';
$string['settings_testconnection_detail'] = 'Use this to check that the connection settings above are correct, and to refresh the list of organizations and academic sessions available below.';
$string['settings_testconnection_link'] = 'Test the Edu-API connection';

// Offering level.
$string['settings_offering'] = 'Course mapping';
$string['settings_offering_level'] = 'Offering level';
$string['settings_offering_level_desc'] = 'Which Edu-API offering becomes the Moodle course. The level not chosen becomes groups within the course when "Sync groups" is enabled.';
$string['settings_offering_level_courseoffering'] = 'CourseOffering (ComponentOfferings become groups)';
$string['settings_offering_level_componentoffering'] = 'ComponentOffering (each becomes its own course)';
$string['settings_offering_level_changed_warning'] = 'The "Offering level" setting has changed since the last sync, and courses already exist under the previous level. Those courses are left untouched: this plugin does not migrate courses between offering levels automatically.';
$string['settings_sync_groups'] = 'Sync groups';
$string['settings_sync_groups_desc'] = 'Create a Moodle group for every offering at the level not chosen as the course (e.g. ComponentOfferings, when CourseOffering is the course level).';

// User matching.
$string['settings_usermatching'] = 'User matching';
$string['settings_user_match_moodlefield'] = 'Moodle field';
$string['settings_user_match_moodlefield_desc'] = 'The Moodle user field to match a Person against.';
$string['settings_user_match_source'] = 'Edu-API source attribute';
$string['settings_user_match_source_desc'] = 'The Person attribute to match against the Moodle field above.';
$string['settings_user_match_source_primaryemail'] = 'primaryEmail';
$string['settings_user_match_source_sourcedid'] = 'sourcedId';
$string['settings_user_match_source_otheridentifier'] = 'otherIdentifiers: {$a}';
$string['settings_create_unmatched_users'] = 'When no matching user is found';
$string['settings_create_unmatched_users_desc'] = 'What to do with a Person that does not match any existing Moodle user.';
$string['settings_create_unmatched_users_skip'] = 'Skip unmatched persons (do not sync them)';
$string['settings_create_unmatched_users_create'] = 'Create a Moodle user when no match is found';
$string['settings_shortname_attribute'] = 'Course shortname source';
$string['settings_shortname_attribute_desc'] = 'The offering attribute used as the Moodle course shortname.';
$string['settings_shortname_attribute_sourcedid'] = 'sourcedId';
$string['settings_shortname_attribute_primarycode'] = 'primaryCode';

// Role mapping.
$string['settings_rolemapping'] = 'Role mapping';
$string['settings_rolemapping_generic_desc'] = 'The Moodle role assigned for each Edu-API RoleTypeEnum value. Choose "Do not enrol" to skip enrolling users with that role entirely.';
$string['settings_rolemapping_notmapped'] = 'Do not enrol';
$string['settings_rolemapping_member'] = 'Role mapping: Member';
$string['settings_rolemapping_chair'] = 'Role mapping: Chair';
$string['settings_rolemapping_staff'] = 'Role mapping: Staff';
$string['settings_rolemapping_student'] = 'Role mapping: Student';
$string['settings_rolemapping_administrator'] = 'Role mapping: Administrator';
$string['settings_rolemapping_aide'] = 'Role mapping: Aide';
$string['settings_rolemapping_guardian'] = 'Role mapping: Guardian';
$string['settings_rolemapping_parent'] = 'Role mapping: Parent';
$string['settings_rolemapping_proctor'] = 'Role mapping: Proctor';
$string['settings_rolemapping_relative'] = 'Role mapping: Relative';
$string['settings_rolemapping_teacher'] = 'Role mapping: Teacher';
$string['settings_rolemapping_advisor'] = 'Role mapping: Advisor';
$string['settings_rolemapping_teachingassistant'] = 'Role mapping: Teaching assistant';

// Enrollment status mapping.
$string['settings_enrollmentstatus'] = 'Enrollment status mapping';
$string['settings_enrollmentstatus_generic_desc'] = 'The sync action applied for each Edu-API EnrollmentStatusEnum value. "recordStatus = deleted" always unenrols, regardless of this mapping.';
$string['settings_enrollmentstatus_action_enrol_active'] = 'Enrol active';
$string['settings_enrollmentstatus_action_enrol_suspended'] = 'Enrol suspended';
$string['settings_enrollmentstatus_action_unenrol'] = 'Unenrol';
$string['settings_enrollmentstatus_action_ignore'] = 'Ignore (do not create; leave existing untouched)';
$string['settings_enrollmentstatus_mapping_enrolled'] = 'Enrollment status: Enrolled';
$string['settings_enrollmentstatus_mapping_registered'] = 'Enrollment status: Registered';
$string['settings_enrollmentstatus_mapping_accepted'] = 'Enrollment status: Accepted';
$string['settings_enrollmentstatus_mapping_pending'] = 'Enrollment status: Pending';
$string['settings_enrollmentstatus_mapping_deferred'] = 'Enrollment status: Deferred';
$string['settings_enrollmentstatus_mapping_onhold'] = 'Enrollment status: On hold';
$string['settings_enrollmentstatus_mapping_suspended'] = 'Enrollment status: Suspended';
$string['settings_enrollmentstatus_mapping_onleave'] = 'Enrollment status: On leave';
$string['settings_enrollmentstatus_mapping_interruption'] = 'Enrollment status: Interruption';
$string['settings_enrollmentstatus_mapping_finished'] = 'Enrollment status: Finished';
$string['settings_enrollmentstatus_mapping_withdrawnpassing'] = 'Enrollment status: Withdrawn (passing)';
$string['settings_enrollmentstatus_mapping_withdrawnfailing'] = 'Enrollment status: Withdrawn (failing)';
$string['settings_enrollmentstatus_mapping_withdrawn'] = 'Enrollment status: Withdrawn';
$string['settings_enrollmentstatus_mapping_dropped'] = 'Enrollment status: Dropped';
$string['settings_enrollmentstatus_mapping_cancelled'] = 'Enrollment status: Cancelled';
$string['settings_enrollmentstatus_mapping_revoked'] = 'Enrollment status: Revoked';
$string['settings_enrollmentstatus_mapping_declined'] = 'Enrollment status: Declined';

// Data to synchronise.
$string['settings_datasync'] = 'Data to synchronise';
$string['settings_datasync_organizations'] = 'Organizations';
$string['settings_datasync_organizations_desc'] = 'The organizations to synchronise. Populated by "Test connection" above.';
$string['settings_datasync_academic_session'] = 'Academic session';
$string['settings_datasync_academic_session_desc'] = 'The single academic session to synchronise. Populated by "Test connection" above.';
$string['settings_exclude_inactive'] = 'Exclude inactive records';
$string['settings_exclude_inactive_desc'] = 'Exclude organizations, offerings and persons whose recordStatus is \'inactive\' from the sync.';
$string['settings_keep_existing_courses'] = 'Keep existing courses';
$string['settings_keep_existing_courses_desc'] = 'Do not archive or remove Moodle courses that were previously synced but no longer appear in the source.';
