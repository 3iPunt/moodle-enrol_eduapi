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

namespace enrol_eduapi\external;

use core_external\external_api;
use invalid_parameter_exception;
use required_capability_exception;

/**
 * Tests for offering_webhook's web service parameter definition and access control.
 *
 * Deliberately does NOT exercise execute()'s full sync happy path (that would require a real or
 * mocked Edu-API provider, already covered indirectly by eduapi_client_test.php and the converter
 * tests) - only the security/validation checklist this external function follows: validate_parameters()
 * first, then validate_context() + require_capability(), before touching anything else. This is also
 * the specific gap this implementation deliberately closes relative to enrol_oneroster's
 * class_webhook, which calls neither.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\external\offering_webhook
 */
final class offering_webhook_test extends \advanced_testcase {
    /**
     * execute_parameters() declares 'sourcedId' as required and 'organization' as an optional,
     * defaulting-to-null parameter.
     */
    public function test_execute_parameters_structure(): void {
        $params = offering_webhook::execute_parameters();
        $keys = $params->keys;

        $this->assertArrayHasKey('sourcedId', $keys);
        $this->assertArrayHasKey('organization', $keys);
        $this->assertSame(VALUE_REQUIRED, $keys['sourcedId']->required);
        $this->assertSame(VALUE_DEFAULT, $keys['organization']->required);
        $this->assertNull($keys['organization']->default);
    }

    /**
     * validate_parameters() rejects a call missing the required 'sourcedId' parameter.
     */
    public function test_validate_parameters_requires_sourcedid(): void {
        $this->expectException(invalid_parameter_exception::class);

        external_api::validate_parameters(
            offering_webhook::execute_parameters(),
            ['organization' => 'org-1']
        );
    }

    /**
     * validate_parameters() accepts a call with only the required 'sourcedId', defaulting
     * 'organization' to null.
     */
    public function test_validate_parameters_accepts_sourcedid_only(): void {
        $params = external_api::validate_parameters(
            offering_webhook::execute_parameters(),
            ['sourcedId' => 'offering-1']
        );

        $this->assertSame('offering-1', $params['sourcedId']);
        $this->assertNull($params['organization']);
    }

    /**
     * execute() enforces moodle/site:config before doing anything else: a user without that
     * capability is refused, even with well-formed parameters and no Edu-API configuration at all.
     */
    public function test_execute_requires_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(required_capability_exception::class);

        offering_webhook::execute('offering-1');
    }

    /**
     * execute_returns() declares 'result', 'warnings' and 'messages'.
     */
    public function test_execute_returns_structure(): void {
        $returns = offering_webhook::execute_returns();

        $this->assertArrayHasKey('result', $returns->keys);
        $this->assertArrayHasKey('warnings', $returns->keys);
        $this->assertArrayHasKey('messages', $returns->keys);
    }
}
