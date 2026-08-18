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

use coding_exception;
use core_course_category;
use enrol_eduapi\local\converter;
use enrol_eduapi\local\v1p0\entities\organization;
use null_progress_trace;
use progress_trace;
use stdClass;

/**
 * Converts an Edu-API Organization entity into a Moodle course category.
 *
 * Per spec.md's entity mapping table: "Organization → Categoría de curso. Jerarquía anidada:
 * organizaciones anidadas en el origen se traducen en categorías anidadas en Moodle." Parent
 * organizations are resolved and created/updated recursively before the child, mirroring
 * enrol_oneroster's update_or_create_category(). `idnumber` is always the Organization's sourcedId,
 * which is how an existing category is matched on subsequent syncs.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class organization_converter {
    /**
     * Build the Moodle course category fields for the given Organization.
     *
     * This is pure data transformation: no database access. Kept separate from convert() so it can be
     * unit tested against fixture entities without a database.
     *
     * @param   organization $org
     * @return  stdClass Fields suitable for core_course_category::create()/update() (without `parent`,
     *                   which convert() fills in after resolving the parent hierarchy)
     */
    public static function get_category_data(organization $org): stdClass {
        return (object) [
            'idnumber' => $org->get_id(),
            'name' => self::extract_name($org),
            'visible' => !in_array($org->get('recordStatus'), ['inactive', 'deleted'], true),
        ];
    }

    /**
     * Extract a display name from the Organization's `name` (LanguageTypedString[]) field.
     *
     * @param   organization $org
     * @return  string The first available name, or the sourcedId if none is present
     */
    protected static function extract_name(organization $org): string {
        $names = $org->get('name') ?? [];
        if (!empty($names) && isset($names[0]->value)) {
            return $names[0]->value;
        }

        return $org->get_id();
    }

    /**
     * Create or update the Moodle course category for the given Organization, resolving and
     * creating/updating its parent hierarchy first.
     *
     * DB-mutating: creates/updates rows in `course_categories`. Not exercised during development
     * phase 3 verification (which must not mutate the Moodle database) — see plan.md's phase 3 gate
     * and TIPEDUAPI-14's verification report for what was and was not run live.
     *
     * Guards against a circular parent chain in the source data (Organization A's parent is B, whose
     * parent is A): $visited tracks the sourcedIds seen along the CURRENT recursion path, and a repeat
     * throws instead of recursing forever. Both existing callers (eduapi_client.php's organization
     * loop, and offering_converter.php resolving an offering's organization) already wrap this call in
     * a try/catch that logs the exception message to their own trace and continues with the next item,
     * so this is reported without the whole sync run aborting.
     *
     * @param   organization $org
     * @param   progress_trace|null $trace Sync trace to report the circular-chain error to (in
     *                                     addition to it being carried in the thrown exception's
     *                                     message); a `null_progress_trace` is used if omitted
     * @param   string[] $visited Internal recursion guard - sourcedIds already visited on this path.
     *                            Callers should not pass this.
     * @return  core_course_category
     * @throws  coding_exception If $org's parent chain is circular
     */
    public static function convert(organization $org, ?progress_trace $trace = null, array $visited = []): core_course_category {
        global $DB;

        $trace = $trace ?? new null_progress_trace();

        $orgid = $org->get_id();
        if (in_array($orgid, $visited, true)) {
            $message = "Circular Organization parent chain detected (" . implode(' -> ', $visited) .
                " -> {$orgid}); stopping recursion here instead of looping forever.";
            $trace->output($message);
            throw new coding_exception($message);
        }
        $visited[] = $orgid;

        $categorydata = self::get_category_data($org);

        $parent = $org->get_parent();
        if ($parent !== null) {
            $parentcategory = self::convert($parent, $trace, $visited);
            $categorydata->parent = $parentcategory->id;
        }

        $existingid = $DB->get_field('course_categories', 'id', ['idnumber' => $categorydata->idnumber]);

        if ($existingid) {
            $category = core_course_category::get($existingid);
            $remotemodified = converter::from_datetime_to_unix($org->get('dateLastModified'));
            if ($remotemodified > $category->timemodified) {
                $category = $category->update($categorydata);
            }

            return $category;
        }

        return core_course_category::create($categorydata);
    }
}
