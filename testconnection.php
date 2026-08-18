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
 * Test that the Edu-API endpoint is configured correctly, and populate the organization/academic
 * session choices used by the `datasync_organizations`/`datasync_academic_session` settings.
 *
 * Now that settings.php (development phase 4) registers this page as an admin_externalpage,
 * admin_externalpage_setup() replaces the manual require_login()/require_capability() pair used
 * during phase 1 (before that registration existed) — its default required capability is the same
 * `moodle/site:config` declared for this page in settings.php.
 *
 * @package   enrol_eduapi
 * @copyright 2026 3iPunt (contacte@tresipunt.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('enrol_eduapi/testconnection');

$returnurl = new moodle_url('/admin/settings.php', [
    'section' => 'enrolsettingseduapi',
]);

$eduapiconfig = get_config('enrol_eduapi');

$requiredfields = [
    'token_url',
    'root_url',
    'clientid',
    'secret',
];

foreach ($requiredfields as $field) {
    if (empty($eduapiconfig->{$field})) {
        redirect(
            $returnurl,
            get_string('missingrequiredconfig', 'enrol_eduapi', $field),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

$client = enrol_eduapi\client_helper::get_client(
    enrol_eduapi\client_helper::VERSION_V1P0,
    $eduapiconfig->token_url,
    $eduapiconfig->root_url,
    $eduapiconfig->clientid,
    $eduapiconfig->secret
);

try {
    $client->authenticate();
} catch (Exception $e) {
    redirect(
        $returnurl,
        get_string('connectionfailed', 'enrol_eduapi', $e->getMessage()),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Populate the organization / academic session choices used by the datasync_organizations and
// datasync_academic_session settings, the same way enrol_oneroster's testconnection.php populates
// its availableschools/academic_sessions config values.
try {
    $container = new enrol_eduapi\local\v1p0\container($client);

    $foundorganizations = [];
    foreach ($container->get_collection_factory()->get_organizations() as $organization) {
        $names = $organization->get('name') ?? [];
        $label = $names[0]->value ?? $organization->get_id();
        $foundorganizations[$organization->get_id()] = $label;
    }
    set_config('availableorganizations', json_encode($foundorganizations), 'enrol_eduapi');

    // Mirrors enrol_oneroster's testconnection.php, which only offers 'semester'/'schoolYear' terms
    // for its single-session selector.
    $foundsessions = [];
    foreach ($container->get_collection_factory()->get_academic_sessions() as $session) {
        if (!in_array($session->get('sessionType'), ['semester', 'schoolYear'], true)) {
            continue;
        }
        $titles = $session->get('title') ?? [];
        $label = $titles[0]->value ?? $session->get_id();
        $foundsessions[$session->get_id()] = $label;
    }
    set_config('availableacademicsessions', json_encode($foundsessions), 'enrol_eduapi');
} catch (Exception $e) {
    redirect(
        $returnurl,
        get_string('connectionpartial', 'enrol_eduapi', $e->getMessage()),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

redirect(
    $returnurl,
    get_string('configurationcorrect', 'enrol_eduapi'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
