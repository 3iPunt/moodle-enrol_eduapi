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
use RuntimeException;

/**
 * The Iterator returned by fake_paginated_collection::getIterator().
 *
 * Used only by tests/local/v1p0/eduapi_client_test.php, via fake_paginated_collection.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_paginated_iterator implements Iterator {
    /** @var array The items to yield, reindexed from 0 */
    protected $items;

    /** @var int|null The 0-based position at which to throw while advancing, or null to never throw */
    protected $failatposition;

    /** @var int The current position */
    protected $position = 0;

    /**
     * Create a new fake paginated iterator.
     *
     * @param   array $items The items to yield
     * @param   int|null $failatposition Throw when the iterator advances to this position (0-based),
     *                                   or never if null
     */
    public function __construct(array $items, ?int $failatposition) {
        $this->items = array_values($items);
        $this->failatposition = $failatposition;
    }

    /**
     * Get the item at the current position.
     *
     * @return  mixed
     */
    public function current(): mixed { // @codingStandardsIgnoreLine
        return $this->items[$this->position];
    }

    /**
     * Get the current position.
     *
     * @return  mixed
     */
    public function key(): mixed { // @codingStandardsIgnoreLine
        return $this->position;
    }

    /**
     * Advance to the next position, throwing if this position is the configured failure point.
     *
     * @throws  RuntimeException If this is the configured failure position
     */
    public function next(): void {
        $this->position++;
        if ($this->failatposition !== null && $this->position === $this->failatposition) {
            throw new RuntimeException("Simulated page-fetch failure advancing to position {$this->position}");
        }
    }

    /**
     * Reset to the first position.
     */
    public function rewind(): void {
        $this->position = 0;
    }

    /**
     * Whether the current position holds an item.
     *
     * @return  bool
     */
    public function valid(): bool {
        return $this->position < count($this->items);
    }
}
