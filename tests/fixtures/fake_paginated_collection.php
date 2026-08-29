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

namespace enrol_eduapi\tests\fixtures;

use Iterator;
use IteratorAggregate;

/**
 * A fake, IteratorAggregate-backed collection whose Iterator can be told to throw when advancing to a
 * given position.
 *
 * Simulates a paginated Edu-API collection whose NEXT-page HTTP fetch fails partway through - the
 * exact failure mode eduapi_client::iterate_safely() exists to survive. Used only by
 * tests/local/v1p0/eduapi_client_test.php.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_paginated_collection implements IteratorAggregate {
    /** @var array The items to yield */
    protected $items;

    /** @var int|null The 0-based position at which to throw while advancing, or null to never throw */
    protected $failatposition;

    /**
     * Create a new fake paginated collection.
     *
     * @param   array $items The items to yield
     * @param   int|null $failatposition Throw when the iterator advances to this position (0-based;
     *                                   0 throws immediately, before any item is yielded), or never if
     *                                   null
     */
    public function __construct(array $items, ?int $failatposition = null) {
        $this->items = $items;
        $this->failatposition = $failatposition;
    }

    /**
     * Get an Iterator that yields this collection's items, throwing while advancing past
     * $failatposition (if set).
     *
     * @return  Iterator
     */
    public function getIterator(): Iterator { // @codingStandardsIgnoreLine
        return new fake_paginated_iterator($this->items, $this->failatposition);
    }
}
