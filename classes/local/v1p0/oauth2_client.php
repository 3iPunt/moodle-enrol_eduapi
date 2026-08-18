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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/oauthlib.php');

use coding_exception;
use enrol_eduapi\client_helper;
use enrol_eduapi\local\command;
use enrol_eduapi\local\exceptions\exception as eduapi_exception;
use enrol_eduapi\local\exceptions\unauthorized;
use enrol_eduapi\local\interfaces\client as client_interface;
use enrol_eduapi\local\interfaces\filter as filter_interface;
use moodle_exception;
use moodle_url;
use null_progress_trace;
use oauth2_client as core_oauth2_client;
use progress_trace;
use stdClass;

/**
 * Edu-API v1p0 client using OAuth2 Client Credentials Grant.
 *
 * Edu-API only defines OAuth2 Client Credentials Grant (no OAuth1), so this
 * class combines the OAuth mechanics and the version-specific details
 * (scope, base URL) in a single class, instead of splitting them across an
 * abstract client + a per-oauth-version subclass as enrol_oneroster does to
 * share code between its OAuth1 and OAuth2 implementations. That split has
 * no value here because there is only one authentication method.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class oauth2_client extends core_oauth2_client implements client_interface {

    /** @var string The Edu-API v1p0 core read-only scope. */
    const SCOPE_CORE_READONLY = 'http://purl.1edtech.org/spec/eduapi/v1p0/scope/core.readonly';

    /** @var string The Edu-API provider's API root URL, as configured by the administrator. */
    protected $baseurl;

    /** @var string The URL of the OAuth2 token endpoint. */
    protected $tokenurl;

    /** @var string The OAuth2 client id. */
    protected $clientid;

    /** @var string The secret associated with the client id. */
    protected $clientsecret;

    /** @var progress_trace The log tracer. */
    protected $trace;

    /**
     * Create a new instance of the Edu-API v1p0 client.
     *
     * @param   string $tokenurl The OAuth2 token endpoint
     * @param   string $server The Edu-API provider's API root URL
     * @param   string $clientid The OAuth2 client id
     * @param   string $clientsecret The secret associated with the client id
     */
    public function __construct(string $tokenurl, string $server, string $clientid, string $clientsecret) {
        // Unlike the OneRoster v1.1 specification, which mandates a fixed API root path segment
        // ("/ims/oneroster/v1p1"), Edu-API v1p0's OpenAPI document only gives an ILLUSTRATIVE server URL
        // ("https://example.org/ims/eduapi/base/v1p0", explicitly flagged as "should be changed to the
        // actual server location") and an illustrative token URL ("https://www.example.com/eduapi/ccg/token/")
        // in its CCGSecurity security scheme — neither is a mandated path prefix like OneRoster's. The
        // administrator is expected to configure the provider's full API root (including any vendor-specific
        // path) verbatim in the 'root_url' setting; this class does not append anything to it.
        $this->baseurl = $server;

        $this->tokenurl = $tokenurl;
        $this->clientid = $clientid;
        $this->clientsecret = $clientsecret;

        parent::__construct(
            $clientid,
            $clientsecret,

            // Edu-API's Client Credentials Grant is performed entirely server-to-server, without a browser
            // redirect, so no meaningful return URL exists.
            new moodle_url(''),

            implode(' ', $this->get_all_scopes())
        );
    }

    /**
     * Returns the auth url for OAuth 2.0 Client request.
     *
     * @return  string
     * @throws  coding_exception Edu-API only supports the Client Credentials Grant
     */
    protected function auth_url() {
        throw new coding_exception('Edu-API does not support browser-based OAuth2 authentication.');
    }

    /**
     * Return the OAuth2 token URL.
     *
     * @return  moodle_url
     */
    protected function token_url(): moodle_url {
        return new moodle_url($this->tokenurl);
    }

    /**
     * Get all of the scopes required for this OAuth2 implementation.
     *
     * @return  string[]
     */
    protected function get_all_scopes(): array {
        return [self::SCOPE_CORE_READONLY];
    }

    /**
     * Get the Edu-API provider's API root URL.
     *
     * @return  string
     */
    public function get_base_url(): string {
        return $this->baseurl;
    }

    /**
     * Set the log tracer.
     *
     * @param   progress_trace $trace
     */
    public function set_trace(progress_trace $trace): void {
        $this->trace = $trace;
    }

    /**
     * Get the log tracer.
     *
     * @return  progress_trace
     */
    public function get_trace(): progress_trace {
        if ($this->trace === null) {
            $this->trace = new null_progress_trace();
        }

        return $this->trace;
    }

    /**
     * Authenticate against the Edu-API endpoint using the Client Credentials Grant.
     *
     * @throws  moodle_exception If the token endpoint does not return a usable access token
     */
    public function authenticate(): void {
        $requestedscopes = implode(' ', $this->get_all_scopes());

        $params = [
            'grant_type' => 'client_credentials',
            'scope' => $requestedscopes,
        ];

        // Basic auth is based on a base64-encoded clientid and secret.
        $idsecret = base64_encode(urlencode($this->clientid) . ':' . urlencode($this->clientsecret));
        $this->setHeader("Authorization: Basic {$idsecret}");
        $this->setHeader('Content-Type: application/x-www-form-urlencoded');

        $request = $this->post(
            $this->token_url(),
            $this->build_post_data($params)
        );

        $info = $this->get_request_info();
        if ($info['http_code'] < 200 || $info['http_code'] >= 300) {
            throw new moodle_exception('connectionfailed', 'enrol_eduapi', '', "HTTP {$info['http_code']}");
        }

        $response = json_decode($request);

        if (is_null($response)) {
            throw new moodle_exception('connectionfailed', 'enrol_eduapi', '', 'Could not decode JSON token response');
        }

        if (!empty($response->error)) {
            $description = $response->error_description ?? $response->error;
            throw new moodle_exception('connectionfailed', 'enrol_eduapi', '', $description);
        }

        if (!isset($response->access_token)) {
            throw new moodle_exception('connectionfailed', 'enrol_eduapi', '', 'No access token found in response');
        }

        $this->set_access_token(
            $response->access_token,
            // Expires 10 seconds before actual expiry to avoid using a token that has just expired.
            time() + $response->expires_in - 10,
            property_exists($response, 'scope') ? $response->scope : $requestedscopes
        );
    }

    /**
     * Execute the supplied command against the Edu-API endpoint.
     *
     * Unlike enrol_oneroster's equivalent (which always decodes a JSON object, because OneRoster wraps
     * every collection in a named property), Edu-API v1p0 collection endpoints return a bare JSON array,
     * so `$response` here may be a plain PHP array (list of stdClass entities) as well as a stdClass
     * (a single by-id entity, or the token-style {error, error_description} shape on failure).
     *
     * The access token is refreshed proactively when it has expired (or none has been obtained yet),
     * and reactively - by re-authenticating once and retrying the request a single time - if the
     * provider still responds with 401 Unauthorized (e.g. the token was revoked server-side, or clock
     * skew made our tracked expiry optimistic). A second 401 is not retried again and is rethrown.
     *
     * @param   command $command The command to execute
     * @param   filter_interface|null $filter An optional filter to apply
     * @return  object An object with `info` (cURL request info) and `response` (the decoded JSON body)
     * @throws  \enrol_eduapi\local\exceptions\exception A typed exception matching the HTTP status code
     * @throws  moodle_exception If the response body could not be decoded as JSON
     */
    public function execute(command $command, ?filter_interface $filter = null): object {
        $this->ensure_fresh_access_token();

        try {
            return $this->execute_once($command, $filter);
        } catch (unauthorized $e) {
            $this->authenticate();
            return $this->execute_once($command, $filter);
        }
    }

    /**
     * Authenticate if no access token has been obtained yet, or if the one we hold has expired.
     *
     * This is the proactive half of token-lifecycle handling: it avoids sending a request with a
     * token we already know is stale. The reactive half (re-authenticating on an actual 401 response)
     * lives in {@see execute()}, since expiry can also be triggered server-side before our tracked
     * expiry is reached (e.g. clock skew, provider-side revocation).
     */
    protected function ensure_fresh_access_token(): void {
        if (empty($this->accesstoken) || empty($this->accesstoken->token) || time() >= $this->accesstoken->expires) {
            $this->authenticate();
        }
    }

    /**
     * Perform a single attempt at executing the supplied command against the Edu-API endpoint, with
     * no token-refresh or retry logic of its own.
     *
     * @param   command $command The command to execute
     * @param   filter_interface|null $filter An optional filter to apply
     * @return  object An object with `info` (cURL request info) and `response` (the decoded JSON body)
     * @throws  \enrol_eduapi\local\exceptions\exception A typed exception matching the HTTP status code
     * @throws  moodle_exception If the response body could not be decoded as JSON
     */
    protected function execute_once(command $command, ?filter_interface $filter = null): object {
        $url = new moodle_url($command->get_url($this->baseurl));
        $params = $command->get_params();
        $method = $command->get_method();
        $sort = $command->get_sort();

        if (array_key_exists('limit', $params)) {
            $url->param('limit', $params['limit']);
            unset($params['limit']);
        }

        if (array_key_exists('offset', $params)) {
            $url->param('offset', $params['offset']);
            unset($params['offset']);
        }

        if ($sort) {
            $url->param('sort', $sort);
            if ($sortorder = $command->get_sort_order()) {
                $url->param('orderBy', $sortorder);
            }
        }

        if ($filter && !empty((string) $filter)) {
            $url->param('filter', (string) $filter);
        }

        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 300,
        ];

        $this->setHeader("Authorization: Bearer {$this->get_access_token()->token}");

        if ($method === client_helper::POST) {
            $result = $this->post($url->out(false), $params, $options);
        } else {
            $result = $this->get($url->out(false), $params, $options);
        }

        $info = $this->get_request_info();
        eduapi_exception::check_and_throw_from_http_response($result, $info, $url);

        $response = json_decode($result);
        if ($response === null && trim($result) !== 'null') {
            throw new moodle_exception('connectionfailed', 'enrol_eduapi', '', 'Could not decode JSON response: ' . $result);
        }

        // The token-error shape ({error, error_description}) can only appear on a stdClass response; a
        // successful collection response is a plain array and has no such property to check.
        if (is_object($response) && !empty($response->error)) {
            $description = $response->error_description ?? $response->error;
            throw new moodle_exception('connectionfailed', 'enrol_eduapi', '', $description);
        }

        return (object) [
            'info' => $info,
            'response' => $response,
        ];
    }

    /**
     * Set the access token to use.
     *
     * @param   string $token
     * @param   int $expiry
     * @param   string $scope
     */
    public function set_access_token(string $token, int $expiry, string $scope): void {
        $this->accesstoken = (object) [
            'token' => $token,
            'expires' => $expiry,
            'scope' => $scope,
        ];
    }

    /**
     * Fetch the access token data.
     *
     * @return  stdClass
     */
    public function get_access_token(): stdClass {
        return $this->accesstoken;
    }

    /**
     * Get the cURL request info from the last request.
     *
     * @return  array
     */
    public function get_request_info(): array {
        return $this->info;
    }
}
