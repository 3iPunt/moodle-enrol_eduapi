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
 * Edu-API v1p0 AcademicSessionManagement endpoint.
 *
 * URLs and operation ids are taken directly from
 * assets/schema/openapi/eduapi_v1p0_openapi3.json (paths `/academicSessions` and
 * `/academicSessions/{id}`, tag `AcademicSessionManagement`), not invented.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class academic_session_management extends endpoint {
    // @codingStandardsIgnoreStart UpperCaseConstantNameSniff

    /** @var string Operation id to fetch all academic sessions */
    const getAllAcademicSessions = 'getAllAcademicSessions';

    /** @var string Operation id to fetch a single academic session */
    const getAcademicSessionById = 'getAcademicSessionById';

    // @codingStandardsIgnoreEnd UpperCaseConstantNameSniff

    /** @var array List of commands and their configuration */
    protected static $commands = [
        self::getAllAcademicSessions => [
            'url' => '/academicSessions',
            'method' => client_helper::GET,
            'description' => 'Read all AcademicSessions for an institution.',
            'collection' => true,
        ],
        self::getAcademicSessionById => [
            'url' => '/academicSessions/:id',
            'method' => client_helper::GET,
            'description' => 'Read an AcademicSession with a specific id.',
        ],
    ];
}
