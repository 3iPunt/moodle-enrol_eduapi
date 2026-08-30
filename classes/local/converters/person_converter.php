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

use core_text;
use core_user;
use dml_exception;
use enrol_eduapi\local\v1p0\entities\person;
use null_progress_trace;
use progress_trace;
use stdClass;

/**
 * Resolves an Edu-API Person entity to a Moodle user, and persists the link.
 *
 * Per spec.md's entity mapping table: Person maps to a Moodle user, with configurable matching between
 * a Moodle field (email/idnumber/username) and an Edu-API source attribute (primaryEmail/sourcedId/a
 * specific otherIdentifiers type). The resulting link is persisted in enrol_eduapi_user_map (sourcedId
 * <-> userid).
 *
 * RESOLVED (Client): what happens when no matching Moodle user is found is configurable via
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

    /** @var int The DB column length of user.department and user.institution */
    const PROFILE_FIELD_MAXLENGTH = 255;

    /** @var string[] Moodle user profile fields configurable via a `user_field_<field>_source` setting */
    const PROFILE_FIELDS = ['department', 'institution'];

    /** @var string The `resolve_source_value()` prefix for a top-level `extensions.<key>` source */
    public const SOURCE_PREFIX_EXTENSIONS = 'extensions';

    /** @var string The `resolve_source_value()` prefix for an `otherIdentifiers.<identifierType>` source */
    public const SOURCE_PREFIX_OTHER_IDENTIFIERS = 'otherIdentifiers';

    /**
     * Build an `otherIdentifiers.<identifierType>` source string for the given identifierType.
     *
     * Shared by settings.php (building the `user_match_source` select's option values) and
     * db/upgrade.php (migrating a stored bare identifierType value), so both agree with
     * resolve_source_value() on the exact prefix and separator.
     *
     * @param   string $identifiertype
     * @return  string
     */
    public static function build_other_identifier_source(string $identifiertype): string {
        return self::SOURCE_PREFIX_OTHER_IDENTIFIERS . '.' . $identifiertype;
    }

    /**
     * Resolve an Edu-API source string to a value on the given Person entity.
     *
     * This is the single grammar for "pick a Person attribute", shared by `user_match_source` and the
     * `user_field_<field>_source` settings:
     *
     * - `primaryEmail`: the person's primary email address.
     * - `sourcedId`: the person's sourcedId.
     * - `extensions.<key>`: a top-level key of the person's `extensions` object. Only a string or an
     *   int value is read (an int is cast to its decimal string); anything else, including a float, a
     *   nested object or an array, is ignored.
     * - `otherIdentifiers.<identifierType>`: the `identifier` of the first `otherIdentifiers` entry
     *   whose `identifierType` matches, via the Person entity's get_other_identifier() accessor. This
     *   never falls through to the `primaryEmail`/`sourcedId` shortcuts above, so e.g.
     *   `otherIdentifiers.sourcedId` resolves to null unless the person actually has an
     *   `otherIdentifiers` entry of type `sourcedId`.
     * - Any other dot-less value, an unrecognised dotted prefix, an empty key (e.g. `extensions.` or
     *   `otherIdentifiers.`), or an empty source string resolves to null. There is no fallback for a
     *   bare `<identifierType>` any more: a value stored before this was tightened is migrated to the
     *   dotted `otherIdentifiers.<identifierType>` form by the `db/upgrade.php` step.
     *
     * The resolved value is trimmed; a whitespace-only value is treated as absent (null).
     *
     * Pure data extraction: no database access, so this can be unit tested against fixture Person
     * entities without a database.
     *
     * @param   person $person
     * @param   string $source e.g. 'primaryEmail', 'sourcedId', 'extensions.department' or
     *                         'otherIdentifiers.systemId'
     * @return  string|null The resolved value, or null if the person has no value for that source
     */
    public static function resolve_source_value(person $person, string $source): ?string {
        if ($source === '') {
            return null;
        }

        $dotpos = strpos($source, '.');
        if ($dotpos === false) {
            if ($source === 'sourcedId') {
                $value = $person->get_id();
            } else if ($source === 'primaryEmail') {
                $email = $person->get('primaryEmail');
                $value = $email->email ?? null;
            } else {
                // No fallback: a bare value other than the two shortcuts above does not describe an
                // attribute (see this method's docblock).
                return null;
            }
        } else {
            $prefix = substr($source, 0, $dotpos);
            $key = substr($source, $dotpos + 1);

            if ($key === '') {
                return null;
            }

            if ($prefix === self::SOURCE_PREFIX_EXTENSIONS) {
                $extensions = $person->get('extensions');
                if (!($extensions instanceof stdClass) || !property_exists($extensions, $key)) {
                    return null;
                }

                $rawvalue = $extensions->{$key};
                if (!is_string($rawvalue) && !is_int($rawvalue)) {
                    return null;
                }

                $value = (string) $rawvalue;
            } else if ($prefix === self::SOURCE_PREFIX_OTHER_IDENTIFIERS) {
                $value = $person->get_other_identifier($key);
            } else {
                return null;
            }
        }

        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Resolve every configured `user_field_<field>_source` setting against a Person entity, truncating
     * each resolved value to PROFILE_FIELD_MAXLENGTH and cleaning it with core_user::clean_field() so it
     * is safe to store in - and compare against - the corresponding Moodle user field.
     *
     * Cleaning here, before any comparison against a currently-stored value, is what makes an
     * already-clean stored value compare equal on every subsequent sync (instead of being re-written
     * forever): user_update_user() would clean the value anyway, so comparing the raw resolved value
     * against an already-cleaned stored value would never converge for a value containing e.g. HTML
     * tags or invalid UTF-8.
     *
     * A field is included in the result only when a source is configured for it, it resolves to a
     * non-null value, and that value survives cleaning as a non-empty string. A field with no source
     * configured, an absent attribute, or a value that cleans to '' is simply omitted - never blanked.
     *
     * Used by both build_new_user_data() (new users) and apply_profile_fields() (existing users), so the
     * two code paths resolve, truncate and clean profile fields identically.
     *
     * @param   person $person
     * @return  array Cleaned values keyed by Moodle field name (see PROFILE_FIELDS)
     */
    private static function resolve_configured_profile_fields(person $person): array {
        $resolved = [];

        foreach (self::PROFILE_FIELDS as $field) {
            $source = get_config('enrol_eduapi', "user_field_{$field}_source");
            if (empty($source)) {
                continue;
            }

            $value = self::resolve_source_value($person, $source);
            if ($value === null) {
                continue;
            }

            $value = core_text::substr($value, 0, self::PROFILE_FIELD_MAXLENGTH);

            $value = core_user::clean_field($value, $field);
            if ($value === '') {
                continue;
            }

            $resolved[$field] = $value;
        }

        return $resolved;
    }

    /**
     * Normalise a value for comparison against the given Moodle field.
     *
     * The value is always trimmed first, so it converges with a value already trimmed by
     * resolve_source_value(). Moodle stores `username` in lowercase and treats it case-insensitively;
     * `email` and `idnumber` are compared as stored (beyond the trim).
     *
     * @param   string $moodlefield
     * @param   string $value
     * @return  string
     */
    public static function normalise_for_field(string $moodlefield, string $value): string {
        $value = trim($value);

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
     * default of "Skip unmatched persons (do not sync them)".
     *
     * @return  bool
     */
    public static function should_create_unmatched_users(): bool {
        return (bool) get_config('enrol_eduapi', 'create_unmatched_users');
    }

    /**
     * Whether department/institution sources are also applied to already-existing users at every sync,
     * per the `user_field_update_existing` setting. Defaults to true (enabled), unlike
     * should_create_unmatched_users(): get_config() only ever returns the literal false used here when
     * the setting has genuinely never been written (e.g. its admin default has not been applied yet), in
     * which case the documented default (enabled) is kept rather than treated as disabled.
     *
     * @return  bool
     */
    public static function should_update_existing_users(): bool {
        $value = get_config('enrol_eduapi', 'user_field_update_existing');

        return $value === false ? true : (bool) $value;
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
            return self::sanitise_username(self::normalise_for_field('username', $sourcevalue), $person->get_id());
        }

        $email = $person->get('primaryEmail')->email ?? null;
        if (!empty($email) && strpos($email, '@') !== false) {
            return self::sanitise_username(strstr($email, '@', true), $person->get_id());
        }

        return self::sanitise_username($person->get_id(), $person->get_id());
    }

    /**
     * Sanitise a candidate username to satisfy Moodle's username rules.
     *
     * Accented characters are transliterated to their ASCII equivalents (é => e) before any
     * remaining disallowed character is stripped, so names such as "farré" survive as "farre"
     * rather than being truncated. Falls back to a stable hash-based username when nothing
     * usable remains.
     *
     * @param   string $candidate
     * @param   string $sourcedid Used to build a stable fallback when the candidate sanitises to ''.
     * @return  string
     */
    public static function sanitise_username(string $candidate, string $sourcedid): string {
        $candidate = core_text::specialtoascii($candidate);
        $candidate = preg_replace('/[^a-z0-9._-]/', '', strtolower($candidate));

        return $candidate !== '' ? $candidate : 'eduapi-' . substr(md5($sourcedid), 0, 8);
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
     * Pure data transformation other than the username-uniqueness check (a read-only lookup) and the
     * `user_field_<field>_source` config reads.
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
        // Trimmed the same way resolve_source_value() trims a 'primaryEmail' source, so a blank-only
        // email is treated as absent (falls back to the placeholder) instead of being stored as-is.
        $email = trim($primaryemail->email ?? '') ?: ($person->get_id() . '@' . self::PLACEHOLDER_EMAIL_DOMAIN);

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

        foreach (self::resolve_configured_profile_fields($person) as $field => $value) {
            $userdata->{$field} = $value;
        }

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
     * Apply the configured `user_field_<field>_source` settings to an existing Moodle user, per
     * `user_field_update_existing` (see should_update_existing_users()).
     *
     * For each field resolved by resolve_configured_profile_fields() that differs from what is currently
     * stored, includes it in a single user_update_user() call (so the standard user_updated event fires,
     * as for any other Moodle profile update) and traces one line per changed field, then applies the
     * same change to the in-memory $user rather than re-reading it from the database. An omitted field
     * (no source configured, an absent attribute, or a value cleaning to '') leaves the stored field
     * untouched rather than blanking it. When `user_field_update_existing` is disabled, or when no
     * configured field actually differs from what is stored, this is a no-op: no update call is made and
     * the given user record is returned unchanged.
     *
     * DB-mutating only when enabled and at least one configured field actually differs from what is
     * stored.
     *
     * @param   stdClass $user The current Moodle user record
     * @param   person $person
     * @param   progress_trace $trace
     * @return  stdClass $user, with any changed fields applied in-memory
     */
    private static function apply_profile_fields(stdClass $user, person $person, progress_trace $trace): stdClass {
        global $CFG;

        if (!self::should_update_existing_users()) {
            return $user;
        }

        $updatedata = (object) ['id' => $user->id];
        $haschanges = false;

        foreach (self::resolve_configured_profile_fields($person) as $field => $value) {
            if ($value === $user->{$field}) {
                continue;
            }

            $updatedata->{$field} = $value;
            $user->{$field} = $value;
            $haschanges = true;
            $trace->output("Person '{$person->get_id()}': updated {$field} to '{$value}'");
        }

        if ($haschanges) {
            require_once($CFG->dirroot . '/user/lib.php');
            user_update_user($updatedata, false, true);
        }

        return $user;
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
     * An account found via the stored mapping or matched by field then goes through apply_profile_fields(),
     * which keeps `department`/`institution` up to date per the `user_field_department_source` /
     * `user_field_institution_source` settings (see that method). A newly-created account does not: its
     * fields were already populated by build_new_user_data(), so running apply_profile_fields() on it
     * would be a redundant no-op.
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
            $mappeduser = $DB->get_record('user', ['id' => $existingid, 'deleted' => 0]);
            return $mappeduser ? self::apply_profile_fields($mappeduser, $person, $trace) : null;
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
        $newlycreated = false;

        if (!$moodleuser) {
            if (!self::should_create_unmatched_users()) {
                return null;
            }
            $moodleuser = self::create_user_from_person($person, $moodlefield, $sourcevalue);
            $newlycreated = true;
        }

        self::save_mapping($sourcedid, (int) $moodleuser->id);

        return $newlycreated ? $moodleuser : self::apply_profile_fields($moodleuser, $person, $trace);
    }
}
