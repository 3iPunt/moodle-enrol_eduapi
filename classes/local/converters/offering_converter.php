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
use enrol_eduapi\local\converter;
use enrol_eduapi\local\v1p0\entities\component_offering;
use enrol_eduapi\local\v1p0\entities\education_offering;
use stdClass;

/**
 * Converts an Edu-API CourseOffering or ComponentOffering entity into a Moodle course (and, for the
 * level not chosen as the course, into groups within it).
 *
 * Per spec.md's entity mapping table: CourseOffering or ComponentOffering maps to a Moodle course,
 * configurable via the offering_level setting - the level not chosen as the course becomes groups
 * within the course, when sync_groups is active. `idnumber` is always the offering's sourcedId.
 *
 * Also implements the `offering_level` no-migration detection resolved in spec.md: if the setting
 * changes after courses already exist under the previous level, existing courses are left untouched
 * and a flag is set for settings.php (development phase 4) to render its warning next to the
 * `offering_level` selector.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offering_converter {
    /** @var string `offering_level` value: CourseOffering becomes the Moodle course */
    const LEVEL_COURSE_OFFERING = 'courseoffering';

    /** @var string `offering_level` value: ComponentOffering becomes the Moodle course */
    const LEVEL_COMPONENT_OFFERING = 'componentoffering';

    /** @var string Internal (non-visible) config key tracking the offering_level used by the last sync */
    const CONFIG_LASTUSED = 'offering_level_lastused';

    /** @var string Internal config key flagging that offering_level changed with courses already synced */
    const CONFIG_CHANGED_WARNING = 'offering_level_changed_warning';

    /**
     * Get the configured `offering_level`, defaulting to `courseoffering` if unset/invalid.
     *
     * @return  string
     */
    public static function get_configured_offering_level(): string {
        $level = get_config('enrol_eduapi', 'offering_level');

        return in_array($level, [self::LEVEL_COURSE_OFFERING, self::LEVEL_COMPONENT_OFFERING], true)
            ? $level
            : self::LEVEL_COURSE_OFFERING;
    }

    /**
     * Detect an `offering_level` change since the last sync and apply the "no migration" policy.
     *
     * On the very first sync (no `offering_level_lastused` recorded yet), the current value is simply
     * recorded. On later syncs, if the level has changed AND at least one `enrol_eduapi` instance
     * already exists (i.e. courses have already been synced under the previous level), existing
     * courses are left untouched — the caller (development phase 5's sync orchestrator) must not
     * attempt to convert or recreate them — and `offering_level_changed_warning` is set so
     * settings.php can render its warning. If no `enrol_eduapi` instances exist yet, the new level is
     * adopted silently (there is nothing to migrate).
     *
     * DB-mutating (writes `offering_level_lastused`/`offering_level_changed_warning` config values;
     * reads the `enrol` table). Not exercised during development phase 3 verification.
     *
     * @return  bool True if the level changed AND existing courses must be left untouched
     */
    public static function detect_offering_level_change(): bool {
        global $DB;

        $current = self::get_configured_offering_level();
        $lastused = get_config('enrol_eduapi', self::CONFIG_LASTUSED);

        if (empty($lastused)) {
            set_config(self::CONFIG_LASTUSED, $current, 'enrol_eduapi');
            return false;
        }

        if ($lastused === $current) {
            return false;
        }

        $hasexistingcourses = $DB->record_exists('enrol', ['enrol' => 'eduapi']);
        if (!$hasexistingcourses) {
            // Nothing synced under the old level yet: safe to adopt the new one.
            set_config(self::CONFIG_LASTUSED, $current, 'enrol_eduapi');
            set_config(self::CONFIG_CHANGED_WARNING, 0, 'enrol_eduapi');
            return false;
        }

        set_config(self::CONFIG_CHANGED_WARNING, 1, 'enrol_eduapi');

        return true;
    }

    /**
     * Build the Moodle course fields for the given offering.
     *
     * Pure data transformation: no database access. `shortname` is derived from
     * `shortname_attribute` ('sourcedId' or 'primaryCode'; anything else falls back to sourcedId).
     * `fullname` is derived from `title` via converter::pick_language_value(), preferring the site's
     * default language (`$CFG->lang`, not the acting user's own current language, so the result is
     * stable no matter who triggers the sync) and honouring the `multilang` setting, with the
     * sourcedId as fallback when the resolved value is empty; the result is then truncated to 254
     * characters with core_text::substr(), since multilang markup can otherwise exceed the
     * `course.fullname`/`groups.name` column length and cause a DML error. When `sync_description` is
     * on and `description` resolves to a non-empty string, `summary` (as `FORMAT_MOODLE`, which
     * preserves the provider's line breaks while still running the multilang filter) is added from it;
     * otherwise neither `summary` nor `summaryformat` is present at all, so an existing course summary
     * is never blanked by convert()'s field-by-field update.
     *
     * @param   education_offering $offering A course_offering or component_offering entity
     * @return  stdClass Fields suitable for create_course()/update_course() (without `category`,
     *                   which convert() fills in after resolving the organization)
     */
    public static function get_course_data(education_offering $offering): stdClass {
        global $CFG;

        $multilang = (bool) get_config('enrol_eduapi', 'multilang');
        $sitelang = $CFG->lang ?? 'en';

        $titles = $offering->get('title') ?? [];
        $fullname = converter::pick_language_value($titles, $multilang, $sitelang);
        if ($fullname === '') {
            $fullname = $offering->get_id();
        }
        $fullname = core_text::substr($fullname, 0, 254);

        $coursedata = (object) [
            'idnumber' => $offering->get_id(),
            'fullname' => $fullname,
            'shortname' => self::extract_shortname($offering),
            'startdate' => converter::from_datetime_to_unix($offering->get('startDate')),
            'enddate' => converter::from_datetime_to_unix($offering->get('endDate')),
            'visible' => !in_array($offering->get('recordStatus'), ['inactive', 'deleted'], true),
        ];

        if (get_config('enrol_eduapi', 'sync_description')) {
            $descriptions = $offering->get('description') ?? [];
            $summary = converter::pick_language_value($descriptions, $multilang, $sitelang);
            if ($summary !== '') {
                $coursedata->summary = $summary;
                $coursedata->summaryformat = FORMAT_MOODLE;
            }
        }

        return $coursedata;
    }

    /**
     * Extract the course shortname per the `shortname_attribute` setting.
     *
     * @param   education_offering $offering
     * @return  string
     */
    protected static function extract_shortname(education_offering $offering): string {
        $attribute = get_config('enrol_eduapi', 'shortname_attribute');

        if ($attribute === 'primaryCode') {
            $code = $offering->get('primaryCode');
            if (!empty($code->identifier)) {
                return $code->identifier;
            }
        }

        // Attribute is sourcedId, unset, or a primaryCode with no identifier: fall back to the
        // sourcedId, which is always present and always unique.
        return $offering->get_id();
    }

    /**
     * Create or update the Moodle course for the given offering, and ensure its `enrol_eduapi`
     * instance exists.
     *
     * DB-mutating (creates/updates `course` rows and `enrol` instances). Not exercised during
     * development phase 3 verification — see this converter's class docblock and TIPEDUAPI-14's
     * verification report.
     *
     * @param   education_offering $offering A course_offering or component_offering entity that
     *                                       matches the configured `offering_level`
     * @return  stdClass|null The Moodle course record, or null if excluded by `exclude_inactive`
     */
    public static function convert(education_offering $offering): ?stdClass {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        if (get_config('enrol_eduapi', 'exclude_inactive') && $offering->get('recordStatus') === 'inactive') {
            return null;
        }

        $coursedata = self::get_course_data($offering);

        $organization = $offering->get_organization();
        if ($organization !== null) {
            $category = organization_converter::convert($organization);
            $coursedata->category = $category->id;
        }

        $localcourse = $DB->get_record('course', ['idnumber' => $coursedata->idnumber]);

        if ($localcourse) {
            // Never change the category of an already-synced course automatically: an administrator
            // may have deliberately moved it. Same precedent as enrol_oneroster.
            unset($coursedata->category);

            $update = false;
            foreach ((array) $coursedata as $field => $value) {
                if ($localcourse->{$field} != $value) {
                    $localcourse->{$field} = $value;
                    $update = true;
                }
            }

            if ($update) {
                update_course($localcourse);
            }
        } else {
            $coursedata->category = $coursedata->category ?? 1;
            $localcourse = create_course($coursedata);
        }

        self::ensure_enrol_instance_exists($localcourse);

        return $localcourse;
    }

    /**
     * Ensure an `enrol_eduapi` instance exists for the given Moodle course, creating it if necessary.
     *
     * DB-mutating. Not exercised during development phase 3 verification.
     *
     * @param   stdClass $course
     * @return  stdClass The enrol instance record
     */
    public static function ensure_enrol_instance_exists(stdClass $course): stdClass {
        global $DB;

        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'eduapi']);
        $status = $course->visible ? ENROL_INSTANCE_ENABLED : ENROL_INSTANCE_DISABLED;

        $plugin = enrol_get_plugin('eduapi');

        if ($instance) {
            if ((int) $instance->status !== $status) {
                $plugin->update_instance($instance, (object) ['status' => $status]);
            }

            return $instance;
        }

        $instanceid = $plugin->add_instance($course, ['status' => $status]);

        return $DB->get_record('enrol', ['id' => $instanceid]);
    }

    /**
     * Get the `enrol_eduapi` instance for a course previously synced from an offering, by that
     * offering's sourcedId.
     *
     * DB-reading only (no mutation). Used by enrollment_converter.php to find where to (un)enrol a
     * user without needing the full offering entity.
     *
     * @param   string $offeringsourcedid
     * @return  stdClass|null
     */
    public static function get_enrol_instance_for_offering(string $offeringsourcedid): ?stdClass {
        global $DB;

        $sql = <<<SQL
            SELECT e.*
              FROM {enrol} e
              JOIN {course} c ON c.id = e.courseid
             WHERE e.enrol = :enrol
               AND c.idnumber = :idnumber
SQL;

        return $DB->get_record_sql($sql, ['enrol' => 'eduapi', 'idnumber' => $offeringsourcedid]) ?: null;
    }

    /**
     * Create or update a Moodle group from a ComponentOffering, inside the given course, when the
     * chosen `offering_level` is `courseoffering` and `sync_groups` is active.
     *
     * The group name is derived the same way as get_course_data()'s `fullname`: preferring the site's
     * default language (`$CFG->lang`) over multilang markup depending on the `multilang` setting, then
     * truncated to 254 characters with core_text::substr() to stay within the `groups.name` column
     * length and avoid a DML error.
     *
     * DB-mutating (creates/updates `groups` rows). Not exercised during development phase 3
     * verification.
     *
     * @param   component_offering $component
     * @param   stdClass $course The Moodle course this component's parent CourseOffering was mapped to
     * @return  stdClass|null The group record, or null if `sync_groups` is not active
     */
    public static function convert_to_group(component_offering $component, stdClass $course): ?stdClass {
        global $CFG, $DB;

        if (!get_config('enrol_eduapi', 'sync_groups')) {
            return null;
        }

        require_once($CFG->dirroot . '/group/lib.php');

        $multilang = (bool) get_config('enrol_eduapi', 'multilang');
        $titles = $component->get('title') ?? [];
        $name = converter::pick_language_value($titles, $multilang, $CFG->lang ?? 'en');
        if ($name === '') {
            $name = $component->get_id();
        }
        $name = core_text::substr($name, 0, 254);

        $existing = $DB->get_record('groups', ['courseid' => $course->id, 'idnumber' => $component->get_id()]);

        if ($existing) {
            if ($existing->name !== $name) {
                $existing->name = $name;
                groups_update_group($existing);
            }

            return $existing;
        }

        $groupdata = (object) [
            'courseid' => $course->id,
            'name' => $name,
            'idnumber' => $component->get_id(),
        ];
        $groupid = groups_create_group($groupdata);

        return $DB->get_record('groups', ['id' => $groupid]);
    }
}
