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
 * Edu-API v1p0 CollectionOffering entity.
 *
 * Adds `collection` (CollectionTemplate sourcedId) and `offeringType` (CollectionTypeEnum) to the
 * fields shared via education_offering.php. Not consumed by any converter in this plugin (see
 * classes/local/factories/entity_factory.php's note) and unverified against the development mock
 * provider, which does not implement `/collectionOfferings`. Exists for structural parity with the
 * EducationOffering hierarchy documented in plan.md.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collection_offering extends education_offering {

    /**
     * Get the operation ID for the by-id lookup of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    protected static function get_operation_id(container_interface $container): string {
        return education_management::getCollectionOfferingById;
    }
}
