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

namespace enrol_eduapi\local;

/**
 * Tests for upgrade_helper::migrate_user_match_source(): the rewrite of a stored `user_match_source`
 * bare identifierType value to the prefixed `otherIdentifiers.<identifierType>` form.
 *
 * Deliberately tests the helper directly rather than xmldb_enrol_eduapi_upgrade(): that function also
 * calls upgrade_plugin_savepoint(), which checks the real installed plugin version in config_plugins
 * and throws a downgrade_exception once that version has reached the savepoint (as it always has on a
 * freshly-installed test/CI site) - a concern of db/upgrade.php's own wiring, not of this migration
 * logic, so it does not belong in this test.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\upgrade_helper
 */
final class upgrade_helper_test extends \advanced_testcase {
    /**
     * migrate_user_match_source() rewrites a stored bare (dot-less) identifierType value to the
     * prefixed `otherIdentifiers.<identifierType>` form.
     */
    public function test_migrate_rewrites_bare_identifiertype_to_prefixed_form(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'systemId', 'enrol_eduapi');

        upgrade_helper::migrate_user_match_source();

        $this->assertSame('otherIdentifiers.systemId', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * migrate_user_match_source() leaves `primaryEmail` untouched: it is a shortcut, not an
     * otherIdentifiers type.
     */
    public function test_migrate_leaves_primaryemail_untouched(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'primaryEmail', 'enrol_eduapi');

        upgrade_helper::migrate_user_match_source();

        $this->assertSame('primaryEmail', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * migrate_user_match_source() leaves `sourcedId` untouched: it is a shortcut, not an
     * otherIdentifiers type.
     */
    public function test_migrate_leaves_sourcedid_untouched(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'sourcedId', 'enrol_eduapi');

        upgrade_helper::migrate_user_match_source();

        $this->assertSame('sourcedId', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * migrate_user_match_source() leaves an already-dotted value untouched, e.g. one already
     * migrated by a previous run, or an `extensions.<key>` value set directly through the config
     * API/CLI.
     */
    public function test_migrate_leaves_already_dotted_value_untouched(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'extensions.department', 'enrol_eduapi');

        upgrade_helper::migrate_user_match_source();

        $this->assertSame('extensions.department', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * migrate_user_match_source() is a no-op when `user_match_source` is not configured (get_config()
     * returns false): the setting stays unset.
     */
    public function test_migrate_leaves_unset_setting_unset(): void {
        $this->resetAfterTest();

        unset_config('user_match_source', 'enrol_eduapi');

        upgrade_helper::migrate_user_match_source();

        $this->assertFalse(get_config('enrol_eduapi', 'user_match_source'));
    }
}
