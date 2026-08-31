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

use enrol_eduapi\local\converter;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\interfaces\entity_factory as entity_factory_interface;
use enrol_eduapi\local\v1p0\entities\enrollment;
use enrol_eduapi\local\v1p0\entities\person;
use enrol_eduapi\tests\fixtures\entity_fixtures_trait;

/**
 * Tests for enrollment_converter::resolve_action() - the EnrollmentStatusEnum -> action mapping and
 * the recordStatus = deleted override - and for convert_to_group_membership(), which keeps a
 * ComponentOffering's Moodle group membership in sync with its own enrolments.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\converters\enrollment_converter
 */
final class enrollment_converter_test extends \advanced_testcase {
    use entity_fixtures_trait;

    /**
     * Build a course, a group inside it, an enrol_eduapi instance for the course, and a user -
     * optionally enrolled in the course - for convert_to_group_membership() tests.
     *
     * @param   bool $enroluser Whether to enrol the created user in the course
     * @return  array{course: \stdClass, group: \stdClass, user: \stdClass, instanceid: int}
     */
    protected function create_group_scenario(bool $enroluser = true): array {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course();
        $group = $generator->create_group(['courseid' => $course->id]);
        $user = $generator->create_user();

        $plugin = enrol_get_plugin('eduapi');
        $instanceid = $plugin->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);

        if ($enroluser) {
            $generator->enrol_user($user->id, $course->id);
        }

