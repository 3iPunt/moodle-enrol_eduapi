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

use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\endpoints\education_management;

/**
 * Edu-API v1p0 CourseOffering entity.
 *
 * Adds `course` (CourseTemplate sourcedId — not resolved, templates are out of scope per spec.md) and
 * `offeringType` (CourseTypeEnum) to the fields shared via education_offering.php. Per spec.md's entity
 * mapping, this becomes a Moodle course when `offering_level = courseoffering` (the default), with its
 * child ComponentOfferings becoming groups when `sync_groups` is active.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_offering extends education_offering {
    /**
     * Get the operation ID for the by-id lookup of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    protected static function get_operation_id(container_interface $container): string {
        return education_management::getCourseOfferingById;
    }
}
