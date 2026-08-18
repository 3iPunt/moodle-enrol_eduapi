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

namespace enrol_eduapi\local;

use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\interfaces\endpoint as endpoint_interface;
use enrol_eduapi\local\interfaces\filter as filter_interface;
use stdClass;

/**
 * An Edu-API Entity: a single by-id resource (Organization, Person, ...).
 *
 * This is a pure data-access wrapper: it fetches and exposes the raw Edu-API fields via get(). Mapping
 * that data onto Moodle objects (categories, courses, users, enrolments) is deliberately NOT done here
 * — that lives in classes/local/converters/ (development phase 3), keeping this layer reusable and
 * testable independently of any Moodle-side decision.
 *
 * Unlike enrol_oneroster's entity (which declares an abstract get_generic_operation_id() to support
 * organisation subtypes such as school vs district), Edu-API's Organization has a free-text
 * `organizationType` rather than an enumerated subtype, so there is no equivalent dispatch to support.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entity {

    /** @var container_interface The container for this Edu-API client */
    protected $container;

    /** @var string The sourcedId of the entity */
    protected $id;

    /** @var stdClass|null The retrieved data */
    protected $data;

    /**
     * Create a new instance of an entity.
     *
     * @param   container_interface $container The container for this Edu-API client
     * @param   string $id The sourcedId of the entity
     * @param   stdClass|null $data The data to pre-seed the entity with if it is already known
     */
    public function __construct(container_interface $container, string $id, ?stdClass $data = null) {
        $this->container = $container;
        $this->id = $id;

        if ($data !== null) {
            $this->data = $data;
        }
    }

    /**
     * Return the data for this entity, fetching it if it has not yet been retrieved.
     *
     * @return  stdClass
     */
    public function get_data(): stdClass {
        if ($this->data === null) {
            $this->refresh_data();
        }

        return $this->data;
    }

    /**
     * Refresh the data for this entity from the Edu-API endpoint.
     *
     * @return  stdClass
     */
    public function refresh_data(): stdClass {
        $this->data = static::fetch_data(
            $this->container,
            [':id' => $this->id]
        );

        return $this->data;
    }

    /**
     * Fetch the data for an entity of this type.
     *
     * @param   container_interface $container
     * @param   array $params The search criterion (typically [':id' => $sourcedid])
     * @param   filter_interface|null $filter Any additional filter to provide
     * @return  stdClass
     */
    public static function fetch_data(container_interface $container, array $params, ?filter_interface $filter = null): stdClass {
        $data = static::get_endpoint($container)->execute(static::get_operation_id($container), $filter, $params);

        return static::parse_returned_row($container, $data);
    }

    /**
     * Fetch an arbitrary field from the entity's raw data.
     *
     * @param   string $name The name of the field to fetch
     * @return  mixed The value of that field, or null if it is not present
     */
    public function get(string $name) {
        $data = $this->get_data();

        return property_exists($data, $name) ? $data->{$name} : null;
    }

    /**
     * Get the sourcedId of this entity, without triggering a fetch.
     *
     * @return  string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the endpoint that serves this entity type.
     *
     * @param   container_interface $container
     * @return  endpoint_interface
     */
    abstract protected static function get_endpoint(container_interface $container): endpoint_interface;

    /**
     * Get the operation ID for the by-id lookup of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    abstract protected static function get_operation_id(container_interface $container): string;

    /**
     * Parse the data returned from the Edu-API endpoint.
     *
     * The default implementation returns the data unchanged: Edu-API v1p0 by-id endpoints return the
     * bare entity object directly (unlike OneRoster, which wraps every response in a named property
     * such as `{org: {...}}`), so no unwrapping is needed. Override only if a future version changes
     * this.
     *
     * @param   container_interface $container
     * @param   stdClass $data The raw data returned from the endpoint
     * @return  stdClass
     */
    protected static function parse_returned_row(container_interface $container, stdClass $data): stdClass {
        return $data;
    }
}
