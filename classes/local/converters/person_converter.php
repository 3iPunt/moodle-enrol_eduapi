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

use dml_exception;
use enrol_eduapi\local\v1p0\entities\person;
use null_progress_trace;
use progress_trace;
use stdClass;

/**
 * Resolves an Edu-API Person entity to a Moodle user, and persists the link.
 *
 * Per spec.md's entity mapping table: "Person → Usuario Moodle. Emparejamiento configurable: campo
 * Moodle (email/idnumber/username) frente a atributo origen Edu-API (primaryEmail/sourcedId/un tipo
 * concreto de otherIdentifiers). El enlace resultante se persiste en enrol_eduapi_user_map (sourcedId
 * ↔ userid)."
 *
 * RESOLVED (Cliente): what happens when no matching Moodle user is found is configurable via
 * `create_unmatched_users` (settings.php, development phase 4): by default the person is skipped (not
 * synced, matching-only, no accounts created); when enabled, a new Moodle user is provisioned from the
 * Person's data (username/email/name derived from `user_match_source`), the same way
 * enrol_oneroster's create_new_user() does for its own unmatched users.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person_converter {

    /** @var string The enrol_eduapi_user_map table name, without the Moodle table prefix */
    const MAP_TABLE = 'enrol_eduapi_user_map';

    /** @var string[] Moodle user fields that `user_match_moodlefield` may select */
    const MOODLE_FIELDS = ['email', 'idnumber', 'username'];

    /** @var string Placeholder email domain used when a created user has no `primaryEmail` at all */
    const PLACEHOLDER_EMAIL_DOMAIN = 'eduapi.invalid';

    /**
     * Resolve the Edu-API source value to match against, per the `user_match_source` setting.
     *
     * Pure data extraction: no database access, so this can be unit tested against fixture Person
     * entities without a database.
     *
     * @param   person $person
     * @param   string $source One of 'primaryEmail', 'sourcedId', or an IdentifierTypeEnum value
     *                         (an `otherIdentifiers` entry's `identifierType`)
     * @return  string|null The value to match, or null if the person has no value for that source
     */
    public static function resolve_source_value(person $person, string $source): ?string {
        switch ($source) {
            case 'sourcedId':
                return $person->get_id();

            case 'primaryEmail':
                $email = $person->get('primaryEmail');
                return $email->email ?? null;

            default:
                // Any other value is treated as an `otherIdentifiers` `identifierType`.
                return $person->get_other_identifier($source);
        }
    }

    /**
     * Normalise a value for comparison against the given Moodle field.
     *
     * Moodle stores `username` in lowercase and treats it case-insensitively; `email` and `idnumber`
     * are compared as stored.
     *
     * @param   string $moodlefield
     * @param   string $value
     * @return  string
     */
    public static function normalise_for_field(string $moodlefield, string $value): string {
        return $moodlefield === 'username' ? strtolower($value) : $value;
    }

    /**
     * Get the Moodle userid already linked to the given Edu-API sourcedId, if any.
     *
     * @param   string $sourcedid
     * @return  int|null
     */
    public static function get_mapped_userid(string $sourcedid): ?int {
        global $DB;

        $mappedid = $DB->get_field(self::MAP_TABLE, 'mappedid', ['parentid' => $sourcedid]);

        return $mappedid ? (int) $mappedid : null;
    }

    /**
     * Persist the link between an Edu-API sourcedId and a Moodle userid.
     *
     * DB-mutating: writes to `enrol_eduapi_user_map`. Not exercised during development phase 3
     * verification (which must not mutate the Moodle database).
     *
     * Resilient to losing a race against a concurrent sync run inserting the same `parentid` first
     * (rejected by the UNIQUE index on `parentid`): re-reads what the other run wrote instead of
     * failing, rather than surfacing a duplicate-key error for what is a legitimate, if redundant,
     * concurrent resolution of the same person.
     *
     * @param   string $sourcedid
     * @param   int $userid
     */
    public static function save_mapping(string $sourcedid, int $userid): void {
        global $DB;

        $existing = $DB->get_record(self::MAP_TABLE, ['parentid' => $sourcedid]);
        if ($existing) {
            if ((int) $existing->mappedid !== $userid) {
                $existing->mappedid = (string) $userid;
                $DB->update_record(self::MAP_TABLE, $existing);
            }
            return;
        }

        try {
            $DB->insert_record(self::MAP_TABLE, (object) [
                'parentid' => $sourcedid,
                'mappedid' => (string) $userid,
            ]);
        } catch (dml_exception $e) {
            // Most likely a concurrent sync run won the race and inserted this parentid first (the
            // UNIQUE index on parentid rejects our duplicate). Re-read instead of failing.
            $existing = $DB->get_record(self::MAP_TABLE, ['parentid' => $sourcedid]);
            if ($existing === false) {
                // Not a duplicate-key conflict after all - some other DB failure. Rethrow.
                throw $e;
            }
        }
    }

    /**
     * Whether a Moodle user should be created for a Person with no matching account, per the
     * `create_unmatched_users` setting. Defaults to false (skip), matching the setting's documented
     * default of "Omitir personas sin emparejar".
     *
     * @return  bool
     */
    public static function should_create_unmatched_users(): bool {
        return (bool) get_config('enrol_eduapi', 'create_unmatched_users');
    }

    /**
     * Derive a Moodle username for a newly-created user from a Person entity.
     *
     * If `user_match_moodlefield` is itself `username`, the value already matched against
     * ($sourcevalue) is reused directly (it is, by definition, what future syncs will look for).
     * Otherwise a username is derived from the local part of `primaryEmail`, falling back to a
     * sanitised sourcedId if no email is available. Uniqueness against existing Moodle usernames is
     * enforced separately by ensure_unique_username().
     *
     * Pure logic (no database access) other than the caller-supplied inputs.
     *
     * @param   person $person
     * @param   string $moodlefield
     * @param   string $sourcevalue
     * @return  string
     */
    public static function derive_username(person $person, string $moodlefield, string $sourcevalue): string {
        if ($moodlefield === 'username') {
            return self::normalise_for_field('username', $sourcevalue);
        }

        $email = $person->get('primaryEmail')->email ?? null;
        if (!empty($email) && strpos($email, '@') !== false) {
            return strtolower(strstr($email, '@', true));
        }

        $sanitised = preg_replace('/[^a-z0-9._-]/', '', strtolower($person->get_id()));

        return $sanitised !== '' ? $sanitised : 'eduapi-' . substr(md5($person->get_id()), 0, 8);
    }

    /**
     * Make a candidate username unique among existing Moodle accounts on this site, appending a
     * numeric suffix if needed.
     *
     * DB-reading only (no mutation).
     *
     * @param   string $username
     * @return  string
     */
    public static function ensure_unique_username(string $username): string {
        global $CFG, $DB;

        $candidate = $username;
        $suffix = 1;
        while ($DB->record_exists('user', ['username' => $candidate, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $candidate = $username . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Build the Moodle user fields for a new account provisioned from a Person entity.
     *
     * Pure data transformation other than the username-uniqueness check (a read-only lookup).
     *
     * @param   person $person
     * @param   string $moodlefield The Moodle field being matched on (`user_match_moodlefield`)
     * @param   string $sourcevalue The Edu-API source value being matched (`user_match_source`)
     * @return  stdClass Fields suitable for user_create_user()
     */
    public static function build_new_user_data(person $person, string $moodlefield, string $sourcevalue): stdClass {
        global $CFG;

        $legalname = $person->get('legalName');
        $firstname = $legalname->givenName ?? null;
        $lastname = $legalname->familyName ?? null;

        $primaryemail = $person->get('primaryEmail');
        $email = $primaryemail->email ?? ($person->get_id() . '@' . self::PLACEHOLDER_EMAIL_DOMAIN);

        $userdata = (object) [
            'auth' => 'manual',
            'mnethostid' => $CFG->mnet_localhost_id,
            'confirmed' => 1,
            'firstname' => $firstname ?: $person->get_id(),
            'lastname' => $lastname ?: '-',
            'email' => $email,
            'username' => self::ensure_unique_username(self::derive_username($person, $moodlefield, $sourcevalue)),
            'idnumber' => $person->get_id(),
            'password' => generate_password(),
        ];

        // Whichever field is being matched on must hold exactly the value future syncs will search
        // for, even if that overrides what was derived above (e.g. email, idnumber).
        $userdata->{$moodlefield} = self::normalise_for_field($moodlefield, $sourcevalue);

        return $userdata;
    }

    /**
     * Create a new Moodle user from a Person entity that had no matching account.
     *
     * DB-mutating: inserts into `user`. Not exercised during development phase 3/4/5 verification
     * (which must not mutate the Moodle database) — see TIPEDUAPI-14's verification reports.
     *
     * @param   person $person
     * @param   string $moodlefield
     * @param   string $sourcevalue
     * @return  stdClass The newly created Moodle user record
     */
    public static function create_user_from_person(person $person, string $moodlefield, string $sourcevalue): stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $userdata = self::build_new_user_data($person, $moodlefield, $sourcevalue);
        $newuserid = user_create_user($userdata, false, false);

        return $DB->get_record('user', ['id' => $newuserid]);
    }

    /**
     * Resolve a Person entity to a Moodle user record, persisting the mapping.
     *
     * If no matching Moodle user is found, either skips the person (returns null) or provisions a new
     * account, per `create_unmatched_users`.
     *
     * DB-mutating (reads `user` and `enrol_eduapi_user_map`; may write to `enrol_eduapi_user_map` and,
     * with `create_unmatched_users` active, to `user`). Not exercised during development phase 3/4/5
     * verification — see this converter's class docblock and TIPEDUAPI-14's verification reports.
     *
     * Moodle does not enforce uniqueness on `email` or `idnumber` (only `username` is unique), so more
     * than one Moodle user can share the matched field/value. In that case there is no reliable way to
     * pick "the" right account, so the person is skipped (same outcome as no match) and a warning
     * naming the matched field and value is emitted to `$trace`.
     *
     * @param   person $person
     * @param   progress_trace|null $trace Sync trace to report an ambiguous-match warning to; a
     *                                     `null_progress_trace` is used if omitted
     * @return  stdClass|null The matched or newly-created Moodle user record, or null if unmatched (or
     *                        ambiguously matched) and `create_unmatched_users` is not applicable
     */
    public static function convert(person $person, ?progress_trace $trace = null): ?stdClass {
        global $DB, $CFG;

        $trace = $trace ?? new null_progress_trace();

        $sourcedid = $person->get_id();

        $existingid = self::get_mapped_userid($sourcedid);
        if ($existingid) {
            return $DB->get_record('user', ['id' => $existingid, 'deleted' => 0]) ?: null;
        }

        $moodlefield = get_config('enrol_eduapi', 'user_match_moodlefield');
        if (!in_array($moodlefield, self::MOODLE_FIELDS, true)) {
            $moodlefield = 'email';
        }

        $source = get_config('enrol_eduapi', 'user_match_source');
        if (empty($source)) {
            $source = 'primaryEmail';
        }

        $sourcevalue = self::resolve_source_value($person, $source);
        if ($sourcevalue === null || $sourcevalue === '') {
            return null;
        }

        $normalisedvalue = self::normalise_for_field($moodlefield, $sourcevalue);
        $matches = $DB->get_records('user', [
            $moodlefield => $normalisedvalue,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ]);

        if (count($matches) > 1) {
            $trace->output(
                "Person '{$sourcedid}' skipped: {$moodlefield} = '{$normalisedvalue}' matches " .
                count($matches) . ' Moodle users (Moodle does not enforce uniqueness on this field); ' .
                'cannot reliably resolve a single account.'
            );
            return null;
        }

        $moodleuser = $matches ? reset($matches) : false;

        if (!$moodleuser) {
            if (!self::should_create_unmatched_users()) {
                return null;
            }
            $moodleuser = self::create_user_from_person($person, $moodlefield, $sourcevalue);
        }

        self::save_mapping($sourcedid, (int) $moodleuser->id);

        return $moodleuser;
    }
}
