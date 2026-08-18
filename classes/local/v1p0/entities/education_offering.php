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

namespace enrol_eduapi\local\v1p0\entities;

use enrol_eduapi\local\entity;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\interfaces\endpoint as endpoint_interface;

/**
 * Edu-API v1p0 EducationOffering abstract entity.
 *
 * Common base for CollectionOffering, CourseOffering and ComponentOffering (the "AbstractEducations"
 * package in the Edu-API v1p0 data model), which all share the same core fields: `sourcedId`,
 * `recordLanguage`, `title`/`description` (LanguageTypedString[]), `primaryCode` (IdentifierEntry),
 * `organization` (Organization sourcedId), `academicSession` (AcademicSession sourcedId),
 * `offeringFormat`, `registrationStatus`, `startDate`/`endDate`, `recordStatus`. All three subclasses
 * are served by the same EducationManagement service group, hence the shared get_endpoint().
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class education_offering extends entity {
    /**
     * Get the endpoint that serves every EducationOffering subtype.
     *
     * @param   container_interface $container
     * @return  endpoint_interface
     */
    protected static function get_endpoint(container_interface $container): endpoint_interface {
        return $container->get_endpoint('education');
    }

    /**
     * Get the sourcedId of the owning Organization.
     *
     * @return  string|null
     */
    public function get_organization_id(): ?string {
        return $this->get('organization');
    }

    /**
     * Get the owning Organization entity.
     *
     * @return  organization|null
     */
    public function get_organization(): ?organization {
        $id = $this->get_organization_id();
        if ($id === null) {
            return null;
        }

        return $this->container->get_entity_factory()->fetch_organization_by_id($id);
    }

    /**
     * Get the sourcedId of the AcademicSession this offering belongs to.
     *
     * @return  string|null
     */
    public function get_academic_session_id(): ?string {
        return $this->get('academicSession');
    }
}
