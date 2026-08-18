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

use enrol_eduapi\tests\fixtures\fake_paginated_collection;
use IteratorAggregate;
use progress_trace;
use ReflectionClass;
use RuntimeException;

/**
 * Tests for eduapi_client::iterate_safely(): the fault-tolerant iteration helper introduced to fix a
 * "single transient failure aborts the whole sync" defect. A plain foreach over a paginated (Generator
 * backed) collection cannot catch a failure raised while ADVANCING the iterator (i.e. fetching the
 * next page), only failures inside the loop body - iterate_safely() exists to catch both.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\v1p0\eduapi_client
 */
final class eduapi_client_test extends \advanced_testcase {
    /**
     * Build a fake_paginated_collection fixture.
     *
     * @param   array $items The items to yield
     * @param   int|null $failatposition Throw when the iterator advances to this position (0-based),
     *                                   or never if null
     * @return  fake_paginated_collection
     */
    protected function make_collection(array $items, ?int $failatposition = null): fake_paginated_collection {
        require_once(__DIR__ . '/../../fixtures/fake_paginated_collection.php');
        require_once(__DIR__ . '/../../fixtures/fake_paginated_iterator.php');

        return new fake_paginated_collection($items, $failatposition);
    }

    /**
     * Invoke the protected iterate_safely() method via reflection, on an eduapi_client instance built
     * without running its constructor (iterate_safely() never touches $this->container).
     *
     * @param   IteratorAggregate $collection
     * @param   callable $itemhandler
     * @param   progress_trace $trace
     * @param   string $context
     * @return  int
     */
    protected function invoke_iterate_safely(
        IteratorAggregate $collection,
        callable $itemhandler,
        progress_trace $trace,
        string $context
    ): int {
        $ref = new ReflectionClass(eduapi_client::class);
        $client = $ref->newInstanceWithoutConstructor();
        $method = $ref->getMethod('iterate_safely');
        $method->setAccessible(true);

        return $method->invoke($client, $collection, $itemhandler, $trace, $context);
    }

    /**
     * A capturing progress_trace mock: output() appends to $messages.
     *
     * @param   array $messages Populated (by reference) as output() is called
     * @return  progress_trace
     */
    protected function capturing_trace(array &$messages): progress_trace {
        $trace = $this->createMock(progress_trace::class);
        $trace->method('output')->willReturnCallback(function ($message) use (&$messages) {
            $messages[] = $message;
        });

        return $trace;
    }

    /**
     * A failure fetching a later page (the iterator throwing while advancing) is caught: items
     * already reached are processed, the failure is logged, and it counts as exactly one error - it
     * does not propagate out of iterate_safely().
     */
    public function test_pagination_failure_is_caught_and_processed_items_are_kept(): void {
        $collection = $this->make_collection(['a', 'b', 'c', 'd'], 2);
        $processed = [];
        $messages = [];

        $errors = $this->invoke_iterate_safely(
            $collection,
            function ($item) use (&$processed) {
                $processed[] = $item;
                return 0;
            },
            $this->capturing_trace($messages),
            'test offerings'
        );

        $this->assertSame(['a', 'b'], $processed);
        $this->assertSame(1, $errors);
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('test offerings', implode(' ', $messages));
    }

    /**
     * A per-item handler failure (e.g. a single offering/enrolment failing to convert) does not abort
     * iteration of the remaining items - only a pagination failure does.
     */
    public function test_per_item_failure_does_not_abort_iteration(): void {
        $collection = $this->make_collection(['a', 'b', 'c', 'd']);
        $processed = [];
        $messages = [];

        $errors = $this->invoke_iterate_safely(
            $collection,
            function ($item) use (&$processed) {
                if ($item === 'b') {
                    throw new RuntimeException("Simulated conversion failure for '{$item}'");
                }
                $processed[] = $item;
                return 0;
            },
            $this->capturing_trace($messages),
            'test enrollments'
        );

        $this->assertSame(['a', 'c', 'd'], $processed);
        $this->assertSame(1, $errors);
    }

    /**
     * An item handler's own returned error count (e.g. sync_offering()'s per-offering error tally) is
     * correctly accumulated, not just exceptions.
     */
    public function test_item_handler_returned_error_counts_are_accumulated(): void {
        $collection = $this->make_collection(['a', 'b', 'c']);
        $messages = [];

        $errors = $this->invoke_iterate_safely(
            $collection,
            fn($item) => $item === 'b' ? 2 : 0,
            $this->capturing_trace($messages),
            'test items'
        );

        $this->assertSame(2, $errors);
    }

    /**
     * The happy path: no pagination failure, no per-item failure, every item processed, zero errors.
     */
    public function test_happy_path_processes_everything_with_no_errors(): void {
        $collection = $this->make_collection(['a', 'b', 'c', 'd']);
        $processed = [];
        $messages = [];

        $errors = $this->invoke_iterate_safely(
            $collection,
            function ($item) use (&$processed) {
                $processed[] = $item;
                return 0;
            },
            $this->capturing_trace($messages),
            'test items'
        );

        $this->assertSame(['a', 'b', 'c', 'd'], $processed);
        $this->assertSame(0, $errors);
    }
}
