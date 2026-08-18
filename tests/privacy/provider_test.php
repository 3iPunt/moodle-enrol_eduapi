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

namespace enrol_eduapi\privacy;

use context_system;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use enrol_eduapi\local\converters\person_converter;

/**
 * Tests for enrol_eduapi's Privacy Subsystem provider: the enrol_eduapi_user_map table links an
 * Edu-API sourcedId to a Moodle userid, reported in each user's own context_user (there is no course
 * association at all, unlike enrol_paypal's provider - the closest precedent this was modelled on).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * get_metadata() declares the enrol_eduapi_user_map table and its two privacy-relevant fields.
     */
    public function test_get_metadata(): void {
        $collection = new collection('enrol_eduapi');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();

        $this->assertCount(1, $itemcollection);
        $table = reset($itemcollection);
        $this->assertEquals('enrol_eduapi_user_map', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('parentid', $privacyfields);
        $this->assertArrayHasKey('mappedid', $privacyfields);
    }

    /**
     * get_contexts_for_userid() returns the user's own context_user only when a mapping exists for
     * them, and nothing for a user with no mapping.
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $mapped = $generator->create_user();
        $unmapped = $generator->create_user();
        $mappedcontext = context_user::instance($mapped->id);

        $this->assertCount(0, provider::get_contexts_for_userid($mapped->id));

        person_converter::save_mapping('sourced-1', $mapped->id);

        $contextlist = provider::get_contexts_for_userid($mapped->id);
        $this->assertCount(1, $contextlist);
        $this->assertEquals($mappedcontext, $contextlist->current());

        $this->assertCount(0, provider::get_contexts_for_userid($unmapped->id));
    }

    /**
     * get_users_in_context() only ever returns the one user a context_user context belongs to, and
     * only when that user actually has a mapping.
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'enrol_eduapi';

        $mapped = $generator->create_user();
        $unmapped = $generator->create_user();
        $mappedcontext = context_user::instance($mapped->id);
        $unmappedcontext = context_user::instance($unmapped->id);

        person_converter::save_mapping('sourced-2', $mapped->id);

        $userlist1 = new userlist($mappedcontext, $component);
        provider::get_users_in_context($userlist1);
        $this->assertCount(1, $userlist1);
        $this->assertEquals($mapped, $userlist1->current());

        $userlist2 = new userlist($unmappedcontext, $component);
        provider::get_users_in_context($userlist2);
        $this->assertCount(0, $userlist2);

        // A non-context_user context (e.g. system) never has any users reported.
        $userlist3 = new userlist(context_system::instance(), $component);
        provider::get_users_in_context($userlist3);
        $this->assertCount(0, $userlist3);
    }

    /**
     * export_user_data() exports the mapped sourcedId(s) under the user's own context_user.
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'enrol_eduapi';

        $user = $generator->create_user();
        $usercontext = context_user::instance($user->id);
        person_converter::save_mapping('sourced-3', $user->id);

        $approvedlist = new approved_contextlist($user, $component, [$usercontext->id]);
        provider::export_user_data($approvedlist);

        $writer = writer::with_context($usercontext);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * export_user_data() does nothing for a context that is not the exporting user's own context_user
     * (e.g. a system context accidentally included in the approved list).
     */
    public function test_export_user_data_ignores_other_contexts(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'enrol_eduapi';

        $user = $generator->create_user();
        person_converter::save_mapping('sourced-4', $user->id);

        $systemcontext = context_system::instance();
        $approvedlist = new approved_contextlist($user, $component, [$systemcontext->id]);
        provider::export_user_data($approvedlist);

        $writer = writer::with_context($systemcontext);
        $this->assertFalse($writer->has_any_data());
    }

    /**
     * delete_data_for_all_users_in_context() removes the mapping for the user of that context_user
     * context, and has no effect on a non-context_user context (e.g. system).
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $usercontext = context_user::instance($user->id);
        person_converter::save_mapping('sourced-5', $user->id);

        // A system context deletion must have no effect.
        provider::delete_data_for_all_users_in_context(context_system::instance());
        $this->assertTrue($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $user->id]));

        provider::delete_data_for_all_users_in_context($usercontext);
        $this->assertFalse($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $user->id]));
    }

    /**
     * delete_data_for_user() only deletes the mapping when the approved context is that user's own
     * context_user - not an unrelated context, and not another user's context_user.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'enrol_eduapi';

        $usera = $generator->create_user();
        $userb = $generator->create_user();
        $useracontext = context_user::instance($usera->id);
        $userbcontext = context_user::instance($userb->id);

        person_converter::save_mapping('sourced-6', $usera->id);
        person_converter::save_mapping('sourced-7', $userb->id);

        // Deleting user A's data in user B's context must not affect user A's mapping.
        $approvedlist = new approved_contextlist($usera, $component, [$userbcontext->id]);
        provider::delete_data_for_user($approvedlist);
        $this->assertTrue($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $usera->id]));

        $approvedlist = new approved_contextlist($usera, $component, [$useracontext->id]);
        provider::delete_data_for_user($approvedlist);
        $this->assertFalse($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $usera->id]));
        $this->assertTrue($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $userb->id]));
    }

    /**
     * delete_data_for_users() deletes the mapping for every listed user within the given context_user
     * context, and has no effect for a non-context_user context.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $component = 'enrol_eduapi';

        $usera = $generator->create_user();
        $userb = $generator->create_user();
        $useracontext = context_user::instance($usera->id);

        person_converter::save_mapping('sourced-8', $usera->id);
        person_converter::save_mapping('sourced-9', $userb->id);

        // A system context deletion must have no effect.
        $approvedlist = new approved_userlist(context_system::instance(), $component, [$usera->id, $userb->id]);
        provider::delete_data_for_users($approvedlist);
        $this->assertTrue($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $usera->id]));

        $approvedlist = new approved_userlist($useracontext, $component, [$usera->id]);
        provider::delete_data_for_users($approvedlist);
        $this->assertFalse($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $usera->id]));
        $this->assertTrue($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $userb->id]));
    }
}
