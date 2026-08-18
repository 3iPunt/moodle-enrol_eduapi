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

use enrol_eduapi\local\interfaces\collection_factory as collection_factory_interface;
use enrol_eduapi\local\interfaces\filter as filter_interface;
use enrol_eduapi\local\v1p0\collections\academic_sessions;
use enrol_eduapi\local\v1p0\collections\affiliations;
use enrol_eduapi\local\v1p0\collections\collection_offerings;
use enrol_eduapi\local\v1p0\collections\component_offerings;
use enrol_eduapi\local\v1p0\collections\course_offerings;
use enrol_eduapi\local\v1p0\collections\enrollments;
use enrol_eduapi\local\v1p0\collections\enrollments_for_component_offering;
use enrol_eduapi\local\v1p0\collections\enrollments_for_course_offering;
use enrol_eduapi\local\v1p0\collections\organizations;
use enrol_eduapi\local\v1p0\collections\persons;

/**
 * Edu-API generic collection factory.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collection_factory extends abstract_factory implements collection_factory_interface {

    /**
     * Get the collection of all organizations.
     *
     * @param   filter_interface|null $filter
     * @return  organizations
     */
    public function get_organizations(?filter_interface $filter = null): organizations {
        return new organizations($this->container, [], $filter);
    }

    /**
     * Get the collection of all academic sessions.
     *
     * @param   filter_interface|null $filter
     * @return  academic_sessions
     */
    public function get_academic_sessions(?filter_interface $filter = null): academic_sessions {
        return new academic_sessions($this->container, [], $filter);
    }

    /**
     * Get the collection of all persons.
     *
     * @param   filter_interface|null $filter
     * @return  persons
     */
    public function get_persons(?filter_interface $filter = null): persons {
        return new persons($this->container, [], $filter);
    }

    /**
     * Get the collection of all course offerings.
     *
     * @param   filter_interface|null $filter
     * @return  course_offerings
     */
    public function get_course_offerings(?filter_interface $filter = null): course_offerings {
        return new course_offerings($this->container, [], $filter);
    }

    /**
     * Get the collection of all component offerings.
     *
     * @param   filter_interface|null $filter
     * @return  component_offerings
     */
    public function get_component_offerings(?filter_interface $filter = null): component_offerings {
        return new component_offerings($this->container, [], $filter);
    }

    /**
     * Get the collection of all collection offerings.
     *
     * Not wired to a live endpoint command in this version — see entity_factory.php's equivalent note.
     *
     * @param   filter_interface|null $filter
     * @return  collection_offerings
     */
    public function get_collection_offerings(?filter_interface $filter = null): collection_offerings {
        return new collection_offerings($this->container, [], $filter);
    }

    /**
     * Get the collection of all enrollments.
     *
     * @param   filter_interface|null $filter
     * @return  enrollments
     */
    public function get_enrollments(?filter_interface $filter = null): enrollments {
        return new enrollments($this->container, [], $filter);
    }

    /**
     * Get the collection of enrollments for a specific course offering.
     *
     * @param   string $courseofferingid
     * @param   filter_interface|null $filter
     * @return  enrollments_for_course_offering
     */
    public function get_enrollments_for_course_offering(
        string $courseofferingid,
        ?filter_interface $filter = null
    ): enrollments_for_course_offering {
        return new enrollments_for_course_offering($this->container, [':id' => $courseofferingid], $filter);
    }

    /**
     * Get the collection of enrollments for a specific component offering.
     *
     * @param   string $componentofferingid
     * @param   filter_interface|null $filter
     * @return  enrollments_for_component_offering
     */
    public function get_enrollments_for_component_offering(
        string $componentofferingid,
        ?filter_interface $filter = null
    ): enrollments_for_component_offering {
        return new enrollments_for_component_offering($this->container, [':id' => $componentofferingid], $filter);
    }

    /**
     * Get the collection of all affiliations.
     *
     * @param   filter_interface|null $filter
     * @return  affiliations
     */
    public function get_affiliations(?filter_interface $filter = null): affiliations {
        return new affiliations($this->container, [], $filter);
    }
}
