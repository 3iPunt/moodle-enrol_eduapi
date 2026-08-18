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
use progress_trace;

/**
 * Edu-API Client interface.
 *
 * Authentication landed in development phase 1; execute() (used by the
 * endpoint layer to perform an authenticated HTTP call) lands in phase 2.
 * synchronise() will be added once the full sync orchestrator lands in
 * phase 5 — see plan.md.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface client {

    /**
     * Set the log tracer.
     *
     * @param   progress_trace $trace
     */
    public function set_trace(progress_trace $trace): void;

    /**
     * Get the log tracer.
     *
     * @return  progress_trace
     */
    public function get_trace(): progress_trace;

    /**
     * Authenticate against the Edu-API endpoint.
     *
     * @throws  \moodle_exception If the OAuth2 Client Credentials Grant fails
     */
    public function authenticate(): void;

    /**
     * Execute the supplied command against the Edu-API endpoint.
     *
     * @param   command $command The command to execute
     * @param   filter|null $filter An optional filter to apply
     * @return  object An object with `info` (the cURL request info) and `response` (the decoded JSON body,
     *                 either a stdClass for a by-id lookup or an array of stdClass for a collection, per
     *                 Edu-API v1p0's bare-array collection response shape)
     */
    public function execute(command $command, ?filter $filter = null): object;
}
