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

namespace enrol_eduapi\local\v1p0\endpoints;

use enrol_eduapi\client_helper;
use enrol_eduapi\local\endpoint;

/**
 * Edu-API v1p0 PersonManagement endpoint.
 *
 * URLs and operation ids are taken directly from
 * assets/schema/openapi/eduapi_v1p0_openapi3.json (paths `/persons` and `/persons/{id}`, tag
 * `PersonManagement`), not invented. The `getAllStaff`/`getAllStudents` convenience operations in that
 * same tag are not wired here: the plugin fetches all persons and role-filters them via Enrollment/
 * Affiliation records instead (see classes/local/converters/, development phase 3).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person_management extends endpoint {
    // @codingStandardsIgnoreStart UpperCaseConstantNameSniff

    /** @var string Operation id to fetch all persons */
    const getAllPersons = 'getAllPersons';

    /** @var string Operation id to fetch a single person */
    const getPersonById = 'getPersonById';

    // @codingStandardsIgnoreEnd UpperCaseConstantNameSniff

    /** @var array List of commands and their configuration */
    protected static $commands = [
        self::getAllPersons => [
            'url' => '/persons',
            'method' => client_helper::GET,
            'description' => 'Read all Persons for an institution.',
            'collection' => true,
        ],
        self::getPersonById => [
            'url' => '/persons/:id',
            'method' => client_helper::GET,
            'description' => 'Read a Person with a specific id.',
        ],
    ];
}
