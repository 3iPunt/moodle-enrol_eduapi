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

namespace enrol_eduapi\local\v1p0;

use coding_exception;
use enrol_eduapi\local\factories\cache_factory;
use enrol_eduapi\local\factories\collection_factory;
use enrol_eduapi\local\factories\entity_factory;
use enrol_eduapi\local\filter;
use enrol_eduapi\local\interfaces\cache_factory as cache_factory_interface;
use enrol_eduapi\local\interfaces\client as client_interface;
use enrol_eduapi\local\interfaces\collection_factory as collection_factory_interface;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\interfaces\endpoint as endpoint_interface;
use enrol_eduapi\local\interfaces\entity_factory as entity_factory_interface;
use enrol_eduapi\local\interfaces\filter as filter_interface;
use enrol_eduapi\local\v1p0\endpoints\academic_session_management;
use enrol_eduapi\local\v1p0\endpoints\affiliation_management;
use enrol_eduapi\local\v1p0\endpoints\education_management;
use enrol_eduapi\local\v1p0\endpoints\enrollment_management;
use enrol_eduapi\local\v1p0\endpoints\organization_management;
use enrol_eduapi\local\v1p0\endpoints\person_management;

/**
 * Edu-API v1p0 Container: wires together the client, the factories, and the 6 service endpoints.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class container implements container_interface {
    /** @var array Maps a service group name to its concrete endpoint class */
    const ENDPOINT_CLASSES = [
        'organization' => organization_management::class,
        'academic_session' => academic_session_management::class,
        'person' => person_management::class,
        'education' => education_management::class,
        'enrollment' => enrollment_management::class,
        'affiliation' => affiliation_management::class,
    ];

    /** @var client_interface The client instance */
    protected $client;

    /** @var endpoint_interface[] Endpoint instances already created, keyed by service group name */
    protected $endpoints = [];

    /** @var entity_factory_interface|null */
    protected $entityfactory;

    /** @var collection_factory_interface|null */
    protected $collectionfactory;

    /** @var cache_factory_interface|null */
    protected $cachefactory;

    /**
     * Constructor for the new container.
     *
     * @param   client_interface $client
     */
    public function __construct(client_interface $client) {
        $this->client = $client;
    }

    /**
     * Get the client instance.
     *
     * @return  client_interface
     */
    public function get_client(): client_interface {
        return $this->client;
    }

    /**
     * Get the endpoint for the named service group.
     *
     * @param   string $name One of the keys of self::ENDPOINT_CLASSES
     * @return  endpoint_interface
     * @throws  coding_exception If the service group name is not known
     */
    public function get_endpoint(string $name): endpoint_interface {
        if (!array_key_exists($name, self::ENDPOINT_CLASSES)) {
            throw new coding_exception("Unknown Edu-API service group '{$name}'");
        }

        if (!isset($this->endpoints[$name])) {
            $classname = self::ENDPOINT_CLASSES[$name];
            $this->endpoints[$name] = new $classname($this);
        }

        return $this->endpoints[$name];
    }

    /**
     * Get the Entity Factory.
     *
     * @return  entity_factory_interface
     */
    public function get_entity_factory(): entity_factory_interface {
        if ($this->entityfactory === null) {
            $this->entityfactory = new entity_factory($this);
        }

        return $this->entityfactory;
    }

    /**
     * Get the Collection Factory.
     *
     * @return  collection_factory_interface
     */
    public function get_collection_factory(): collection_factory_interface {
        if ($this->collectionfactory === null) {
            $this->collectionfactory = new collection_factory($this);
        }

        return $this->collectionfactory;
    }

    /**
     * Get the Cache Factory.
     *
     * @return  cache_factory_interface
     */
    public function get_cache_factory(): cache_factory_interface {
        if ($this->cachefactory === null) {
            $this->cachefactory = new cache_factory($this);
        }

        return $this->cachefactory;
    }

    /**
     * Get an instance of a filter.
     *
     * @return  filter_interface
     */
    public function get_filter_instance(): filter_interface {
        return new filter();
    }
}
