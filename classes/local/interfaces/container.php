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

namespace enrol_eduapi\local\interfaces;

use enrol_eduapi\local\interfaces\client as client_interface;

/**
 * An Edu-API Container: wires together the client, factories and endpoints.
 *
 * Unlike enrol_oneroster's container (which exposes a single
 * `get_rostering_endpoint()`, because OneRoster collapses every operation
 * into one "rostering" service), Edu-API v1p0's operations are grouped by
 * several service interfaces (OrganizationManagement, PersonManagement,
 * EducationManagement, ...), so this container exposes a single generic
 * `get_endpoint(string $name)` dispatcher instead of one getter per group —
 * see classes/local/v1p0/container.php for the concrete mapping.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface container {

    /**
     * Constructor for the new container.
     *
     * @param   client_interface $client
     */
    public function __construct(client_interface $client);

    /**
     * Get the client instance.
     *
     * @return  client_interface
     */
    public function get_client(): client_interface;

    /**
     * Get the endpoint for the named service group.
     *
     * @param   string $name One of 'organization', 'academic_session', 'person', 'education',
     *                       'enrollment', 'affiliation'
     * @return  endpoint
     */
    public function get_endpoint(string $name): endpoint;

    /**
     * Get the Entity Factory.
     *
     * @return  entity_factory
     */
    public function get_entity_factory(): entity_factory;

    /**
     * Get the Collection Factory.
     *
     * @return  collection_factory
     */
    public function get_collection_factory(): collection_factory;

    /**
     * Get the Cache Factory.
     *
     * @return  cache_factory
     */
    public function get_cache_factory(): cache_factory;

    /**
     * Get an instance of a filter.
     *
     * @return  filter
     */
    public function get_filter_instance(): filter;
}
