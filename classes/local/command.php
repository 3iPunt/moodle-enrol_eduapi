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
use OutOfRangeException;

/**
 * An Edu-API Command to pass to the Endpoint.
 *
 * Unlike enrol_oneroster's command, which carries a list of possible wrapper property names to look
 * for in the response (`'collection' => ['orgs']`), Edu-API v1p0 collection responses are bare JSON
 * arrays with no wrapper property, so `$iscollection` here is a plain boolean: it only tells the caller
 * whether to expect (and paginate) a list, not where to find it in the payload.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class command {
    /** @var endpoint The endpoint that this command relates to */
    protected $endpoint;

    /** @var string The URL for this method */
    protected $url;

    /** @var string The Method to be called */
    protected $method;

    /** @var string The description of the command */
    protected $description;

    /** @var bool Whether this command returns a bare-array collection */
    protected $iscollection;

    /** @var array The list of parameters to use */
    protected $params;

    /** @var string|null The sort to apply */
    protected $sort;

    /** @var string|null The sort order to apply */
    protected $sortorder;

    /**
     * Create a new Command.
     *
     * @param   endpoint $endpoint The endpoint that this command relates to
     * @param   string $url The URL for this method
     * @param   string $method The Method to be called
     * @param   string $description The description of the command
     * @param   bool $iscollection Whether this command returns a bare-array collection
     * @param   string|null $defaultsort The default sort column to apply
     * @param   string|null $defaultsortorder The direction of the default sort order
     * @param   array $params The list of parameters to use
     */
    public function __construct(
        endpoint $endpoint,
        string $url,
        string $method,
        string $description,
        bool $iscollection,
        ?string $defaultsort,
        ?string $defaultsortorder,
        array $params
    ) {
        foreach ($params as $name => $value) {
            if ($name[0] === ':') {
                if (strpos($url, $name) === false) {
                    throw new OutOfRangeException("Parameter not found in URL '{$name}'");
                }
                // Encode the substituted value: an unencoded sourcedId containing / ? # & (all valid in
                // Edu-API sourcedIds, which are opaque strings) would otherwise corrupt the resulting
                // request path (e.g. splitting it into extra segments, or introducing a query string /
                // fragment where none was intended).
                $url = str_replace($name, rawurlencode($value), $url);
                unset($params[$name]);
            }
        }

        if (strpos($url, '/:') !== false) {
            throw new OutOfRangeException("URL contains untranslated parameters '{$url}'");
        }

        if (array_key_exists('sort', $params)) {
            $this->sort = $params['sort'];
            unset($params['sort']);
        } else if ($defaultsort) {
            $this->sort = $defaultsort;
        }

        if (array_key_exists('orderBy', $params)) {
            $this->sortorder = $params['orderBy'];
            unset($params['orderBy']);
        } else if ($defaultsortorder) {
            $this->sortorder = $defaultsortorder;
        }

        $this->endpoint = $endpoint;
        $this->url = $url;
        $this->method = $method;
        $this->description = $description;
        $this->iscollection = $iscollection;
        $this->params = $params;
    }

    /**
     * Get the final URL to call given the specified base URL.
     *
     * @param   string $baseurl The root URL of the endpoint
     * @return  string
     */
    public function get_url(string $baseurl): string {
        return $this->endpoint->get_url_for_command($baseurl, $this->url);
    }

    /**
     * Get the method to use.
     *
     * @return  string
     */
    public function get_method(): string {
        return $this->method;
    }

    /**
     * Get the description of the command.
     *
     * @return  string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Get the final list of parameters.
     *
     * @return  array
     */
    public function get_params(): array {
        return $this->params;
    }

    /**
     * Get the sort string to apply to this command.
     *
     * @return  string|null
     */
    public function get_sort(): ?string {
        return $this->sort;
    }

    /**
     * Get the sort order to apply to this command.
     *
     * @return  string|null
     */
    public function get_sort_order(): ?string {
        return $this->sortorder;
    }

    /**
     * Whether this command returns a bare-array collection.
     *
     * @return  bool
     */
    public function is_collection(): bool {
        return $this->iscollection;
    }

    /**
     * Require that this command returns a collection.
     *
     * @throws  BadMethodCallException
     */
    public function require_collection(): void {
        if (!$this->is_collection()) {
            throw new BadMethodCallException("The '{$this->method}' method does not return a collection");
        }
    }
}
