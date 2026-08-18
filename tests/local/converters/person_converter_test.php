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

use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\entities\person;
use progress_trace;
use stdClass;

/**
 * Tests for person_converter: pure username helpers, and convert()'s matching/ambiguity/creation
 * logic against a real (test) database.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\converters\person_converter
 */
final class person_converter_test extends \advanced_testcase {
    /**
     * Build a Person entity pre-seeded with data, so get()/get_id() never trigger a network fetch.
     *
     * @param   string $sourcedid
     * @param   array $data Raw fields, merged over sensible defaults
     * @return  person
     */
    protected function make_person(string $sourcedid, array $data = []): person {
        $container = $this->createMock(container_interface::class);
        $defaults = [
            'sourcedId' => $sourcedid,
            'recordStatus' => 'active',
            'legalName' => (object) ['givenName' => 'Test', 'familyName' => 'Person'],
            'primaryEmail' => null,
            'otherIdentifiers' => [],
        ];

        return new person($container, $sourcedid, (object) array_merge($defaults, $data));
    }

    /**
     * normalise_for_field() lowercases usernames only; email/idnumber are compared as stored.
     */
    public function test_normalise_for_field(): void {
        $this->assertSame('john.doe', person_converter::normalise_for_field('username', 'John.Doe'));
        $this->assertSame('John.Doe@Example.com', person_converter::normalise_for_field('email', 'John.Doe@Example.com'));
        $this->assertSame('ID-001', person_converter::normalise_for_field('idnumber', 'ID-001'));
    }

    /**
     * sanitise_username() transliterates a common accented character before stripping whatever is
     * still disallowed, instead of truncating the candidate at the first non-ASCII byte.
     */
    public function test_sanitise_username_transliterates_accents(): void {
        $this->assertSame('bertran-farre', person_converter::sanitise_username('Bertran-Farré', 'src-1'));
    }

    /**
     * sanitise_username() falls back to a stable hash-based username when the candidate sanitises to
     * an empty string.
     */
    public function test_sanitise_username_falls_back_to_hash_when_empty(): void {
        $expected = 'eduapi-' . substr(md5('src-empty'), 0, 8);

        $this->assertSame($expected, person_converter::sanitise_username('', 'src-empty'));
        $this->assertSame($expected, person_converter::sanitise_username('###', 'src-empty'));
    }

    /**
     * derive_username() reuses the matched value directly when the matched Moodle field is itself
     * 'username'.
     */
    public function test_derive_username_prefers_moodlefield_username(): void {
        $person = $this->make_person('src-1');

        $this->assertSame('john.doe', person_converter::derive_username($person, 'username', 'John.Doe'));
    }

    /**
     * derive_username() falls back to the local part of primaryEmail when the matched field is not
     * 'username'.
     */
    public function test_derive_username_falls_back_to_email_local_part(): void {
        $person = $this->make_person('src-1', [
            'primaryEmail' => (object) ['email' => 'Jane.Smith@example.org'],
        ]);

        $this->assertSame('jane.smith', person_converter::derive_username($person, 'email', 'jane.smith@example.org'));
    }

    /**
     * derive_username() falls back to a sanitised sourcedId when there is no primaryEmail at all.
     */
    public function test_derive_username_falls_back_to_sourcedid_when_no_email(): void {
        $person = $this->make_person('Person-123');

        $this->assertSame('person-123', person_converter::derive_username($person, 'idnumber', 'Person-123'));
    }

    /**
     * convert() skips a Person when the matched Moodle field/value is shared by more than one Moodle
     * user (Moodle does not enforce uniqueness on 'idnumber'), and reports the ambiguity to the trace.
     */
    public function test_convert_skips_ambiguous_match(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $generator->create_user(['idnumber' => 'shared-idnumber']);
        $generator->create_user(['idnumber' => 'shared-idnumber']);

        set_config('user_match_moodlefield', 'idnumber', 'enrol_eduapi');
        set_config('user_match_source', 'sourcedId', 'enrol_eduapi');
        set_config('create_unmatched_users', 0, 'enrol_eduapi');

        $person = $this->make_person('shared-idnumber');

        $messages = [];
        $trace = $this->createMock(progress_trace::class);
        $trace->method('output')->willReturnCallback(function ($message) use (&$messages) {
            $messages[] = $message;
        });

        $result = person_converter::convert($person, $trace);

        $this->assertNull($result);
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('shared-idnumber', implode(' ', $messages));
    }

    /**
     * convert() returns null (skips) an unmatched Person when create_unmatched_users is off (the
     * documented default).
     */
    public function test_convert_returns_null_when_unmatched_and_not_creating(): void {
        global $DB;

        $this->resetAfterTest();

        set_config('user_match_moodlefield', 'email', 'enrol_eduapi');
        set_config('user_match_source', 'primaryEmail', 'enrol_eduapi');
        set_config('create_unmatched_users', 0, 'enrol_eduapi');

        $person = $this->make_person('unmatched-1', [
            'primaryEmail' => (object) ['email' => 'nobody.here@example.org'],
        ]);

        $usercountbefore = $DB->count_records('user');

        $this->assertNull(person_converter::convert($person));
        $this->assertEquals($usercountbefore, $DB->count_records('user'));
    }

    /**
     * convert() provisions a new Moodle user from the Person's data when create_unmatched_users is on,
     * and persists the sourcedId <-> userid mapping.
     */
    public function test_convert_creates_unmatched_user_when_enabled(): void {
        global $DB;

        $this->resetAfterTest();

        set_config('user_match_moodlefield', 'email', 'enrol_eduapi');
        set_config('user_match_source', 'primaryEmail', 'enrol_eduapi');
        set_config('create_unmatched_users', 1, 'enrol_eduapi');

        $person = $this->make_person('new-person-1', [
            'legalName' => (object) ['givenName' => 'Ada', 'familyName' => 'Lovelace'],
            'primaryEmail' => (object) ['email' => 'ada.lovelace@example.org'],
        ]);

        $result = person_converter::convert($person);

        $this->assertNotNull($result);
        $this->assertSame('ada.lovelace@example.org', $result->email);
        $this->assertTrue($DB->record_exists('user', ['id' => $result->id, 'deleted' => 0]));
        $this->assertEquals(
            $result->id,
            person_converter::get_mapped_userid('new-person-1')
        );
    }

    /**
     * convert() re-uses the mapping stored in a previous sync run instead of matching again.
     */
    public function test_convert_reuses_existing_mapping(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        person_converter::save_mapping('already-mapped-1', $user->id);

        // Matching config deliberately points somewhere that would NOT match this user, to prove the
        // stored mapping - not a fresh field match - is what convert() used.
        set_config('user_match_moodlefield', 'email', 'enrol_eduapi');
        set_config('user_match_source', 'primaryEmail', 'enrol_eduapi');

        $person = $this->make_person('already-mapped-1', [
            'primaryEmail' => (object) ['email' => 'does-not-match-anything@example.org'],
        ]);

        $result = person_converter::convert($person);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }
}
