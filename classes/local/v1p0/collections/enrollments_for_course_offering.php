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

namespace enrol_eduapi\local\v1p0\collections;

use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\endpoints\enrollment_management;

/**
 * Edu-API v1p0 Enrollments-for-a-CourseOffering collection.
 *
 * Reuses enrollments.php's endpoint and row-parsing, overriding only the operation id — the same
 * pattern enrol_oneroster uses for its `classes_for_user extends classes_collection`. Constructed with
 * params `[':id' => $courseofferingid]` (see classes/local/factories/collection_factory.php).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrollments_for_course_offering extends enrollments {
    /**
     * Get the operation ID for the paginated list of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    protected static function get_operation_id(container_interface $container): string {
        return enrollment_management::getAllEnrollmentsForCourseOffering;
    }
}
