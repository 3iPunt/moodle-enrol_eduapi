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

/**
 * Tests for enrollment_converter::resolve_action() - the EnrollmentStatusEnum -> action mapping and
 * the recordStatus = deleted override.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\converters\enrollment_converter
 */
final class enrollment_converter_test extends \advanced_testcase {
    /**
     * Data provider: every EnrollmentStatusEnum value covered by DEFAULT_STATUS_ACTIONS, paired with
     * the action it should resolve to when no `enrollmentstatus_mapping_<estado>` setting has been
     * configured.
     *
     * @return array
     */
    public static function default_status_action_provider(): array {
        return [
            'enrolled -> enrol_active' => ['enrolled', enrollment_converter::ACTION_ENROL_ACTIVE],
            'registered -> enrol_active' => ['registered', enrollment_converter::ACTION_ENROL_ACTIVE],
            'accepted -> enrol_active' => ['accepted', enrollment_converter::ACTION_ENROL_ACTIVE],
            'pending -> enrol_suspended' => ['pending', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'deferred -> enrol_suspended' => ['deferred', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'onHold -> enrol_suspended' => ['onHold', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'suspended -> enrol_suspended' => ['suspended', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'onLeave -> enrol_suspended' => ['onLeave', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'interruption -> enrol_suspended' => ['interruption', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'finished -> enrol_suspended' => ['finished', enrollment_converter::ACTION_ENROL_SUSPENDED],
            'withdrawnPassing -> enrol_suspended' => [
                'withdrawnPassing',
                enrollment_converter::ACTION_ENROL_SUSPENDED,
            ],
            'withdrawnFailing -> enrol_suspended' => [
                'withdrawnFailing',
                enrollment_converter::ACTION_ENROL_SUSPENDED,
            ],
            'withdrawn -> unenrol' => ['withdrawn', enrollment_converter::ACTION_UNENROL],
            'dropped -> unenrol' => ['dropped', enrollment_converter::ACTION_UNENROL],
            'cancelled -> unenrol' => ['cancelled', enrollment_converter::ACTION_UNENROL],
            'revoked -> unenrol' => ['revoked', enrollment_converter::ACTION_UNENROL],
            'declined -> unenrol' => ['declined', enrollment_converter::ACTION_UNENROL],
        ];
    }

    /**
     * The 17 EnrollmentStatusEnum values resolve to their documented default action when no override
     * is configured, and recordStatus is not 'deleted'.
     *
     * @dataProvider default_status_action_provider
     * @param string $enrollmentstatus
     * @param string $expected
     */
    public function test_resolve_action_default_mapping(string $enrollmentstatus, string $expected): void {
        $this->resetAfterTest();

        $this->assertSame($expected, enrollment_converter::resolve_action($enrollmentstatus, 'active'));
    }

    /**
     * An EnrollmentStatusEnum value with no default and no configured override falls back to "ignore".
     */
    public function test_resolve_action_unmapped_status_defaults_to_ignore(): void {
        $this->resetAfterTest();

        $this->assertSame(
            enrollment_converter::ACTION_IGNORE,
            enrollment_converter::resolve_action('ext:someVendorExtension', 'active')
        );
    }

    /**
     * A configured `enrollmentstatus_mapping_<estado>` setting overrides the documented default.
     */
    public function test_resolve_action_respects_configured_override(): void {
        $this->resetAfterTest();

        // Default for 'enrolled' is enrol_active; override it to unenrol.
        set_config('enrollmentstatus_mapping_enrolled', enrollment_converter::ACTION_UNENROL, 'enrol_eduapi');

        $this->assertSame(
            enrollment_converter::ACTION_UNENROL,
            enrollment_converter::resolve_action('enrolled', 'active')
        );
    }

    /**
     * An invalid stored value for `enrollmentstatus_mapping_<estado>` (e.g. corrupted config) is
     * ignored and the default is used instead, rather than resolving to a bogus action.
     */
    public function test_resolve_action_ignores_invalid_configured_value(): void {
        $this->resetAfterTest();

        set_config('enrollmentstatus_mapping_enrolled', 'not-a-real-action', 'enrol_eduapi');

        $this->assertSame(
            enrollment_converter::ACTION_ENROL_ACTIVE,
            enrollment_converter::resolve_action('enrolled', 'active')
        );
    }

    /**
     * recordStatus = 'deleted' always forces unenrol, regardless of the enrollmentStatus mapping -
     * including when a configured override says otherwise, and for statuses with no default mapping
     * at all.
     *
     * @dataProvider default_status_action_provider
     * @param string $enrollmentstatus
     */
    public function test_resolve_action_record_status_deleted_overrides_everything(string $enrollmentstatus): void {
        $this->resetAfterTest();

        // Even an explicit override to "enrol_active" must not survive a deleted recordStatus.
        set_config(
            "enrollmentstatus_mapping_{$enrollmentstatus}",
            enrollment_converter::ACTION_ENROL_ACTIVE,
            'enrol_eduapi'
        );

        $this->assertSame(
            enrollment_converter::ACTION_UNENROL,
            enrollment_converter::resolve_action($enrollmentstatus, 'deleted')
        );
    }

    /**
     * recordStatus = 'deleted' overrides even a completely unmapped enrollmentStatus value.
     */
    public function test_resolve_action_record_status_deleted_overrides_unmapped_status(): void {
        $this->resetAfterTest();

        $this->assertSame(
            enrollment_converter::ACTION_UNENROL,
            enrollment_converter::resolve_action('ext:someVendorExtension', 'deleted')
        );
    }
}
