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

namespace enrol_eduapi;

/**
 * Tests for the db/upgrade.php step that migrates a stored `user_match_source` bare identifierType
 * value to the prefixed `otherIdentifiers.<identifierType>` form.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::xmldb_enrol_eduapi_upgrade
 */
final class upgradelib_test extends \advanced_testcase {
    /**
     * Load upgradelib.php (for upgrade_plugin_savepoint(), normally only included by Moodle's own
     * upgrade bootstrap) and db/upgrade.php once, so xmldb_enrol_eduapi_upgrade() is defined and
     * callable.
     */
    public static function setUpBeforeClass(): void {
        global $CFG;

        parent::setUpBeforeClass();

        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/enrol/eduapi/db/upgrade.php');
    }

    /**
     * The upgrade step rewrites a stored bare (dot-less) `user_match_source` identifierType value to
     * the prefixed `otherIdentifiers.<identifierType>` form.
     */
    public function test_upgrade_rewrites_bare_identifiertype_to_prefixed_form(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'systemId', 'enrol_eduapi');

        \xmldb_enrol_eduapi_upgrade(2026082901);

        $this->assertSame('otherIdentifiers.systemId', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * The upgrade step leaves `primaryEmail` untouched: it is a shortcut, not an otherIdentifiers
     * type.
     */
    public function test_upgrade_leaves_primaryemail_untouched(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'primaryEmail', 'enrol_eduapi');

        \xmldb_enrol_eduapi_upgrade(2026082901);

        $this->assertSame('primaryEmail', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * The upgrade step leaves `sourcedId` untouched: it is a shortcut, not an otherIdentifiers type.
     */
    public function test_upgrade_leaves_sourcedid_untouched(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'sourcedId', 'enrol_eduapi');

        \xmldb_enrol_eduapi_upgrade(2026082901);

        $this->assertSame('sourcedId', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * The upgrade step leaves an already-dotted value untouched, e.g. one already migrated by a
     * previous run, or an `extensions.<key>` value set directly through the config API/CLI.
     */
    public function test_upgrade_leaves_already_dotted_value_untouched(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'extensions.department', 'enrol_eduapi');

        \xmldb_enrol_eduapi_upgrade(2026082901);

        $this->assertSame('extensions.department', get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * The upgrade step is a no-op when `user_match_source` is not configured (get_config() returns
     * false, e.g. its admin default has never been applied).
     */
    public function test_upgrade_is_noop_when_setting_not_configured(): void {
        $this->resetAfterTest();

        unset_config('user_match_source', 'enrol_eduapi');

        \xmldb_enrol_eduapi_upgrade(2026082901);

        $this->assertFalse(get_config('enrol_eduapi', 'user_match_source'));
    }

    /**
     * The upgrade step does not run again once its savepoint has already been reached.
     */
    public function test_upgrade_does_not_run_again_at_or_after_savepoint(): void {
        $this->resetAfterTest();

        set_config('user_match_source', 'systemId', 'enrol_eduapi');

        \xmldb_enrol_eduapi_upgrade(2026083000);

        $this->assertSame('systemId', get_config('enrol_eduapi', 'user_match_source'));
    }
}
