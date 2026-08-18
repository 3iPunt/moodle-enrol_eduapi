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
 * Edu-API v1p0 EducationManagement endpoint.
 *
 * URLs and operation ids are taken directly from
 * assets/schema/openapi/eduapi_v1p0_openapi3.json (paths `/courseOfferings`,
 * `/courseOfferings/{id}`, `/componentOfferings`, `/componentOfferings/{id}`,
 * `/collectionOfferings`, `/collectionOfferings/{id}`, all under tag `EducationManagement`), not
 * invented. `EducationTemplates` operations from the same tag are intentionally not wired: they are
 * out of scope for this plugin (see spec.md's "Alcance excluido").
 *
 * The `getCollectionOfferingById`/`getAllCollectionOfferings` commands are declared for structural
 * completeness but are unverified against the development mock provider, which does not implement
 * `/collectionOfferings` (no converter in this plugin consumes CollectionOffering — see
 * entity_factory.php's note).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class education_management extends endpoint {
    // @codingStandardsIgnoreStart UpperCaseConstantNameSniff

    /** @var string Operation id to fetch all course offerings */
    const getAllCourseOfferings = 'getAllCourseOfferings';

    /** @var string Operation id to fetch a single course offering */
    const getCourseOfferingById = 'getCourseOfferingById';

    /** @var string Operation id to fetch all component offerings */
    const getAllComponentOfferings = 'getAllComponentOfferings';

    /** @var string Operation id to fetch a single component offering */
    const getComponentOfferingById = 'getComponentOfferingById';

    /** @var string Operation id to fetch all collection offerings */
    const getAllCollectionOfferings = 'getAllCollectionOfferings';

    /** @var string Operation id to fetch a single collection offering */
    const getCollectionOfferingById = 'getCollectionOfferingById';

    // @codingStandardsIgnoreEnd UpperCaseConstantNameSniff

    /** @var array List of commands and their configuration */
    protected static $commands = [
        self::getAllCourseOfferings => [
            'url' => '/courseOfferings',
            'method' => client_helper::GET,
            'description' => 'Read all CourseOfferings for an institution.',
            'collection' => true,
        ],
        self::getCourseOfferingById => [
            'url' => '/courseOfferings/:id',
            'method' => client_helper::GET,
            'description' => 'Read a CourseOffering with a specific id.',
        ],
        self::getAllComponentOfferings => [
            'url' => '/componentOfferings',
            'method' => client_helper::GET,
            'description' => 'Read all ComponentOfferings for an institution.',
            'collection' => true,
        ],
        self::getComponentOfferingById => [
            'url' => '/componentOfferings/:id',
            'method' => client_helper::GET,
            'description' => 'Read a ComponentOffering with a specific id.',
        ],
        self::getAllCollectionOfferings => [
            'url' => '/collectionOfferings',
            'method' => client_helper::GET,
            'description' => 'Read all CollectionOfferings for an institution.',
            'collection' => true,
        ],
        self::getCollectionOfferingById => [
            'url' => '/collectionOfferings/:id',
            'method' => client_helper::GET,
            'description' => 'Read a CollectionOffering with a specific id.',
        ],
    ];
}
