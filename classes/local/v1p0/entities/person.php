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
use enrol_eduapi\local\v1p0\endpoints\person_management;

/**
 * Edu-API v1p0 Person entity.
 *
 * Fields per assets/schema/json/eduapi_v1p0_person-jsonschema1.json: `sourcedId`, `recordLanguage`,
 * `legalName` (PersonName: `familyName`, `givenName`, ...), `primaryEmail` (OptionallyTypedEmail:
 * `email`, `emailType`), `otherIdentifiers` (IdentifierEntry[]: `identifier`, `identifierType`),
 * `recordStatus`.
 *
 * This entity intentionally exposes no Moodle-user mapping logic: resolving `user_match_moodlefield` /
 * `user_match_source` against a Moodle user and persisting `enrol_eduapi_user_map` is
 * classes/local/converters/person_converter.php's job (development phase 3).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class person extends entity {
    /**
     * Get the endpoint that serves this entity type.
     *
     * @param   container_interface $container
     * @return  endpoint_interface
     */
    protected static function get_endpoint(container_interface $container): endpoint_interface {
        return $container->get_endpoint('person');
    }

    /**
     * Get the operation ID for the by-id lookup of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    protected static function get_operation_id(container_interface $container): string {
        return person_management::getPersonById;
    }

    /**
     * Get the value of a specific `otherIdentifiers` entry, by its `identifierType`.
     *
     * Used by person_converter.php when `user_match_source` selects an `otherIdentifiers` type rather
     * than `primaryEmail` or `sourcedId`.
     *
     * @param   string $identifiertype One of the IdentifierTypeEnum values (or an `ext:` extension)
     * @return  string|null The identifier value, or null if no matching entry exists
     */
    public function get_other_identifier(string $identifiertype): ?string {
        $entries = $this->get('otherIdentifiers') ?? [];
        foreach ($entries as $entry) {
            if (($entry->identifierType ?? null) === $identifiertype) {
                return $entry->identifier ?? null;
            }
        }

        return null;
    }
}
