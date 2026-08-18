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
 * Edu-API v1p0 ComponentOffering entity.
 *
 * Adds `component` (ComponentTemplate sourcedId — not resolved, templates are out of scope per
 * spec.md), `offeringType` (ComponentTypeEnum) and `parent` (array of parent CourseOffering
 * sourcedIds — a JSON array per its schema, even though a component practically has one parent) to the
 * fields shared via education_offering.php. Per spec.md's entity mapping, this becomes a Moodle course
 * of its own when `offering_level = componentoffering`, or a group inside its parent CourseOffering's
 * course when `offering_level = courseoffering` and `sync_groups` is active.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class component_offering extends education_offering {
    /**
     * Get the operation ID for the by-id lookup of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    protected static function get_operation_id(container_interface $container): string {
        return education_management::getComponentOfferingById;
    }

    /**
     * Get the sourcedId(s) of the parent CourseOffering(s), if any.
     *
     * @return  string[]
     */
    public function get_parent_ids(): array {
        return $this->get('parent') ?? [];
    }
}
