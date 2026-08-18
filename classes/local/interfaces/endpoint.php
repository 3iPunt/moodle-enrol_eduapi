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

use enrol_eduapi\local\command;

/**
 * An Edu-API Endpoint.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface endpoint {

    /**
     * Create the endpoint.
     *
     * @param   container $container
     */
    public function __construct(container $container);

    /**
     * Execute the supplied method and return a single entity's data.
     *
     * @param   string $method
     * @param   filter|null $filter
     * @param   array $params
     */
    public function execute(string $method, ?filter $filter = null, array $params = []);

    /**
     * Execute the supplied command.
     *
     * @param   command $command
     * @param   filter|null $filter
     */
    public function execute_command(command $command, ?filter $filter = null);

    /**
     * Execute a method which returns a paginated collection.
     *
     * @param   string $method
     * @param   filter|null $filter
     * @param   array $params
     * @param   callable $callback
     * @return  iterable
     */
    public function execute_paginated_function(
        string $method,
        ?filter $filter,
        array $params,
        callable $callback
    ): iterable;
}
