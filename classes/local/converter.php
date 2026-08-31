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

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Edu-API date/time conversion utilities.
 *
 * Edu-API v1p0 dates use the `DateTimeZ` derived type: "a DateTime with the trailing timezone
 * specifier included, e.g. 2021-09-07T02:09:59+02:00" (per the JSON Schemas' `$comment` fields) —
 * i.e. RFC 3339 / ISO 8601 with an explicit offset (or `Z`). Unlike enrol_oneroster's converter (which
 * parses OneRoster v1.1's stricter `Y-m-d\TG:i:s.+` format), PHP's DateTime constructor already parses
 * this format natively, so no custom format string is needed here.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter {
    /**
     * Convert an Edu-API `DateTimeZ` value to a unix timestamp.
     *
     * @param   string|null $date An RFC 3339 date-time, e.g. '2026-09-01T00:00:00+00:00'
     * @return  int The unix timestamp, or 0 if $date is null/empty/unparseable
     */
    public static function from_datetime_to_unix(?string $date): int {
        if (empty($date)) {
            return 0;
        }

        try {
            return (new DateTime($date))->getTimestamp();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Convert an Edu-API `Date` value (date only, no time component) to a unix timestamp at midnight UTC.
     *
     * Edu-API v1p0's own schemas only use `DateTimeZ` for the fields this plugin reads (startDate,
     * endDate, dateLastModified); this is kept for parity with enrol_oneroster's converter, in case a
     * date-only field is needed by a future version.
     *
     * @param   string|null $date A date in 'Y-m-d' format
     * @return  int The unix timestamp at midnight UTC, or 0 if $date is null/empty/unparseable
     */
    public static function from_date_to_unix(?string $date): int {
        if (empty($date)) {
            return 0;
        }

        $datetime = DateTime::createFromFormat('Y-m-d', $date, new DateTimeZone('UTC'));
        if ($datetime === false) {
            return 0;
        }
        $datetime->setTime(0, 0, 0, 0);

        return $datetime->getTimestamp();
    }

    /**
     * Normalize an Edu-API `recordLanguage` locale (e.g. `en-US`) into a Moodle language code.
     *
     * Trims and lowercases the input, then replaces `-` with `_`. If the resulting code is itself an
     * installed translation it is returned as-is (so a region variant such as `pt_br` survives);
     * otherwise only the primary subtag before the first `_` is returned (so `en_us` becomes `en`).
     * get_list_of_translations() is already cached by Moodle's own cache API (MUC), so no extra
     * request-level caching is done here.
     *
     * @param   string $recordlanguage An Edu-API recordLanguage/locale, e.g. 'en-US' or 'es'
     * @return  string The Moodle language code, or '' if $recordlanguage is empty
     */
    public static function to_moodle_lang(string $recordlanguage): string {
        $trimmed = trim($recordlanguage);
        if ($trimmed === '') {
            return '';
        }

        $normalized = str_replace('-', '_', strtolower($trimmed));

        if (array_key_exists($normalized, get_string_manager()->get_list_of_translations())) {
            return $normalized;
        }

        $parts = explode('_', $normalized, 2);

        return $parts[0];
    }

    /**
     * Pick or combine the display value from an array of language-tagged entries.
     *
     * `$entries` is the raw `title`/`description` field of an Edu-API entity: an array of stdClass
     * objects shaped `{recordLanguage, value}` (LanguageTypedString[]). Entries with an empty `value`
     * are skipped. When `$multilang` is true, every remaining entry is wrapped in its own
     * `<span lang="..." class="multilang">` tag and concatenated (rendering requires the site's
     * "Multi-language content" filter to be enabled); an entry whose recordLanguage cannot be mapped
     * to a Moodle language code is omitted rather than emitting an empty `lang=""` attribute, and if
     * every entry ends up omitted this way, the first non-empty entry's raw value is returned instead
     * of losing the title entirely. When `$multilang` is false, a single value is picked: the entry
     * matching `$sitelang`, else the entry mapping to 'en', else the first non-empty entry. `$sitelang`
     * defaults to the site's default language (`$CFG->lang`) rather than the acting user's own
     * current/session language, so the resolved value is stable no matter who or what triggers a sync.
     *
     * @param   array $entries stdClass[] shaped {recordLanguage, value}
     * @param   bool $multilang Whether to emit multilang markup for every language, or pick one value
     * @param   string|null $sitelang The preferred Moodle language code; defaults to $CFG->lang
     * @return  string
     */
    public static function pick_language_value(array $entries, bool $multilang, ?string $sitelang = null): string {
        $nonempty = array_values(array_filter($entries, function ($entry) {
            return !empty($entry->value ?? '');
        }));

        if (empty($nonempty)) {
            return '';
        }

        if ($multilang) {
            $result = '';
            foreach ($nonempty as $entry) {
                $lang = self::to_moodle_lang($entry->recordLanguage ?? '');
                if ($lang === '') {
                    continue;
                }
                $result .= '<span lang="' . $lang . '" class="multilang">' . $entry->value . '</span>';
            }

            return $result !== '' ? $result : $nonempty[0]->value;
        }

        if ($sitelang === null) {
            global $CFG;
            $sitelang = $CFG->lang ?? 'en';
        }

        foreach ($nonempty as $entry) {
            if (self::to_moodle_lang($entry->recordLanguage ?? '') === $sitelang) {
                return $entry->value;
            }
        }

        foreach ($nonempty as $entry) {
            if (self::to_moodle_lang($entry->recordLanguage ?? '') === 'en') {
                return $entry->value;
            }
        }

        return $nonempty[0]->value;
    }
}
