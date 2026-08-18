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
 * Edu-API Enrolment Client.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_eduapi\local\converters;

use enrol_eduapi\local\v1p0\entities\enrollment;
use progress_trace;

/**
 * Converts an Edu-API Enrollment entity into a Moodle enrolment + role assignment.
 *
 * Per spec.md's entity mapping table and "Lifecycle and states" section:
 * - `role` (RoleTypeEnum) maps to a Moodle role id via the `role_mapping_<roletype>` settings, with an
 *   explicit "Do not enrol" (not mapped) option — see resolve_role_id().
 * - `enrollmentStatus` (17 values) maps to one of 4 actions via the `enrollmentstatus_mapping_<estado>`
 *   settings — see resolve_action().
 * - `recordStatus = deleted` ALWAYS forces unenrolment, overriding whatever the `enrollmentStatus`
 *   mapping says.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrollment_converter {
    /** @var string Action: create/update the enrolment in Moodle's active state */
    const ACTION_ENROL_ACTIVE = 'enrol_active';

    /** @var string Action: create/update the enrolment in Moodle's suspended state */
    const ACTION_ENROL_SUSPENDED = 'enrol_suspended';

    /** @var string Action: unenrol the user, if currently enrolled */
    const ACTION_UNENROL = 'unenrol';

    /** @var string Action: no sync action at all — do not create, do not touch an existing enrolment */
    const ACTION_IGNORE = 'ignore';

    /** @var string[] All valid action values, for validating a stored setting */
    const VALID_ACTIONS = [
        self::ACTION_ENROL_ACTIVE,
        self::ACTION_ENROL_SUSPENDED,
        self::ACTION_UNENROL,
        self::ACTION_IGNORE,
    ];

    /**
     * Initial proposed defaults for the `EnrollmentStatusEnum` → action mapping, per spec.md (marked
     * there as "initial proposal, not validated against real provider cases"). Used only as a
     * fallback when the corresponding `enrollmentstatus_mapping_<estado>` setting has not been
     * configured yet (e.g. before development phase 4's settings.php exists, or before an
     * administrator has visited the settings page).
     */
    const DEFAULT_STATUS_ACTIONS = [
        'enrolled' => self::ACTION_ENROL_ACTIVE,
        'registered' => self::ACTION_ENROL_ACTIVE,
        'accepted' => self::ACTION_ENROL_ACTIVE,

        'pending' => self::ACTION_ENROL_SUSPENDED,
        'deferred' => self::ACTION_ENROL_SUSPENDED,
        'onHold' => self::ACTION_ENROL_SUSPENDED,
        'suspended' => self::ACTION_ENROL_SUSPENDED,
        'onLeave' => self::ACTION_ENROL_SUSPENDED,
        'interruption' => self::ACTION_ENROL_SUSPENDED,
        'finished' => self::ACTION_ENROL_SUSPENDED,
        'withdrawnPassing' => self::ACTION_ENROL_SUSPENDED,
        'withdrawnFailing' => self::ACTION_ENROL_SUSPENDED,

        'withdrawn' => self::ACTION_UNENROL,
        'dropped' => self::ACTION_UNENROL,
        'cancelled' => self::ACTION_UNENROL,
        'revoked' => self::ACTION_UNENROL,
        'declined' => self::ACTION_UNENROL,
    ];

    /**
     * Default Moodle role shortname per `RoleTypeEnum` value, per spec.md. Values not present here
     * (aide, guardian, parent, proctor, relative, member, chair, advisor, teachingAssistant) default to
     * "Do not enrol" (null) — replicating enrol_oneroster's precedent of leaving those same roles
     * unmapped ("not currently supported").
     */
    const DEFAULT_ROLE_SHORTNAMES = [
        'student' => 'student',
        'teacher' => 'editingteacher',
        'administrator' => 'manager',
    ];

    /**
     * Resolve the Moodle role id for the given `RoleTypeEnum` value.
     *
     * Reads the `role_mapping_<roletype>` setting; '0'/empty means "Do not enrol" and returns null.
     * Falls back to DEFAULT_ROLE_SHORTNAMES (resolved to a real role id via a read-only `role` table
     * lookup) when the setting has not been configured yet.
     *
     * @param   string $roletype A RoleTypeEnum value (or an `ext:` extension, which is never mapped)
     * @return  int|null The Moodle role id, or null if this role should not be enrolled ("Do not enrol")
     */
    public static function resolve_role_id(string $roletype): ?int {
        global $DB;

        $configured = get_config('enrol_eduapi', "role_mapping_{$roletype}");
        if ($configured !== false && $configured !== '') {
            $roleid = (int) $configured;
            return $roleid > 0 ? $roleid : null;
        }

        $defaultshortname = self::DEFAULT_ROLE_SHORTNAMES[$roletype] ?? null;
        if ($defaultshortname === null) {
            return null;
        }

        $roleid = $DB->get_field('role', 'id', ['shortname' => $defaultshortname]);

        return $roleid ? (int) $roleid : null;
    }

    /**
     * Resolve the sync action for the given `enrollmentStatus` and `recordStatus` pair.
     *
     * `recordStatus = deleted` always wins, regardless of the `enrollmentStatus` mapping (spec.md: "the
     * EnrollmentStatusEnum mapping cannot override this rule").
     *
     * Pure logic plus a `get_config()` read (no database mutation), so this is fully testable without
     * a live sync.
     *
     * @param   string $enrollmentstatus One of the 17 EnrollmentStatusEnum values (or an `ext:` value)
     * @param   string $recordstatus One of 'active', 'deleted', 'inactive'
     * @return  string One of the ACTION_* constants
     */
    public static function resolve_action(string $enrollmentstatus, string $recordstatus): string {
        if ($recordstatus === 'deleted') {
            return self::ACTION_UNENROL;
        }

        $configured = get_config('enrol_eduapi', "enrollmentstatus_mapping_{$enrollmentstatus}");
        if (in_array($configured, self::VALID_ACTIONS, true)) {
            return $configured;
        }

        return self::DEFAULT_STATUS_ACTIONS[$enrollmentstatus] ?? self::ACTION_IGNORE;
    }

    /**
     * Apply an Enrollment entity to Moodle: (un)enrol the matched user in the matched course's
     * `enrol_eduapi` instance, and assign the resolved role.
     *
     * DB-mutating (reads/writes `user_enrolments` and `role_assignments` via the enrol_eduapi plugin
     * API). Not exercised during development phase 3 verification — see this converter's class
     * docblock and TIPEDUAPI-14's verification report. Depends on person_converter (to resolve the
     * Moodle user) and offering_converter (to resolve the course's enrol_eduapi instance), both of
     * which must have already processed the corresponding Person/offering in the same sync run.
     *
     * @param   enrollment $enrollment
     * @param   progress_trace|null $trace Sync trace forwarded to person_converter::convert() so an
     *                                     ambiguous person match can be reported; optional
     * @return  void
     */
    public static function convert(enrollment $enrollment, ?progress_trace $trace = null): void {
        global $DB;

        $person = $enrollment->get_person();
        $moodleuser = person_converter::convert($person, $trace);
        if ($moodleuser === null) {
            // No matching Moodle user for this person: nothing to (un)enrol.
            return;
        }

        $instance = offering_converter::get_enrol_instance_for_offering($enrollment->get('educationOffering'));
        if ($instance === null) {
            // The offering has not been synced to a Moodle course (yet), so there is nothing to
            // (un)enrol into.
            return;
        }

        $roleid = self::resolve_role_id($enrollment->get('role'));
        if ($roleid === null) {
            // This RoleTypeEnum value is mapped to "Do not enrol": skip this enrolment entirely.
            return;
        }

        $action = self::resolve_action($enrollment->get('enrollmentStatus'), $enrollment->get('recordStatus'));

        $plugin = enrol_get_plugin('eduapi');
        $existing = $DB->get_record('user_enrolments', ['userid' => $moodleuser->id, 'enrolid' => $instance->id]);

        switch ($action) {
            case self::ACTION_IGNORE:
                // Do not create; do not touch an existing enrolment.
                return;

            case self::ACTION_UNENROL:
                if ($existing) {
                    $plugin->unenrol_user($instance, $moodleuser->id);
                }
                return;

            case self::ACTION_ENROL_ACTIVE:
            case self::ACTION_ENROL_SUSPENDED:
                $status = $action === self::ACTION_ENROL_SUSPENDED ? ENROL_USER_SUSPENDED : ENROL_USER_ACTIVE;

                if ($existing) {
                    if ((int) $existing->status !== $status) {
                        $plugin->update_user_enrol($instance, $moodleuser->id, $status);
                    }
                } else {
                    $plugin->enrol_user($instance, $moodleuser->id, $roleid, 0, 0, $status);
                }

                $context = \context_course::instance($instance->courseid);
                if (
                    !$DB->record_exists('role_assignments', [
                    'roleid' => $roleid,
                    'contextid' => $context->id,
                    'userid' => $moodleuser->id,
                    'component' => "enrol_{$instance->enrol}",
                    'itemid' => $instance->id,
                    ])
                ) {
                    role_assign($roleid, $moodleuser->id, $context->id, "enrol_{$instance->enrol}", $instance->id);
                }
                return;
        }
    }
}
