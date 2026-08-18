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
}
