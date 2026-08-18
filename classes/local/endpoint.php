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

use BadMethodCallException;
use coding_exception;
use enrol_eduapi\local\interfaces\client as client_interface;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\interfaces\endpoint as endpoint_interface;
use enrol_eduapi\local\interfaces\filter as filter_interface;

/**
 * An Edu-API Endpoint: declares the commands (URLs) for one service group and executes them.
 *
 * Unlike enrol_oneroster's single monolithic "rostering" endpoint (which handles every OneRoster
 * operation because OneRoster collapses them all into one service), Edu-API v1p0 groups its
 * operations into several service interfaces (OrganizationManagement, PersonManagement,
 * EducationManagement, ...), so classes/local/v1p0/endpoints/ has one concrete subclass of this class
 * per group, each declaring only the commands for its own group in its own `$commands` array.
 *
 * The pagination logic in execute_paginated_function() reads the decoded response body directly as a
 * PHP array (Edu-API v1p0 collection responses are bare JSON arrays), rather than searching for a
 * named wrapper property as enrol_oneroster's equivalent does.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class endpoint implements endpoint_interface {

    /** @var container_interface The container containing the client and all factories */
    protected $container;

    /** @var array List of commands and their configuration, keyed by operation id */
    protected static $commands = [];

    /**
     * Constructor for the endpoint.
     *
     * @param   container_interface $container
     */
    final public function __construct(container_interface $container) {
        $this->container = $container;
    }

    /**
     * Get the client related to this endpoint.
     *
     * @return  client_interface
     */
    final protected function get_client(): client_interface {
        return $this->container->get_client();
    }

    /**
     * Execute the supplied method and return a single entity's data.
     *
     * @param   string $method
     * @param   filter_interface|null $filter
     * @param   array $params
     * @return  mixed
     */
    public function execute(string $method, ?filter_interface $filter = null, array $params = []) {
        return $this->get_client()->execute($this->get_http_method($method, $params), $filter)->response;
    }

    /**
     * Execute the supplied command.
     *
     * @param   command $command
     * @param   filter_interface|null $filter
     * @return  mixed
     */
    public function execute_command(command $command, ?filter_interface $filter = null) {
        return $this->get_client()->execute($command, $filter)->response;
    }

    /**
     * Execute a method which returns a paginated, bare-array collection.
     *
     * @param   string $method
     * @param   filter_interface|null $filter
     * @param   array $params
     * @param   callable $callback Invoked once per raw item; a non-null return value is yielded
     * @return  iterable
     */
    public function execute_paginated_function(
        string $method,
        ?filter_interface $filter,
        array $params,
        callable $callback
    ): iterable {
        if (!array_key_exists('offset', $params)) {
            $params['offset'] = 0;
        }

        if (!array_key_exists('limit', $params)) {
            $pagesize = get_config('enrol_eduapi', 'pagesize');
            $params['limit'] = $pagesize ? (int) $pagesize : 100;
        }

        $command = $this->get_http_method($method, $params);
        $command->require_collection();

        do {
            $result = $this->get_client()->execute($command, $filter);
            $response = $result->response;

            if (!is_array($response)) {
                throw new coding_exception(
                    "Expected a JSON array response for a collection endpoint, got " . gettype($response)
                );
            }

            foreach ($response as $item) {
                if ($parsed = $callback($item)) {
                    yield $parsed;
                }
            }

            $receivedcount = count($response);
            $params['offset'] += $params['limit'];

            $morepages = $receivedcount >= $params['limit'];
            if ($morepages) {
                $command = $this->get_http_method($method, $params);
            }
        } while ($morepages);
    }

    /**
     * Get the command details for the specified method.
     *
     * @param   string $method The name of the Edu-API operation
     * @param   array $params The params to apply
     * @return  command
     */
    protected function get_http_method(string $method, array $params): command {
        $commanddata = static::get_command_data($method);

        return new command(
            $this,
            $commanddata['url'],
            $commanddata['method'],
            $commanddata['description'],
            $commanddata['collection'] ?? false,
            $commanddata['defaultsort'] ?? null,
            $commanddata['defaultsortorder'] ?? null,
            $params
        );
    }

    /**
     * Get the command data for the specified operation id.
     *
     * @param   string $command
     * @return  array
     */
    protected static function get_command_data(string $command): array {
        if (array_key_exists($command, static::$commands)) {
            return static::$commands[$command];
        }

        throw new BadMethodCallException("Unknown operation id '{$command}'");
    }

    /**
     * Get the list of all commands declared by this endpoint.
     *
     * @return  array
     */
    public static function get_all_commands(): array {
        return static::$commands;
    }

    /**
     * Get the URL for the specified command, relative to the configured API root.
     *
     * @param   string $baseurl
     * @param   string $endpoint
     * @return  string
     */
    public function get_url_for_command(string $baseurl, string $endpoint): string {
        return "{$baseurl}{$endpoint}";
    }
}
