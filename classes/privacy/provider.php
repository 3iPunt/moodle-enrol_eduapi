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
 * Privacy Subsystem implementation for enrol_eduapi.
 *
 * @package    enrol_eduapi
 * @category   privacy
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_eduapi\privacy;

use context;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for enrol_eduapi.
 *
 * RESOLVED (Jefe de Proyecto, spec.md): a full Privacy API implementation, departing from
 * enrol_oneroster's precedent (a `null_provider` declaring it stores no personal data). There is no
 * enrol_oneroster full-provider precedent to port from here — this was modelled on enrol_paypal's
 * full provider instead (see /enrol/paypal/classes/privacy/provider.php), adapted for this plugin's
 * very different data shape.
 *
 * The one table this plugin persists, `enrol_eduapi_user_map` (`parentid` = the Edu-API sourcedId,
 * `mappedid` = the Moodle userid), links a pseudonymous external identifier to a specific Moodle user.
 * Even though the Edu-API spec requires `sourcedId` itself to be anonymised/pseudonymised ("will not
 * contain Personally Identifiable Information"), the MAPPING as a whole re-identifies that pseudonym
 * to a real Moodle account, which makes it personal data under the GDPR (pseudonymisation is not
 * anonymisation). Unlike enrol_paypal's `enrol_paypal` table (which has a `courseid` and therefore
 * naturally reports its data per course context), this table has no course association at all — it is
 * a site-wide identity link, not tied to any one course — so this provider reports its data in each
 * user's own `context_user`, the same way plugins storing global per-user data (not course-scoped)
 * normally do.
 *
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // The enrol_eduapi_user_map table stores a per-user identifier link.
    \core_privacy\local\metadata\provider,

    // This plugin can determine which users have data within a given context.
    \core_privacy\local\request\core_userlist_provider,

    // This plugin can export/delete an individual user's own data.
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns metadata about this plugin's personal data storage.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection A listing of user data stored by this plugin.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'enrol_eduapi_user_map',
            [
                'parentid' => 'privacy:metadata:enrol_eduapi_user_map:parentid',
                'mappedid' => 'privacy:metadata:enrol_eduapi_user_map:mappedid',
            ],
            'privacy:metadata:enrol_eduapi_user_map'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int $userid The user to search.
     * @return  contextlist The list of contexts containing user info for the specified user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        if ($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $userid])) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_user) {
            return;
        }

        if ($DB->record_exists('enrol_eduapi_user_map', ['mappedid' => (string) $context->instanceid])) {
            $userlist->add_user($context->instanceid);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_user || (int) $context->instanceid !== (int) $user->id) {
                continue;
            }

            $mappings = $DB->get_records('enrol_eduapi_user_map', ['mappedid' => (string) $user->id], 'id');
            if (empty($mappings)) {
                continue;
            }

            $data = array_values(array_map(
                fn($mapping) => (object) ['sourcedid' => $mapping->parentid],
                $mappings
            ));

            writer::with_context($context)->export_data(
                [get_string('privacy:mappingpath', 'enrol_eduapi')],
                (object) ['mappings' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_user) {
            return;
        }

        $DB->delete_records('enrol_eduapi_user_map', ['mappedid' => (string) $context->instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_user && (int) $context->instanceid === (int) $user->id) {
                $DB->delete_records('enrol_eduapi_user_map', ['mappedid' => (string) $user->id]);
            }
        }
    }

    /**
     * Delete multiple users' data within a single context.
     *
     * @param   approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_user) {
            return;
        }

        // A user context only ever contains that one user, but handle the list generically in case a
        // future context/entity relationship changes that.
        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('enrol_eduapi_user_map', ['mappedid' => (string) $userid]);
        }
    }
}
