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

use IteratorAggregate;
use Traversable;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\interfaces\endpoint as endpoint_interface;
use enrol_eduapi\local\interfaces\filter as filter_interface;
use stdClass;

/**
 * An Edu-API Collection: a paginated list of entities of a single type.
 *
 * Each page is fetched via the endpoint's execute_paginated_function(), which handles the limit/offset
 * pagination and yields one entity at a time — see classes/local/endpoint.php for why the bare-array
 * response shape of Edu-API v1p0 (as opposed to OneRoster's named-property wrapper) simplifies that
 * loop considerably.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class collection implements IteratorAggregate {
    /** @var container_interface The container to which this collection belongs */
    protected $container;

    /** @var array The params to use with this endpoint */
    protected $params = [];

    /** @var iterable|null The retrieved data */
    protected $data;

    /** @var filter_interface|null The filter to be applied to the collection */
    protected $filter;

    /** @var callable|null A record filter to be applied during the fetch */
    protected $recordfilter;

    /**
     * Create a new instance of a collection.
     *
     * @param   container_interface $container The container to which this collection belongs
     * @param   array $params All of the parameters, including those required as URL substitutions
     * @param   filter_interface|null $filter A filter to apply to the endpoint call
     * @param   callable|null $recordfilter A filter to apply to each entity after retrieval
     */
    public function __construct(
        container_interface $container,
        array $params = [],
        ?filter_interface $filter = null,
        ?callable $recordfilter = null
    ) {
        $this->container = $container;
        $this->filter = $this->process_filter($filter);
        $this->params = $this->process_params($params);
        $this->recordfilter = $recordfilter;
    }

    /**
     * Fetch the data in this collection.
     *
     * @return  Traversable
     */
    public function getIterator(): Traversable { // @codingStandardsIgnoreLine
        return (function () {
            yield from $this->get_data();
        })();
    }

    /**
     * Process the current filter, creating an empty filter if none was specified.
     *
     * @param   filter_interface|null $filter
     * @return  filter_interface
     */
    protected function process_filter(?filter_interface $filter): filter_interface {
        if ($filter) {
            return $filter;
        }

        return $this->container->get_filter_instance();
    }

    /**
     * Process the supplied parameters and modify them as required.
     *
     * @param   array $params
     * @return  array
     */
    protected function process_params(array $params): array {
        return $params;
    }

    /**
     * Return the data for this collection, fetching it if it has not yet been retrieved.
     *
     * @return  iterable
     */
    public function get_data(): iterable {
        if ($this->data === null) {
            $this->refresh_data();
        }

        return $this->data;
    }

    /**
     * Refresh the data for this collection from the Edu-API endpoint.
     *
     * @return  iterable
     */
    public function refresh_data(): iterable {
        $this->data = static::get_endpoint($this->container)->execute_paginated_function(
            static::get_operation_id($this->container),
            $this->get_filter(),
            $this->get_params(),
            function ($data) {
                $entity = static::parse_returned_row($this->container, $data);
                if ($this->recordfilter && !call_user_func($this->recordfilter, $entity)) {
                    return null;
                }

                return $entity;
            }
        );

        return $this->data;
    }

    /**
     * Get the filter to use when fetching this collection.
     *
     * @return  filter_interface|null
     */
    protected function get_filter(): ?filter_interface {
        return $this->filter;
    }

    /**
     * Get the parameters used to fetch this collection.
     *
     * @return  array
     */
    protected function get_params(): array {
        return $this->params;
    }

    /**
     * Get the endpoint that serves this collection's entity type.
     *
     * @param   container_interface $container
     * @return  endpoint_interface
     */
    abstract protected static function get_endpoint(container_interface $container): endpoint_interface;

    /**
     * Get the operation ID for the paginated list of this entity type.
     *
     * @param   container_interface $container
     * @return  string
     */
    abstract protected static function get_operation_id(container_interface $container): string;

    /**
     * Parse a single row of data returned from the Edu-API endpoint into an entity.
     *
     * @param   container_interface $container
     * @param   stdClass $data The raw data for a single item, as returned from the endpoint
     * @return  entity
     */
    abstract protected static function parse_returned_row(container_interface $container, stdClass $data): entity;
}
