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
use enrol_eduapi\local\v1p0\endpoints\academic_session_management;

/**
 * Edu-API v1p0 AcademicSession entity.
 *
 * Fields per assets/schema/json/eduapi_v1p0_academicsession-jsonschema1.json: `sourcedId`,
 * `recordLanguage`, `sessionType` (AcademicSessionTypeEnum), `title` (LanguageTypedString[]),
 * `startDate`, `endDate`, `parent`/`children` (nested sessions), `recordStatus`.
 *
 * Per spec.md, AcademicSession is not materialised as a Moodle object: it only scopes which
 * offerings/enrollments the sync (development phase 5) processes, via the `datasync_academic_session`
 * setting.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class academic_session extends entity {

    /**
     * Get the endpoint that serves this entity type.
     *
     * @param   container_interface $container
     * @return  endpoint_interface
     */
    protected static function get_endpoint(container_interface $container): endpoint_interface {
        return $container->get_endpoint('academic_session');
    }

    /**
     * Get the operation ID for the by-id lookup of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    protected static function get_operation_id(container_interface $container): string {
        return academic_session_management::getAcademicSessionById;
    }
}
