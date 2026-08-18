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

namespace enrol_eduapi\local\factories;

use enrol_eduapi\local\interfaces\entity_factory as entity_factory_interface;
use enrol_eduapi\local\v1p0\entities\affiliation as affiliation_entity;
use enrol_eduapi\local\v1p0\entities\academic_session as academic_session_entity;
use enrol_eduapi\local\v1p0\entities\collection_offering as collection_offering_entity;
use enrol_eduapi\local\v1p0\entities\component_offering as component_offering_entity;
use enrol_eduapi\local\v1p0\entities\course_offering as course_offering_entity;
use enrol_eduapi\local\v1p0\entities\enrollment as enrollment_entity;
use enrol_eduapi\local\v1p0\entities\organization as organization_entity;
use enrol_eduapi\local\v1p0\entities\person as person_entity;
use stdClass;

/**
 * Edu-API generic entity factory.
 *
 * Unlike enrol_oneroster's entity_factory (which dispatches an Organization result to either a
 * `school` or `org` PHP class depending on its OneRoster `type`, and caches results per-type via
 * cache_factory), Edu-API's Organization has a single flat shape (`organizationType` is a free string,
 * not an enumerated subtype), so there is no dispatch to make: each `get_X_from_result()` method here
 * simply wraps the raw row in the one matching entity class. Caching is not wired in yet either —
 * classes/local/factories/cache_factory.php exists and works, but nothing here calls it: doing so is
 * left for development phase 5, when the full sync orchestrator makes repeated lookups of the same
 * entities across a single run worth optimising.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class entity_factory extends abstract_factory implements entity_factory_interface {
    /**
     * Get an Organization entity from a raw row.
     *
     * @param   stdClass $data
     * @return  organization_entity
     */
    public function get_organization_from_result(stdClass $data): organization_entity {
        return new organization_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch an Organization entity by its sourcedId.
     *
     * @param   string $id
     * @return  organization_entity
     */
    public function fetch_organization_by_id(string $id): organization_entity {
        return new organization_entity($this->container, $id);
    }

    /**
     * Get an AcademicSession entity from a raw row.
     *
     * @param   stdClass $data
     * @return  academic_session_entity
     */
    public function get_academic_session_from_result(stdClass $data): academic_session_entity {
        return new academic_session_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch an AcademicSession entity by its sourcedId.
     *
     * @param   string $id
     * @return  academic_session_entity
     */
    public function fetch_academic_session_by_id(string $id): academic_session_entity {
        return new academic_session_entity($this->container, $id);
    }

    /**
     * Get a Person entity from a raw row.
     *
     * @param   stdClass $data
     * @return  person_entity
     */
    public function get_person_from_result(stdClass $data): person_entity {
        return new person_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch a Person entity by its sourcedId.
     *
     * @param   string $id
     * @return  person_entity
     */
    public function fetch_person_by_id(string $id): person_entity {
        return new person_entity($this->container, $id);
    }

    /**
     * Get a CourseOffering entity from a raw row.
     *
     * @param   stdClass $data
     * @return  course_offering_entity
     */
    public function get_course_offering_from_result(stdClass $data): course_offering_entity {
        return new course_offering_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch a CourseOffering entity by its sourcedId.
     *
     * @param   string $id
     * @return  course_offering_entity
     */
    public function fetch_course_offering_by_id(string $id): course_offering_entity {
        return new course_offering_entity($this->container, $id);
    }

    /**
     * Get a ComponentOffering entity from a raw row.
     *
     * @param   stdClass $data
     * @return  component_offering_entity
     */
    public function get_component_offering_from_result(stdClass $data): component_offering_entity {
        return new component_offering_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch a ComponentOffering entity by its sourcedId.
     *
     * @param   string $id
     * @return  component_offering_entity
     */
    public function fetch_component_offering_by_id(string $id): component_offering_entity {
        return new component_offering_entity($this->container, $id);
    }

    /**
     * Get a CollectionOffering entity from a raw row.
     *
     * Not wired to a live endpoint command in this version: CollectionOffering has no consumer in the
     * plugin (course/component offerings carry everything the converters need — see spec.md's
     * excluded-scope section) and is not served by the development mock provider. It exists for
     * structural parity with the EducationOffering hierarchy documented in plan.md.
     *
     * @param   stdClass $data
     * @return  collection_offering_entity
     */
    public function get_collection_offering_from_result(stdClass $data): collection_offering_entity {
        return new collection_offering_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch a CollectionOffering entity by its sourcedId.
     *
     * @param   string $id
     * @return  collection_offering_entity
     */
    public function fetch_collection_offering_by_id(string $id): collection_offering_entity {
        return new collection_offering_entity($this->container, $id);
    }

    /**
     * Get an Enrollment entity from a raw row.
     *
     * @param   stdClass $data
     * @return  enrollment_entity
     */
    public function get_enrollment_from_result(stdClass $data): enrollment_entity {
        return new enrollment_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch an Enrollment entity by its sourcedId.
     *
     * @param   string $id
     * @return  enrollment_entity
     */
    public function fetch_enrollment_by_id(string $id): enrollment_entity {
        return new enrollment_entity($this->container, $id);
    }

    /**
     * Get an Affiliation entity from a raw row.
     *
     * @param   stdClass $data
     * @return  affiliation_entity
     */
    public function get_affiliation_from_result(stdClass $data): affiliation_entity {
        return new affiliation_entity($this->container, $data->sourcedId, $data);
    }

    /**
     * Fetch an Affiliation entity by its sourcedId.
     *
     * @param   string $id
     * @return  affiliation_entity
     */
    public function fetch_affiliation_by_id(string $id): affiliation_entity {
        return new affiliation_entity($this->container, $id);
    }
}
