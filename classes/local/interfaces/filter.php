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

/**
 * An Edu-API Filter.
 *
 * Edu-API v1p0 defines a single free-form `filter` query parameter whose exact grammar is
 * implementation-defined by the provider (per the OpenAPI document's parameter description). This
 * contract mirrors enrol_oneroster's filter abstraction so a concrete predicate builder can be added
 * once a real provider's grammar is known, without changing the container/collection/entity contracts.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface filter {
    /** @var string The join operator for AND */
    const AND = 'AND';

    /** @var string The join operator for OR */
    const OR = 'OR';

    /**
     * Constructor for a new filter.
     *
     * @param   string|null $field The field to filter on
     * @param   string|null $value The value to filter on
     * @param   string $predicate The filter search type
     */
    public function __construct(?string $field = null, ?string $value = null, string $predicate = '=');

    /**
     * Set the join operator when dealing with multiple filters.
     *
     * @param   string $logicaloperator A valid logical join operator, such as AND, or OR.
     * @return  self
     */
    public function set_operator(string $logicaloperator): self;

    /**
     * Add a filter.
     *
     * @param   string $field The field to filter on
     * @param   string $value The value to filter on
     * @param   string $predicate The filter search type
     * @return  self
     */
    public function add_filter(string $field, string $value, string $predicate = '='): self;
}
