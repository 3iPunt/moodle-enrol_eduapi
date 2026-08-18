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

namespace enrol_eduapi\local\v1p0;

use enrol_eduapi\local\converters\enrollment_converter;
use enrol_eduapi\local\converters\offering_converter;
use enrol_eduapi\local\converters\organization_converter;
use enrol_eduapi\local\v1p0\entities\course_offering;
use enrol_eduapi\local\v1p0\entities\education_offering;
use null_progress_trace;
use progress_trace;
use Throwable;

/**
 * Orchestrates a full Edu-API sync, delegating all Moodle-mapping logic to the
 * classes/local/converters/ (development phase 3), in the order documented in spec.md:
 * organizaciones → sesión académica → offerings → personas → matrículas.
 *
 * Unlike enrol_oneroster's oneroster_client.php (~74 KB, all sync logic in one file), this class is
 * a thin orchestrator: it resolves the sync SCOPE (which organizations, which academic session, which
 * offering level) and loops, but every actual Moodle write goes through a converter.
 *
 * "Personas" are not synced as a separate eager step: Edu-API has no organization-scoped `/persons`
 * endpoint (only a global collection), so eagerly fetching every Person in the provider regardless of
 * whether they are ever enrolled in anything in scope would be wasteful. Instead, each Enrollment is
 * resolved together with its Person at the point enrollment_converter::convert() processes it — which
 * still respects the spec's documented order (a person is only ever synced immediately before the
 * matrícula that needs it).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eduapi_client {
    /** @var container */
    protected $container;

    /** @var progress_trace|null */
    protected $trace;

    /**
     * Create a new eduapi_client.
     *
     * @param   container $container
     */
    public function __construct(container $container) {
        $this->container = $container;
    }

    /**
     * Set the log tracer.
     *
     * @param   progress_trace $trace
     */
    public function set_trace(progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * Get the log tracer.
     *
     * @return  progress_trace
     */
    public function get_trace(): progress_trace {
        if ($this->trace === null) {
            $this->trace = new null_progress_trace();
        }

        return $this->trace;
    }

    /**
     * Get the Organization sourcedIds configured in `datasync_organizations`.
     *
     * `admin_setting_configmultiselect` stores its selection as a comma-separated string.
     *
     * @return  string[]
     */
    protected function get_scoped_organization_ids(): array {
        $configured = get_config('enrol_eduapi', 'datasync_organizations');
        if (empty($configured)) {
            return [];
        }

        return array_values(array_filter(explode(',', $configured)));
    }

    /**
     * Whether the given entity should be excluded from the sync because it is inactive and
     * `exclude_inactive` is active.
     *
     * Per spec.md, `exclude_inactive` applies to organizations, offerings and persons — never to
     * Enrollment, whose own 17-value `enrollmentStatus` mapping (enrollment_converter.php) is the
     * separate, dedicated filtering axis for matrícula records.
     *
     * @param   object $entity Any entity exposing get('recordStatus')
     * @return  bool
     */
    protected function is_excluded_by_record_status($entity): bool {
        return (bool) get_config('enrol_eduapi', 'exclude_inactive') && $entity->get('recordStatus') === 'inactive';
    }

    /**
     * Run the full Edu-API sync, or (if `$filter` names a single offering) a scoped sync of just that
     * offering — the latter is what development phase 6's `eduapi_offering_webhook` web service will
     * call.
     *
     * DB-mutating (via the converters). Not exercised during development phase 4/5 verification, which
     * must not mutate the Moodle database — see TIPEDUAPI-14's verification reports.
     *
     * @param   array|null $filter `['offering_sourcedid' => string]` to sync a single offering, or
     *                             null for a full sync
     */
    public function synchronise(?array $filter = null): void {
        $trace = $this->get_trace();
        $trace->output('Starting Edu-API sync');

        if (offering_converter::detect_offering_level_change()) {
            $trace->output(
                "offering_level has changed since the last sync and courses already exist under the " .
                "previous level: those courses are left untouched (no migration). See the warning on " .
                "the plugin's settings page."
            );
        }

        if (!empty($filter['offering_sourcedid'])) {
            $this->synchronise_single_offering($filter['offering_sourcedid'], $trace);
            $trace->output('Edu-API sync done (single offering)');
            return;
        }

        $errors = 0;

        $organizationids = $this->get_scoped_organization_ids();
        if (empty($organizationids)) {
            $trace->output('No organizations configured in datasync_organizations: nothing to sync.');
            return;
        }

        foreach ($organizationids as $organizationid) {
            try {
                $organization = $this->container->get_entity_factory()->fetch_organization_by_id($organizationid);
                if ($this->is_excluded_by_record_status($organization)) {
                    $trace->output("Excluding organization '{$organizationid}': recordStatus is inactive.", 1);
                    continue;
                }
                organization_converter::convert($organization, $trace);
            } catch (Throwable $e) {
                $trace->output("Organization '{$organizationid}' failed: " . $e->getMessage());
                $errors++;
            }
        }

        $sessionid = get_config('enrol_eduapi', 'datasync_academic_session');
        $offeringlevel = offering_converter::get_configured_offering_level();
        $touchedcourseidnumbers = [];

        if ($offeringlevel === offering_converter::LEVEL_COURSE_OFFERING) {
            $errors += $this->iterate_safely(
                $this->container->get_collection_factory()->get_course_offerings(),
                function ($courseoffering) use ($organizationids, $sessionid, &$touchedcourseidnumbers, $trace) {
                    if (!$this->offering_in_scope($courseoffering, $organizationids, $sessionid)) {
                        return 0;
                    }
                    return $this->sync_offering($courseoffering, $touchedcourseidnumbers, $trace, true);
                },
                $trace,
                'course offerings'
            );
        } else {
            $errors += $this->iterate_safely(
                $this->container->get_collection_factory()->get_component_offerings(),
                function ($componentoffering) use ($organizationids, $sessionid, &$touchedcourseidnumbers, $trace) {
                    if (!$this->offering_in_scope($componentoffering, $organizationids, $sessionid)) {
                        return 0;
                    }
                    return $this->sync_offering($componentoffering, $touchedcourseidnumbers, $trace, false);
                },
                $trace,
                'component offerings'
            );
        }

        if (!get_config('enrol_eduapi', 'keep_existing_courses')) {
            $this->disable_untouched_courses($touchedcourseidnumbers, $trace);
        }

        $trace->output("Edu-API sync done with {$errors} errors");
    }

    /**
     * Whether the given offering is within the configured organization/academic-session scope and is
     * not excluded by `exclude_inactive`.
     *
     * @param   education_offering $offering
     * @param   string[] $organizationids
     * @param   string|false $sessionid
     * @return  bool
     */
    protected function offering_in_scope(education_offering $offering, array $organizationids, $sessionid): bool {
        if (!in_array($offering->get_organization_id(), $organizationids, true)) {
            return false;
        }

        if (!empty($sessionid) && $offering->get_academic_session_id() !== $sessionid) {
            return false;
        }

        return !$this->is_excluded_by_record_status($offering);
    }

    /**
     * Iterate a (possibly paginated) collection, catching failures raised both from processing an
     * individual item and from advancing the iterator itself (i.e. fetching the NEXT page over HTTP).
     *
     * A plain `foreach ($collection as $item) { try { ... } catch (...) {} }` only protects the loop
     * BODY: our collections are backed by a PHP Generator (see collection::getIterator() and
     * endpoint::execute_paginated_function()), and the HTTP call for the next page happens when the
     * `foreach` construct itself resumes the generator to advance to the next item — OUTSIDE any
     * try/catch placed inside the loop body. A transient failure there (429/500/network) would
     * otherwise propagate up uncaught and abort everything the caller still had left to do (other
     * offerings, other organizations...), contradicting the "one failing instance must not interrupt
     * the whole sync" requirement. This helper drives the iterator manually instead, so a pagination
     * failure only stops THIS collection early (a Generator cannot be resumed once it has thrown)
     * while the caller safely continues with whatever else remains.
     *
     * @param   \IteratorAggregate $collection
     * @param   callable $itemhandler Invoked once per successfully-fetched item; must return the
     *                                number of errors encountered handling that item (0 if none). Any
     *                                exception it lets escape is also caught and logged.
     * @param   progress_trace $trace
     * @param   string $context Human-readable label used in trace messages (e.g. "course offerings")
     * @return  int The total number of errors encountered (per-item failures, plus at most one more
     *              if pagination itself failed)
     */
    protected function iterate_safely(
        \IteratorAggregate $collection,
        callable $itemhandler,
        progress_trace $trace,
        string $context
    ): int {
        $iterator = $collection->getIterator();
        $errors = 0;

        try {
            $iterator->rewind();
        } catch (Throwable $e) {
            $trace->output("Fetching {$context} failed: " . $e->getMessage());
            return 1;
        }

        while (true) {
            try {
                if (!$iterator->valid()) {
                    break;
                }
                $item = $iterator->current();
            } catch (Throwable $e) {
                $trace->output("Fetching {$context} failed while paginating: " . $e->getMessage() .
                    ' (remaining records in this collection were skipped for this run)');
                $errors++;
                break;
            }

            try {
                $errors += (int) $itemhandler($item);
            } catch (Throwable $e) {
                $trace->output(ucfirst($context) . ' item failed: ' . $e->getMessage());
                $errors++;
            }

            try {
                $iterator->next();
            } catch (Throwable $e) {
                $trace->output("Fetching further {$context} failed while paginating: " . $e->getMessage() .
                    ' (remaining records in this collection were skipped for this run)');
                $errors++;
                break;
            }
        }

        return $errors;
    }

    /**
     * Convert one offering to a Moodle course, its groups (if applicable), and all of its enrolments.
     *
     * @param   education_offering $offering A course_offering or component_offering entity
     * @param   string[] $touchedcourseidnumbers Accumulator of idnumbers of courses touched this run
     *                                           (passed by reference)
     * @param   progress_trace $trace
     * @param   bool $iscourselevel Whether $offering is a course_offering (the chosen course level)
     * @return  int The number of errors encountered while processing this offering
     */
    protected function sync_offering(
        education_offering $offering,
        array &$touchedcourseidnumbers,
        progress_trace $trace,
        bool $iscourselevel
    ): int {
        $errors = 0;

        try {
            $course = offering_converter::convert($offering);
        } catch (Throwable $e) {
            $trace->output("Offering '{$offering->get_id()}' failed: " . $e->getMessage());
            return 1;
        }

        if ($course === null) {
            // Excluded by exclude_inactive inside the converter, or otherwise not synced.
            return 0;
        }

        $touchedcourseidnumbers[] = $course->idnumber;

        if ($iscourselevel && $offering instanceof course_offering && get_config('enrol_eduapi', 'sync_groups')) {
            $errors += $this->sync_groups_for_course_offering($offering, $course, $trace);
        }

        $enrollments = $iscourselevel
            ? $this->container->get_collection_factory()->get_enrollments_for_course_offering($offering->get_id())
            : $this->container->get_collection_factory()->get_enrollments_for_component_offering($offering->get_id());

        $errors += $this->iterate_safely(
            $enrollments,
            function ($enrollment) use ($trace) {
                try {
                    enrollment_converter::convert($enrollment, $trace);
                    return 0;
                } catch (Throwable $e) {
                    $trace->output("Enrollment '{$enrollment->get_id()}' failed: " . $e->getMessage());
                    return 1;
                }
            },
            $trace,
            "enrollments for offering '{$offering->get_id()}'"
        );

        return $errors;
    }

    /**
     * Create/update a Moodle group for every ComponentOffering that lists $courseoffering as a parent.
     *
     * @param   course_offering $courseoffering
     * @param   \stdClass $course The Moodle course $courseoffering was mapped to
     * @param   progress_trace $trace
     * @return  int The number of errors encountered
     */
    protected function sync_groups_for_course_offering(
        course_offering $courseoffering,
        \stdClass $course,
        progress_trace $trace
    ): int {
        return $this->iterate_safely(
            $this->container->get_collection_factory()->get_component_offerings(),
            function ($component) use ($courseoffering, $course, $trace) {
                if (!in_array($courseoffering->get_id(), $component->get_parent_ids(), true)) {
                    return 0;
                }

                try {
                    offering_converter::convert_to_group($component, $course);
                    return 0;
                } catch (Throwable $e) {
                    $trace->output("Group for component offering '{$component->get_id()}' failed: " . $e->getMessage());
                    return 1;
                }
            },
            $trace,
            'component offerings (group sync)'
        );
    }

    /**
     * Sync a single offering by sourcedId, per the configured `offering_level` — used by the
     * `eduapi_offering_webhook` web service (development phase 6) to sync "this offering now" without
     * a full sync.
     *
     * @param   string $offeringsourcedid
     * @param   progress_trace $trace
     */
    protected function synchronise_single_offering(string $offeringsourcedid, progress_trace $trace): void {
        $offeringlevel = offering_converter::get_configured_offering_level();
        $touchedcourseidnumbers = [];

        try {
            if ($offeringlevel === offering_converter::LEVEL_COURSE_OFFERING) {
                $offering = $this->container->get_entity_factory()->fetch_course_offering_by_id($offeringsourcedid);
                $this->sync_offering($offering, $touchedcourseidnumbers, $trace, true);
            } else {
                $offering = $this->container->get_entity_factory()->fetch_component_offering_by_id($offeringsourcedid);
                $this->sync_offering($offering, $touchedcourseidnumbers, $trace, false);
            }
        } catch (Throwable $e) {
            $trace->output("Offering '{$offeringsourcedid}' failed: " . $e->getMessage());
        }
    }

    /**
     * Disable (never delete) the `enrol_eduapi` instance of every previously-synced course whose
     * idnumber was not touched in this run, when `keep_existing_courses` is off.
     *
     * DESIGN DECISION (spec.md describes only the `keep_existing_courses = true` behaviour —
     * "los cursos... no se archivan ni eliminan" — and does not specify the exact Moodle mechanics for
     * the opposite case). This deliberately conservative, reversible interpretation disables the
     * enrolment method rather than deleting the course or any of its data, consistent with how Moodle
     * enrol plugins generally treat "no longer in the source" (suspend/disable), never with an
     * irreversible course deletion. Worth confirming with the client if a stronger action (e.g.
     * archiving) is actually wanted.
     *
     * @param   string[] $touchedcourseidnumbers idnumbers of courses touched during this sync run
     * @param   progress_trace $trace
     */
    protected function disable_untouched_courses(array $touchedcourseidnumbers, progress_trace $trace): void {
        global $DB;

        $sql = <<<SQL
            SELECT e.*, c.idnumber
              FROM {enrol} e
              JOIN {course} c ON c.id = e.courseid
             WHERE e.enrol = :enrol
               AND e.status = :enabled
SQL;
        $instances = $DB->get_records_sql($sql, ['enrol' => 'eduapi', 'enabled' => ENROL_INSTANCE_ENABLED]);

        $plugin = enrol_get_plugin('eduapi');
        foreach ($instances as $instance) {
            if (in_array($instance->idnumber, $touchedcourseidnumbers, true)) {
                continue;
            }

            $plugin->update_instance($instance, (object) ['status' => ENROL_INSTANCE_DISABLED]);
            $trace->output("Disabled enrol_eduapi instance for course '{$instance->idnumber}': no longer in sync scope.");
        }
    }
}
