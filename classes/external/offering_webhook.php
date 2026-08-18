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

namespace enrol_eduapi\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use enrol_eduapi\client_helper;
use enrol_eduapi\json_progress_trace;
use enrol_eduapi\local\converters\offering_converter;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\container;
use enrol_eduapi\local\v1p0\eduapi_client;
use enrol_eduapi\local\v1p0\entities\education_offering;
use moodle_exception;
use Throwable;

/**
 * Edu-API offering webhook: "sync this offering now".
 *
 * Réplica exacta del patrón de enrol_oneroster\external\class_webhook (resolved in spec.md, Q2): a
 * standard Moodle external function, not a payload receiver — the provider does not push data here.
 * It receives an offering's sourcedId (and, optionally, an organization sourcedId to validate the
 * offering against) and triggers a synchronise() filtered to that single offering, reusing the
 * development phase 5 orchestrator (eduapi_client).
 *
 * Unlike enrol_oneroster's class_webhook (which calls neither validate_parameters(),
 * validate_context(), nor require_capability() — a real gap relative to Moodle's own web service
 * security guidance), this implementation follows the full external function security checklist:
 * validate_parameters() first, then validate_context() + require_capability() before touching
 * anything.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class offering_webhook extends external_api {
    /**
     * Parameter definition for execute().
     *
     * @return  external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sourcedId' => new external_value(
                PARAM_TEXT,
                'Edu-API offering sourcedId (a CourseOffering or ComponentOffering, per the configured offering_level)',
                VALUE_REQUIRED
            ),
            'organization' => new external_value(
                PARAM_TEXT,
                'Optional Edu-API organization sourcedId. If given, the offering is only synced when ' .
                    'it belongs to this organization.',
                VALUE_DEFAULT,
                null
            ),
        ]);
    }

    /**
     * Sync a single Edu-API offering now.
     *
     * @param   string $sourcedid The offering's sourcedId
     * @param   string|null $organization An optional organization sourcedId to validate the offering
     *                                    against
     * @return  array ['result' => bool, 'warnings' => array, 'messages' => string[]]
     */
    public static function execute(string $sourcedid, ?string $organization = null): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'sourcedId' => $sourcedid,
            'organization' => $organization,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $result = true;
        $warnings = [];
        $trace = new json_progress_trace();

        try {
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

            if (!empty($params['organization'])) {
                $offering = self::find_offering($container, $params['sourcedId']);
                if ($offering === null) {
                    throw new moodle_exception('offeringnotfound', 'enrol_eduapi', '', $params['sourcedId']);
                }
                if ($offering->get_organization_id() !== $params['organization']) {
                    throw new moodle_exception('offeringorganizationmismatch', 'enrol_eduapi', '', $params['sourcedId']);
                }
            }

            $eduapiclient = new eduapi_client($container);
            $eduapiclient->set_trace($trace);
            $eduapiclient->synchronise(['offering_sourcedid' => $params['sourcedId']]);
        } catch (Throwable $e) {
            $result = false;
            $warnings[] = [
                'warningcode' => (string) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return [
            'result' => $result,
            'warnings' => $warnings,
            'messages' => $trace->get_data(),
        ];
    }

    /**
     * Fetch the offering entity matching the configured `offering_level`, for the organization check.
     *
     * @param   container_interface $container
     * @param   string $sourcedid
     * @return  education_offering|null null if the offering does not exist
     */
    protected static function find_offering(container_interface $container, string $sourcedid): ?education_offering {
        try {
            if (offering_converter::get_configured_offering_level() === offering_converter::LEVEL_COURSE_OFFERING) {
                $offering = $container->get_entity_factory()->fetch_course_offering_by_id($sourcedid);
            } else {
                $offering = $container->get_entity_factory()->fetch_component_offering_by_id($sourcedid);
            }
            $offering->get_data();

            return $offering;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Return definition for execute().
     *
     * @return  external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'The processing result'),
            'warnings' => new external_warnings(),
            'messages' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'A progress trace message'),
                'List of progress trace messages',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