        return [
            'course' => $course,
            'group' => $group,
            'user' => $user,
            'instanceid' => $instanceid,
        ];
    }

    /**
     * Build a course, an enrol_eduapi instance for it, and a user - for convert() tests (course-level
     * enrolment, as opposed to create_group_scenario()'s component/group membership tests).
     *
     * The course idnumber matches what offering_converter::get_enrol_instance_for_offering() looks up,
     * so an Enrollment whose `educationOffering` is 'course-1' resolves to this instance.
     *
     * @return  array{course: \stdClass, user: \stdClass, instanceid: int}
     */
    protected function create_course_scenario(): array {
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(['idnumber' => 'course-1']);
        $user = $generator->create_user();

        $plugin = enrol_get_plugin('eduapi');
        $instanceid = $plugin->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);

        return [
            'course' => $course,
            'user' => $user,
            'instanceid' => $instanceid,
        ];
    }

    /**
     * Build an Enrollment entity pre-seeded with data, whose get_person() resolves to $person without
     * any network fetch.
     *
     * @param   string $sourcedid
     * @param   person $person
     * @param   array $data Raw fields, merged over sensible defaults
     * @return  enrollment
     */
    protected function make_enrollment(string $sourcedid, person $person, array $data = []): enrollment {
        $factory = new class ($person) implements entity_factory_interface {
            /** @var person The person to return from every fetch_person_by_id() call */
            private $person;

            /**
             * Constructor.
             *
             * @param   person $person
             */
            public function __construct(person $person) {
                $this->person = $person;
            }

            /**
             * Get the fake person for any fetch_person_by_id() call.
             *
             * @param   string $id
             * @return  person
             */
            public function fetch_person_by_id(string $id): person {
                return $this->person;
            }
        };

        $container = $this->createMock(container_interface::class);
        $container->method('get_entity_factory')->willReturn($factory);

        $defaults = [
            'sourcedId' => $sourcedid,
            'recordStatus' => 'active',
            'enrollmentStatus' => 'enrolled',
            'person' => $person->get_id(),
            'role' => 'student',
            'educationOffering' => 'component-1',
        ];

        return new enrollment($container, $sourcedid, (object) array_merge($defaults, $data));
    }

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

    /**
     * A user enrolled in the course, with an active enrolment mapped to enrol_active, is added to the
     * component's group, tagged with this plugin's component/itemid (the course's enrol_eduapi
     * instance id) so the membership is identifiable as automatically managed.
     */
    public function test_convert_to_group_membership_adds_enrolled_user(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_group_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'));

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $member = $DB->get_record('groups_members', [
            'groupid' => $scenario['group']->id,
            'userid' => $scenario['user']->id,
        ]);
        $this->assertNotFalse($member);
        $this->assertSame('enrol_eduapi', $member->component);
        $this->assertSame((int) $scenario['instanceid'], (int) $member->itemid);
    }

    /**
     * A user already a member of the group, with an active enrolment, stays a member and no exception
     * is raised for the duplicate membership.
     */
    public function test_convert_to_group_membership_is_idempotent_for_existing_member(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/group/lib.php');
        $scenario = $this->create_group_scenario();
        groups_add_member($scenario['group']->id, $scenario['user']->id);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'));

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertTrue($DB->record_exists('groups_members', [
            'groupid' => $scenario['group']->id,
            'userid' => $scenario['user']->id,
        ]));
    }

    /**
     * An enrolment mapped to unenrol (e.g. enrollmentStatus 'withdrawn') removes an existing group
     * member.
     */
    public function test_convert_to_group_membership_removes_withdrawn_member(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/group/lib.php');
        $scenario = $this->create_group_scenario();
        groups_add_member($scenario['group']->id, $scenario['user']->id);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'enrollmentStatus' => 'withdrawn',
        ]);

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertFalse($DB->record_exists('groups_members', [
            'groupid' => $scenario['group']->id,
            'userid' => $scenario['user']->id,
        ]));
    }

    /**
     * A recordStatus of 'deleted' removes an existing group member, overriding an otherwise active
     * enrollmentStatus - consistent with resolve_action()'s deleted-always-wins rule.
     */
    public function test_convert_to_group_membership_removes_member_on_deleted_record_status(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/group/lib.php');
        $scenario = $this->create_group_scenario();
        groups_add_member($scenario['group']->id, $scenario['user']->id);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'recordStatus' => 'deleted',
        ]);

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertFalse($DB->record_exists('groups_members', [
            'groupid' => $scenario['group']->id,
            'userid' => $scenario['user']->id,
        ]));
    }

    /**
     * An active enrolment for a user who is NOT enrolled in the course is not added to the group
     * (groups_add_member() itself refuses it), and the trace explains why.
     */
    public function test_convert_to_group_membership_skips_user_not_enrolled_in_course(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_group_scenario(false);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'));

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertFalse($DB->record_exists('groups_members', [
            'groupid' => $scenario['group']->id,
            'userid' => $scenario['user']->id,
        ]));
        $this->assertStringContainsString('not enrolled', implode(' ', $messages));
    }

    /**
     * An enrolment whose Person has no stored sourcedId <-> userid mapping is skipped without raising
     * an exception and without adding any group member: this method never falls back to
     * person_converter::convert() to match or create one.
     */
    public function test_convert_to_group_membership_skips_unresolvable_person(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_group_scenario();

        // Deliberately no person_converter::save_mapping() call: 'unmatched-person' has no mapping.
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('unmatched-person'));

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertSame(0, $DB->count_records('groups_members', ['groupid' => $scenario['group']->id]));
        $this->assertStringContainsString('not mapped to a Moodle user', implode(' ', $messages));
    }

    /**
     * A role (RoleTypeEnum) mapped to "Do not enrol" (e.g. 'guardian', which has no default mapping)
     * blocks the group membership entirely: an otherwise-active enrolment is not added.
     */
    public function test_convert_to_group_membership_skips_unmapped_role_and_does_not_add(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_group_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'role' => 'guardian',
        ]);

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertSame(0, $DB->count_records('groups_members', ['groupid' => $scenario['group']->id]));
    }

    /**
     * A role mapped to "Do not enrol" also blocks removal: an existing member is left untouched even
     * when the enrolment would otherwise resolve to unenrol.
     */
    public function test_convert_to_group_membership_skips_unmapped_role_and_does_not_remove_existing_member(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/group/lib.php');
        $scenario = $this->create_group_scenario();
        groups_add_member($scenario['group']->id, $scenario['user']->id);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'role' => 'guardian',
            'enrollmentStatus' => 'withdrawn',
        ]);

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertTrue($DB->record_exists('groups_members', [
            'groupid' => $scenario['group']->id,
            'userid' => $scenario['user']->id,
        ]));
    }

    /**
     * Even with create_unmatched_users active, the group membership pass never provisions a Moodle
     * user: it only ever reads an existing sourcedId <-> userid mapping.
     */
    public function test_convert_to_group_membership_never_creates_a_user(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('user_match_moodlefield', 'email', 'enrol_eduapi');
        set_config('user_match_source', 'primaryEmail', 'enrol_eduapi');
        set_config('create_unmatched_users', 1, 'enrol_eduapi');

        $scenario = $this->create_group_scenario(false);
        $usercountbefore = $DB->count_records('user');

        $enrollment = $this->make_enrollment('enr-1', $this->make_person('unmapped-person', [
            'primaryEmail' => (object) ['email' => 'nobody@example.org'],
        ]));

        $messages = [];
        enrollment_converter::convert_to_group_membership($enrollment, $scenario['group'], $this->capturing_trace($messages));

        $this->assertEquals($usercountbefore, $DB->count_records('user'));
        $this->assertSame(0, $DB->count_records('groups_members', ['groupid' => $scenario['group']->id]));
    }

    /**
     * A new enrolment whose Enrollment record carries its own startDate/endDate is enrolled with those
     * dates converted to unix timestamps, taking precedence over any roleEnablement fallback.
     */
    public function test_convert_new_enrolment_uses_enrollment_own_dates(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
            'startDate' => '2020-08-31T00:00:00.000Z',
            'endDate' => '2020-12-21T00:00:00.000Z',
        ]);

        enrollment_converter::convert($enrollment, null, [
            'student' => ['startDate' => '2099-01-01T00:00:00.000Z', 'endDate' => '2099-06-01T00:00:00.000Z'],
        ]);

        $ue = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertNotFalse($ue);
        $this->assertSame(converter::from_datetime_to_unix('2020-08-31T00:00:00.000Z'), (int) $ue->timestart);
        $this->assertSame(converter::from_datetime_to_unix('2020-12-21T00:00:00.000Z'), (int) $ue->timeend);
    }

    /**
     * A new enrolment whose Enrollment record carries no dates falls back to the offering's
     * roleEnablement entry matching the enrolment's own role.
     */
    public function test_convert_new_enrolment_falls_back_to_role_enablement_dates(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
            'role' => 'teacher',
        ]);

        $roleenablement = [
            'teacher' => ['startDate' => '2020-08-31T00:00:00.000Z', 'endDate' => '2020-12-21T00:00:00.000Z'],
            'student' => ['startDate' => '2021-01-01T00:00:00.000Z', 'endDate' => '2021-06-01T00:00:00.000Z'],
        ];
        enrollment_converter::convert($enrollment, null, $roleenablement);

        $ue = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertNotFalse($ue);
        $this->assertSame(converter::from_datetime_to_unix('2020-08-31T00:00:00.000Z'), (int) $ue->timestart);
        $this->assertSame(converter::from_datetime_to_unix('2020-12-21T00:00:00.000Z'), (int) $ue->timeend);
    }

    /**
     * A new enrolment with neither its own dates nor a matching roleEnablement entry is enrolled with
     * timestart/timeend = 0 ("no limit").
     */
    public function test_convert_new_enrolment_uses_zero_when_no_dates_available(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
        ]);

        enrollment_converter::convert($enrollment, null, null);

        $ue = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertNotFalse($ue);
        $this->assertSame(0, (int) $ue->timestart);
        $this->assertSame(0, (int) $ue->timeend);
    }

    /**
     * A roleEnablement entry present only for a DIFFERENT role than the enrolment's own role must never
     * be applied: the enrolment falls through to 0, it does not borrow the other role's dates.
     */
    public function test_convert_new_enrolment_role_enablement_for_different_role_falls_back_to_zero(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
            'role' => 'student',
        ]);

        $roleenablement = [
            'teacher' => ['startDate' => '2020-08-31T00:00:00.000Z', 'endDate' => '2020-12-21T00:00:00.000Z'],
        ];
        enrollment_converter::convert($enrollment, null, $roleenablement);

        $ue = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertNotFalse($ue);
        $this->assertSame(0, (int) $ue->timestart);
        $this->assertSame(0, (int) $ue->timeend);
    }

    /**
     * An enrolment whose own startDate/endDate are empty strings (not null) is treated the same as
     * having no dates at all: the roleEnablement fallback still applies, an empty string must not
     * suppress it.
     */
    public function test_convert_new_enrolment_treats_empty_string_date_as_absent(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
            'startDate' => '',
            'endDate' => '',
        ]);

        $roleenablement = [
            'student' => ['startDate' => '2020-08-31T00:00:00.000Z', 'endDate' => '2020-12-21T00:00:00.000Z'],
        ];
        enrollment_converter::convert($enrollment, null, $roleenablement);

        $ue = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertNotFalse($ue);
        $this->assertSame(converter::from_datetime_to_unix('2020-08-31T00:00:00.000Z'), (int) $ue->timestart);
        $this->assertSame(converter::from_datetime_to_unix('2020-12-21T00:00:00.000Z'), (int) $ue->timeend);
    }

    /**
     * An already-enrolled user whose stored dates differ from the resolved dates gets those dates
     * updated via update_user_enrol().
     */
    public function test_convert_existing_enrolment_updates_dates_when_they_differ(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        $plugin = enrol_get_plugin('eduapi');
        $roleid = enrollment_converter::resolve_role_id('student');
        // Enrol the user directly with stale dates, bypassing convert() so this test controls the
        // "before" state precisely.
        $instance = $this->get_instance($scenario['instanceid']);
        $plugin->enrol_user($instance, $scenario['user']->id, $roleid, 111, 222, ENROL_USER_ACTIVE);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
            'startDate' => '2020-08-31T00:00:00.000Z',
            'endDate' => '2020-12-21T00:00:00.000Z',
        ]);

        enrollment_converter::convert($enrollment, null, null);

        $ue = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertNotFalse($ue);
        $this->assertSame(converter::from_datetime_to_unix('2020-08-31T00:00:00.000Z'), (int) $ue->timestart);
        $this->assertSame(converter::from_datetime_to_unix('2020-12-21T00:00:00.000Z'), (int) $ue->timeend);
    }

    /**
     * An already-enrolled user whose stored status AND dates already match the resolved values is left
     * untouched: no update is written (the whole row, including timemodified, stays identical).
     */
    public function test_convert_existing_enrolment_does_not_update_when_dates_and_status_match(): void {
        global $DB;

        $this->resetAfterTest();
        $scenario = $this->create_course_scenario();

        $roleid = enrollment_converter::resolve_role_id('student');
        $instance = $this->get_instance($scenario['instanceid']);
        $startdate = converter::from_datetime_to_unix('2020-08-31T00:00:00.000Z');
        $enddate = converter::from_datetime_to_unix('2020-12-21T00:00:00.000Z');
        $plugin = enrol_get_plugin('eduapi');
        $plugin->enrol_user($instance, $scenario['user']->id, $roleid, $startdate, $enddate, ENROL_USER_ACTIVE);

        $before = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);

        person_converter::save_mapping('person-1', $scenario['user']->id);
        $enrollment = $this->make_enrollment('enr-1', $this->make_person('person-1'), [
            'educationOffering' => 'course-1',
            'startDate' => '2020-08-31T00:00:00.000Z',
            'endDate' => '2020-12-21T00:00:00.000Z',
        ]);

        enrollment_converter::convert($enrollment, null, null);

        $after = $DB->get_record('user_enrolments', ['userid' => $scenario['user']->id, 'enrolid' => $scenario['instanceid']]);
        $this->assertEquals($before, $after);
    }

    /**
     * Fetch a live `enrol` instance record by id, as required by enrol_user()/update_user_enrol()'s
     * `stdClass $instance` parameter.
     *
     * @param   int $instanceid
     * @return  \stdClass
     */
    protected function get_instance(int $instanceid): \stdClass {
        global $DB;

        return $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
    }
}
