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

use core_user;
use enrol_eduapi\tests\fixtures\entity_fixtures_trait;
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
    use entity_fixtures_trait;

    /**
     * normalise_for_field() lowercases usernames only; email/idnumber are compared as stored.
     */
    public function test_normalise_for_field(): void {
        $this->assertSame('john.doe', person_converter::normalise_for_field('username', 'John.Doe'));
        $this->assertSame('John.Doe@Example.com', person_converter::normalise_for_field('email', 'John.Doe@Example.com'));
        $this->assertSame('ID-001', person_converter::normalise_for_field('idnumber', 'ID-001'));
    }

    /**
     * normalise_for_field() trims the value, so a match source value already trimmed by
     * resolve_source_value() converges with what is compared/stored here.
     */
    public function test_normalise_for_field_trims_whitespace(): void {
        $this->assertSame('john.doe', person_converter::normalise_for_field('username', '  John.Doe  '));
        $this->assertSame('person@example.org', person_converter::normalise_for_field('email', '  person@example.org  '));
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

    /**
     * resolve_source_value() returns the `primaryEmail` email when present.
     */
    public function test_resolve_source_value_primaryemail_present(): void {
        $person = $this->make_person('src-1', [
            'primaryEmail' => (object) ['email' => 'person@example.org'],
        ]);

        $this->assertSame('person@example.org', person_converter::resolve_source_value($person, 'primaryEmail'));
    }

    /**
     * resolve_source_value() returns null for `primaryEmail` when the person has none.
     */
    public function test_resolve_source_value_primaryemail_absent(): void {
        $person = $this->make_person('src-1');

        $this->assertNull(person_converter::resolve_source_value($person, 'primaryEmail'));
    }

    /**
     * resolve_source_value() returns the sourcedId for the `sourcedId` source.
     */
    public function test_resolve_source_value_sourcedid(): void {
        $person = $this->make_person('src-1');

        $this->assertSame('src-1', person_converter::resolve_source_value($person, 'sourcedId'));
    }

    /**
     * resolve_source_value() looks up a dotted `otherIdentifiers.<identifierType>` source directly
     * against the person's `otherIdentifiers` entries.
     */
    public function test_resolve_source_value_otheridentifiers_dotted_present(): void {
        $person = $this->make_person('src-1', [
            'otherIdentifiers' => [
                (object) ['identifier' => 'ORG-001', 'identifierType' => 'systemId'],
            ],
        ]);

        $this->assertSame(
            'ORG-001',
            person_converter::resolve_source_value($person, 'otherIdentifiers.systemId')
        );
    }

    /**
     * resolve_source_value() returns null for a dotted `otherIdentifiers.<identifierType>` source when
     * no `otherIdentifiers` entry of that type exists, without leaking the person's own sourcedId or
     * primaryEmail through the `sourcedId`/`primaryEmail` identifierType names.
     */
    public function test_resolve_source_value_otheridentifiers_dotted_does_not_leak_special_sources(): void {
        $person = $this->make_person('src-1', [
            'primaryEmail' => (object) ['email' => 'person@example.org'],
            'otherIdentifiers' => [
                (object) ['identifier' => 'ORG-001', 'identifierType' => 'systemId'],
            ],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'otherIdentifiers.sourcedId'));
        $this->assertNull(person_converter::resolve_source_value($person, 'otherIdentifiers.primaryEmail'));
    }

    /**
     * resolve_source_value() returns null for a dotted `otherIdentifiers.<identifierType>` source when
     * no `otherIdentifiers` entry of that (unrelated, non-leaking) type exists.
     */
    public function test_resolve_source_value_otheridentifiers_dotted_missing(): void {
        $person = $this->make_person('src-1', [
            'otherIdentifiers' => [
                (object) ['identifier' => 'ORG-001', 'identifierType' => 'systemId'],
            ],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'otherIdentifiers.sisSourcedId'));
    }

    /**
     * resolve_source_value() returns null for `otherIdentifiers.` (a dot with an empty key), the same
     * way it does for `extensions.`: the empty-key guard applies to both prefixes.
     */
    public function test_resolve_source_value_otheridentifiers_empty_key(): void {
        $person = $this->make_person('src-1', [
            'otherIdentifiers' => [
                (object) ['identifier' => 'ORG-001', 'identifierType' => 'systemId'],
            ],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'otherIdentifiers.'));
    }

    /**
     * resolve_source_value() has no fallback for a bare (dot-less) source that is neither
     * `primaryEmail` nor `sourcedId`: it resolves to null even when the person happens to carry an
     * `otherIdentifiers` entry whose `identifierType` equals that same bare string, proving there is
     * no legacy fallback to an undotted `otherIdentifiers` lookup. A value stored before this grammar
     * was tightened is migrated to the dotted `otherIdentifiers.<identifierType>` form by the
     * `db/upgrade.php` step instead.
     */
    public function test_resolve_source_value_bare_dotless_source_has_no_fallback(): void {
        $person = $this->make_person('src-1', [
            'otherIdentifiers' => [
                (object) ['identifier' => 'ORG-001', 'identifierType' => 'extensions'],
            ],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'extensions'));
    }

    /**
     * resolve_source_value() reads a top-level `extensions` key when it is a string.
     */
    public function test_resolve_source_value_extensions_string(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => 'Engineering'],
        ]);

        $this->assertSame('Engineering', person_converter::resolve_source_value($person, 'extensions.department'));
    }

    /**
     * resolve_source_value() casts an int `extensions` value to its decimal string.
     */
    public function test_resolve_source_value_extensions_int_cast_to_string(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['costcentre' => 42],
        ]);

        $this->assertSame('42', person_converter::resolve_source_value($person, 'extensions.costcentre'));
    }

    /**
     * resolve_source_value() ignores a float `extensions` value: only strings and ints are cast.
     */
    public function test_resolve_source_value_extensions_float_ignored(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['gpa' => 3.75],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'extensions.gpa'));
    }

    /**
     * resolve_source_value() ignores a nested object `extensions` value, rather than casting it to a
     * string.
     */
    public function test_resolve_source_value_extensions_object_ignored(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => (object) ['name' => 'Engineering']],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'extensions.department'));
    }

    /**
     * resolve_source_value() ignores an array `extensions` value, rather than casting it to a string.
     */
    public function test_resolve_source_value_extensions_array_ignored(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['campuses' => ['north', 'south']],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'extensions.campuses'));
    }

    /**
     * resolve_source_value() returns null when the requested `extensions` key is not present.
     */
    public function test_resolve_source_value_extensions_key_missing(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => 'Engineering'],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'extensions.institution'));
    }

    /**
     * resolve_source_value() returns null for `extensions.` (a dot with an empty key).
     */
    public function test_resolve_source_value_extensions_empty_key(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => 'Engineering'],
        ]);

        $this->assertNull(person_converter::resolve_source_value($person, 'extensions.'));
    }

    /**
     * resolve_source_value() returns null for a dotted source with an unknown prefix.
     */
    public function test_resolve_source_value_unknown_dotted_prefix(): void {
        $person = $this->make_person('src-1');

        $this->assertNull(person_converter::resolve_source_value($person, 'foo.bar'));
    }

    /**
     * resolve_source_value() trims the resolved value and treats a whitespace-only value as absent
     * (null), rather than as an empty string.
     */
    public function test_resolve_source_value_trims_whitespace(): void {
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => '  Engineering  ', 'empty' => '   '],
        ]);

        $this->assertSame('Engineering', person_converter::resolve_source_value($person, 'extensions.department'));
        $this->assertNull(person_converter::resolve_source_value($person, 'extensions.empty'));
    }

    /**
     * Every `otherIdentifiers.<identifierType>` source built by
     * person_converter::build_other_identifier_source() - the same helper settings.php uses to build
     * the `user_match_source` select's option values - resolves through resolve_source_value() for a
     * person carrying an `otherIdentifiers` entry of that type. Keep this list in sync with the
     * $identifiertypes array in settings.php.
     */
    public function test_resolve_source_value_resolves_every_settings_identifier_type(): void {
        $identifiertypes = [
            'systemId', 'productId', 'userName', 'accountId', 'emailAddress', 'nationalIdentityNumber',
            'isbn', 'issn', 'lisSourcedId', 'oneRosterSourcedId', 'sisSourcedId', 'ltiContextId',
            'ltiDeploymentId', 'ltiToolId', 'ltiPlatformId', 'ltiUserId', 'identifier',
        ];

        foreach ($identifiertypes as $identifiertype) {
            $person = $this->make_person('src-1', [
                'otherIdentifiers' => [
                    (object) ['identifier' => "VALUE-{$identifiertype}", 'identifierType' => $identifiertype],
                ],
            ]);

            $source = person_converter::build_other_identifier_source($identifiertype);

            $this->assertSame(
                "VALUE-{$identifiertype}",
                person_converter::resolve_source_value($person, $source),
                "Failed for identifierType '{$identifiertype}'"
            );
        }
    }

    /**
     * build_new_user_data() sets `department` and `institution` from the configured sources when
     * creating a new user.
     */
    public function test_build_new_user_data_sets_department_and_institution_when_configured(): void {
        $this->resetAfterTest();

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');
        set_config('user_field_institution_source', 'otherIdentifiers.systemId', 'enrol_eduapi');

        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => 'Engineering'],
            'otherIdentifiers' => [
                (object) ['identifier' => 'Acme University', 'identifierType' => 'systemId'],
            ],
        ]);

        $userdata = person_converter::build_new_user_data($person, 'email', 'src-1@example.org');

        $this->assertSame('Engineering', $userdata->department);
        $this->assertSame('Acme University', $userdata->institution);
    }

    /**
     * build_new_user_data() leaves `department`/`institution` unset when no source is configured, even
     * when the person carries matching extensions data.
     */
    public function test_build_new_user_data_leaves_fields_unset_when_no_source_configured(): void {
        $this->resetAfterTest();

        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => 'Engineering'],
        ]);

        $userdata = person_converter::build_new_user_data($person, 'email', 'src-1@example.org');

        $this->assertObjectNotHasProperty('department', $userdata);
        $this->assertObjectNotHasProperty('institution', $userdata);
    }

    /**
     * build_new_user_data() trims a `primaryEmail` value carrying leading/trailing whitespace, so the
     * stored `email` field converges with a `primaryEmail` value resolved (and trimmed) elsewhere.
     */
    public function test_build_new_user_data_trims_primaryemail_whitespace(): void {
        $this->resetAfterTest();

        $person = $this->make_person('src-1', [
            'primaryEmail' => (object) ['email' => '  ada.lovelace@example.org  '],
        ]);

        $userdata = person_converter::build_new_user_data($person, 'idnumber', 'src-1');

        $this->assertSame('ada.lovelace@example.org', $userdata->email);
    }

    /**
     * build_new_user_data() truncates a configured field's resolved value to
     * person_converter::PROFILE_FIELD_MAXLENGTH characters when it exceeds the Moodle
     * user.department/user.institution column length.
     */
    public function test_build_new_user_data_truncates_long_extension_value(): void {
        $this->resetAfterTest();

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');

        $longvalue = str_repeat('x', 300);
        $person = $this->make_person('src-1', [
            'extensions' => (object) ['department' => $longvalue],
        ]);

        $userdata = person_converter::build_new_user_data($person, 'email', 'src-1@example.org');

        $this->assertSame(person_converter::PROFILE_FIELD_MAXLENGTH, strlen($userdata->department));
        $this->assertSame(substr($longvalue, 0, person_converter::PROFILE_FIELD_MAXLENGTH), $userdata->department);
    }

    /**
     * convert() updates the `department` field on an already-mapped existing user when the configured
     * source resolves to a value different from what is currently stored.
     */
    public function test_convert_updates_department_on_mapped_user_when_different(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => 'Old department']);
        person_converter::save_mapping('mapped-1', $user->id);

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');

        $person = $this->make_person('mapped-1', [
            'extensions' => (object) ['department' => 'New department'],
        ]);

        $messages = [];
        $trace = $this->capturing_trace($messages);

        $result = person_converter::convert($person, $trace);

        $this->assertNotNull($result);
        $this->assertSame('New department', $result->department);
        $this->assertSame('New department', $DB->get_field('user', 'department', ['id' => $user->id]));
        $this->assertStringContainsString("Person 'mapped-1': updated department to 'New department'", implode(' ', $messages));
    }

    /**
     * convert() does not trigger a user update when the configured source resolves to the value already
     * stored on the user (the stored record, including `timemodified`, is left untouched).
     */
    public function test_convert_does_not_update_when_value_already_matches(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => 'Engineering']);
        person_converter::save_mapping('mapped-2', $user->id);

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');

        $person = $this->make_person('mapped-2', [
            'extensions' => (object) ['department' => 'Engineering'],
        ]);

        $before = $DB->get_record('user', ['id' => $user->id]);

        $result = person_converter::convert($person);

        $after = $DB->get_record('user', ['id' => $user->id]);

        $this->assertNotNull($result);
        $this->assertEquals($before, $after);
    }

    /**
     * convert() leaves the `department` field untouched (does not blank it) when the configured source
     * resolves to null because the person has no value for that attribute.
     */
    public function test_convert_leaves_field_untouched_when_attribute_absent(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => 'Existing department']);
        person_converter::save_mapping('mapped-3', $user->id);

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');

        // The person has extensions, but not the configured key.
        $person = $this->make_person('mapped-3', [
            'extensions' => (object) ['other' => 'value'],
        ]);

        $result = person_converter::convert($person);

        $this->assertNotNull($result);
        $this->assertSame('Existing department', $result->department);
        $this->assertSame('Existing department', $DB->get_field('user', 'department', ['id' => $user->id]));
    }

    /**
     * convert() leaves `department`/`institution` untouched when no source is configured, even when the
     * person carries matching extensions data.
     */
    public function test_convert_leaves_fields_untouched_when_no_source_configured(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => 'Existing department']);
        person_converter::save_mapping('mapped-4', $user->id);

        $person = $this->make_person('mapped-4', [
            'extensions' => (object) ['department' => 'Some other department'],
        ]);

        $before = $DB->get_record('user', ['id' => $user->id]);

        $result = person_converter::convert($person);

        $after = $DB->get_record('user', ['id' => $user->id]);

        $this->assertNotNull($result);
        $this->assertSame('Existing department', $result->department);
        $this->assertEquals($before, $after);
    }

    /**
     * convert() cleans the resolved profile field value (core_user::clean_field(), the same cleaning
     * user_update_user() itself would apply) before both storing it and comparing it against what is
     * already stored, so a value containing tags or other content PARAM_TEXT strips converges to the
     * cleaned value instead of being re-written on every sync.
     */
    public function test_convert_cleans_department_value_before_comparing(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => '']);
        person_converter::save_mapping('mapped-clean-1', $user->id);

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');

        $person = $this->make_person('mapped-clean-1', [
            'extensions' => (object) ['department' => 'R&D <Robotics>'],
        ]);

        $expected = core_user::clean_field('R&D <Robotics>', 'department');

        $messages = [];
        $trace = $this->capturing_trace($messages);
        $result = person_converter::convert($person, $trace);

        $this->assertSame($expected, $result->department);
        $this->assertSame($expected, $DB->get_field('user', 'department', ['id' => $user->id]));
        $this->assertNotEmpty($messages);

        // A second sync with the same source data must not re-write the field: the stored value is
        // already clean, so it now compares equal to the freshly-cleaned resolved value.
        $messages = [];
        $trace = $this->capturing_trace($messages);
        $result2 = person_converter::convert($person, $trace);

        $this->assertSame($expected, $result2->department);
        $this->assertEmpty($messages);
    }

    /**
     * apply_profile_fields() leaves an existing user's configured fields untouched when
     * `user_field_update_existing` is explicitly disabled, even though a source is configured and
     * resolves to a value different from what is stored.
     */
    public function test_convert_does_not_update_existing_user_when_update_existing_disabled(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => 'Old department']);
        person_converter::save_mapping('mapped-noupdate-1', $user->id);

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');
        set_config('user_field_update_existing', 0, 'enrol_eduapi');

        $person = $this->make_person('mapped-noupdate-1', [
            'extensions' => (object) ['department' => 'New department'],
        ]);

        $messages = [];
        $trace = $this->capturing_trace($messages);

        $result = person_converter::convert($person, $trace);

        $this->assertNotNull($result);
        $this->assertSame('Old department', $result->department);
        $this->assertSame('Old department', $DB->get_field('user', 'department', ['id' => $user->id]));
        $this->assertEmpty($messages);
    }

    /**
     * apply_profile_fields() updates an existing user's configured field when `user_field_update_existing`
     * is explicitly enabled: the same outcome as leaving it at its (enabled) default.
     */
    public function test_convert_updates_existing_user_when_update_existing_explicitly_enabled(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user(['department' => 'Old department']);
        person_converter::save_mapping('mapped-explicit-enable-1', $user->id);

        set_config('user_field_department_source', 'extensions.department', 'enrol_eduapi');
        set_config('user_field_update_existing', 1, 'enrol_eduapi');

        $person = $this->make_person('mapped-explicit-enable-1', [
            'extensions' => (object) ['department' => 'New department'],
        ]);

        $result = person_converter::convert($person);

        $this->assertNotNull($result);
        $this->assertSame('New department', $result->department);
        $this->assertSame('New department', $DB->get_field('user', 'department', ['id' => $user->id]));
    }
}
