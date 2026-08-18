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
 * Edu-API Client.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_eduapi;

use enrol_eduapi\local\interfaces\client as client_interface;
use InvalidArgumentException;

/**
 * Edu-API Client factory.
 *
 * Unlike enrol_oneroster::client_helper::get_client(), this factory does not take an
 * 'oauth_version' parameter: Edu-API only defines OAuth2 Client Credentials Grant, it does not
 * define OAuth1. The 'version' parameter is kept (mirroring the 'classes/local/v1p0/' subtree
 * decision) so that a future Edu-API spec version can be added as 'v1p1' etc. without changing
 * this contract.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client_helper {
    /** @var string Edu-API Version 1p0 (Candidate Final Public). */
    const VERSION_V1P0 = 'v1p0';

    /** @var string A GET request. */
    const GET = 'GET';

    /** @var string A POST request. */
    const POST = 'POST';

    /**
     * Get an instance of the Edu-API client.
     *
     * @param   string $version The Edu-API spec version to use
     * @param   string $tokenurl The OAuth2 token endpoint
     * @param   string $server The Edu-API provider's API root URL
     * @param   string $clientid The OAuth2 client id
     * @param   string $clientsecret The secret associated with the client id
     * @return  client_interface
     * @throws  InvalidArgumentException If the requested version is not implemented
     */
    public static function get_client(
        string $version,
        string $tokenurl,
        string $server,
        string $clientid,
        string $clientsecret
    ): client_interface {
        $classname = "\\enrol_eduapi\\local\\{$version}\\oauth2_client";
        if (!class_exists($classname)) {
            throw new InvalidArgumentException("Unknown Edu-API version '{$version}' ({$classname})");
        }

        return new $classname($tokenurl, $server, $clientid, $clientsecret);
    }
}
