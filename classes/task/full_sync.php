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

namespace enrol_eduapi\task;

use enrol_eduapi\client_helper;
use enrol_eduapi\local\v1p0\container;
use enrol_eduapi\local\v1p0\eduapi_client;
use text_progress_trace;

/**
 * Edu-API full synchronisation scheduled task.
 *
 * Replicates enrol_oneroster\task\full_sync: authenticate, then delegate the whole sync to the
 * orchestrator (eduapi_client here, rather than the client itself — see that class's docblock for why
 * enrol_eduapi splits the connection concern (oauth2_client) from the sync orchestration concern
 * (eduapi_client), unlike enrol_oneroster which combines both in one client class per OAuth version).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class full_sync extends \core\task\scheduled_task {

    /**
     * Get the name of this task, for the scheduled tasks admin UI.
     *
     * @return  string
     */
    public function get_name() {
        return get_string('fullsync', 'enrol_eduapi');
    }

    /**
     * Run the full Edu-API sync.
     */
    public function execute() {
        $trace = new text_progress_trace();

        if (!enrol_is_enabled('eduapi')) {
            $trace->output('Edu-API enrolment method is not enabled');
            return;
        }

        $config = get_config('enrol_eduapi');

        $client = client_helper::get_client(
            client_helper::VERSION_V1P0,
            $config->token_url,
            $config->root_url,
            $config->clientid,
            $config->secret
        );

        $client->set_trace($trace);
        $client->authenticate();

        $container = new container($client);
        $eduapiclient = new eduapi_client($container);
        $eduapiclient->set_trace($trace);
        $eduapiclient->synchronise();
    }
}
