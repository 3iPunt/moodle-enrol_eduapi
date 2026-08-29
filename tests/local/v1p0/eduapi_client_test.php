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

use enrol_eduapi\local\interfaces\collection_factory as collection_factory_interface;
use enrol_eduapi\local\v1p0\entities\academic_session;
use enrol_eduapi\local\v1p0\entities\course_offering;
use enrol_eduapi\tests\fixtures\fake_paginated_collection;
use IteratorAggregate;
use progress_trace;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Tests for eduapi_client::iterate_safely(): the fault-tolerant iteration helper introduced to fix a
 * "single transient failure aborts the whole sync" defect. A plain foreach over a paginated (Generator
 * backed) collection cannot catch a failure raised while ADVANCING the iterator (i.e. fetching the
 * next page), only failures inside the loop body - iterate_safely() exists to catch both.
 *
 * Also covers eduapi_client::resolve_academic_session_scope() and eduapi_client::offering_in_scope():
 * the academic-session-scope expansion that lets a configured schoolYear session also accept offerings
 * tagged with one of its child semesters, instead of only an exact sourcedId match.
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
     * Invoke a protected/private method via reflection.
     *
     * No setAccessible(true) call is needed: CI runs PHP 8.3+, where accessibility checks for
     * Reflection calls were removed (PHP 8.1), making setAccessible() a no-op.
     *
     * $args is a plain array, not a variadic ...$args: a reference element (`&$var`) inside an array
     * keeps its reference identity even after the array is copied into this method's own $args
     * parameter, so a caller needing a by-reference parameter observed afterward (such as
     * resolve_academic_session_scope()'s $errors) can supply it as `&$errors` inside the array and read
     * it back once this method returns. A true `...$args` variadic cannot do this: PHP always copies
     * variadic arguments by value on capture, which is also why PHP 8.3+ warns if you try.
     *
     * @param   object $object
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    protected function invoke_protected(object $object, string $method, array $args) {
        $ref = new ReflectionMethod($object, $method);

        return $ref->invokeArgs($object, $args);
    }

    /**
     * Build a container whose collection factory's get_academic_sessions() yields the academic_session
     * entities returned by $buildsessions, as a fake_paginated_collection (optionally failing while
     * advancing to $failatposition). This is the same collection_factory::get_academic_sessions() call
     * testconnection.php:96 makes, and it is fetched exactly once by resolve_academic_session_scope().
     *
     * $buildsessions receives the container being built (entities need it to construct, even though a
     * fully pre-seeded entity never uses it to fetch), and must return an academic_session[].
     *
     * collection_factory (local\interfaces\collection_factory) is a marker interface with no declared
     * methods, so it cannot be mocked with PHPUnit's createMock()+method(); a small hand-written stub
     * implementing it stands in instead.
     *
     * @param   callable $buildsessions function(container $container): academic_session[]
     * @param   int|null $failatposition Throw while iterating to this position (0-based; 0 throws
     *                                   before any item is yielded), or never if null
     * @return  container
     */
    protected function mock_container_with_academic_sessions_collection(
        callable $buildsessions,
        ?int $failatposition = null
    ): container {
        $container = $this->createMock(container::class);
        $collection = $this->make_collection($buildsessions($container), $failatposition);

        $factory = new class($collection) implements collection_factory_interface {
            /** @var fake_paginated_collection */
            private $collection;

            /**
             * @param   fake_paginated_collection $collection
             */
            public function __construct(fake_paginated_collection $collection) {
                $this->collection = $collection;
            }

            /**
             * @return  fake_paginated_collection
             */
            public function get_academic_sessions() {
                return $this->collection;
            }
        };

        $container->method('get_collection_factory')->willReturn($factory);

        return $container;
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

    /**
     * A configured schoolYear session expands to itself plus its child semesters: an offering tagged
     * with a child semester id, or with the schoolYear id itself, is in scope; one tagged with an
     * unrelated session id is not.
     */
    public function test_schoolyear_scope_includes_child_semesters_but_not_unrelated_sessions(): void {
        $this->resetAfterTest();

        $container = $this->mock_container_with_academic_sessions_collection(function ($container) {
            return [
                new academic_session($container, 'sy-1', (object) ['sourcedId' => 'sy-1', 'children' => ['sem-1', 'sem-2']]),
                new academic_session($container, 'sem-1', (object) ['sourcedId' => 'sem-1', 'children' => []]),
                new academic_session($container, 'sem-2', (object) ['sourcedId' => 'sem-2', 'children' => []]),
            ];
        });

        $client = new eduapi_client($container);
        $messages = [];
        $errors = 0;
        $sessionids = $this->invoke_protected(
            $client,
            'resolve_academic_session_scope',
            ['sy-1', $this->capturing_trace($messages), &$errors]
        );

        $this->assertEqualsCanonicalizing(['sy-1', 'sem-1', 'sem-2'], $sessionids);
        $this->assertStringContainsString('expanded to 3 sessions', implode(' ', $messages));

        $offeringinsem1 = new course_offering($container, 'off-1', (object) [
            'sourcedId' => 'off-1',
            'organization' => 'org-a',
            'academicSession' => 'sem-1',
        ]);
        $offeringinschoolyear = new course_offering($container, 'off-2', (object) [
            'sourcedId' => 'off-2',
            'organization' => 'org-a',
            'academicSession' => 'sy-1',
        ]);
        $offeringunrelated = new course_offering($container, 'off-3', (object) [
            'sourcedId' => 'off-3',
            'organization' => 'org-a',
            'academicSession' => 'other-session',
        ]);

        $this->assertTrue($this->invoke_protected($client, 'offering_in_scope', [$offeringinsem1, ['org-a'], $sessionids]));
        $this->assertTrue($this->invoke_protected($client, 'offering_in_scope', [$offeringinschoolyear, ['org-a'], $sessionids]));
        $this->assertFalse($this->invoke_protected($client, 'offering_in_scope', [$offeringunrelated, ['org-a'], $sessionids]));
    }

    /**
     * A configured semester with no children resolves to exact match only.
     */
    public function test_semester_with_no_children_resolves_to_exact_match_only(): void {
        $this->resetAfterTest();

        $container = $this->mock_container_with_academic_sessions_collection(function ($container) {
            return [
                new academic_session($container, 'sem-1', (object) ['sourcedId' => 'sem-1', 'children' => []]),
            ];
        });

        $client = new eduapi_client($container);
        $messages = [];
        $errors = 0;
        $sessionids = $this->invoke_protected(
            $client,
            'resolve_academic_session_scope',
            ['sem-1', $this->capturing_trace($messages), &$errors]
        );

        $this->assertSame(['sem-1'], $sessionids);

        $offeringinsem1 = new course_offering($container, 'off-1', (object) [
            'sourcedId' => 'off-1',
            'organization' => 'org-a',
            'academicSession' => 'sem-1',
        ]);
        $offeringinsem2 = new course_offering($container, 'off-2', (object) [
            'sourcedId' => 'off-2',
            'organization' => 'org-a',
            'academicSession' => 'sem-2',
        ]);

        $this->assertTrue($this->invoke_protected($client, 'offering_in_scope', [$offeringinsem1, ['org-a'], $sessionids]));
        $this->assertFalse($this->invoke_protected($client, 'offering_in_scope', [$offeringinsem2, ['org-a'], $sessionids]));
    }

    /**
     * No configured academic session resolves to an empty scope, and offering_in_scope() then accepts
     * any academic session (only the organization and record-status checks still apply). The academic
     * sessions collection is never even iterated in this case.
     */
    public function test_no_configured_session_means_no_session_filtering(): void {
        $this->resetAfterTest();

        $container = $this->mock_container_with_academic_sessions_collection(fn() => []);
        $client = new eduapi_client($container);
        $messages = [];
        $errors = 0;

        $sessionids = $this->invoke_protected(
            $client,
            'resolve_academic_session_scope',
            [false, $this->capturing_trace($messages), &$errors]
        );

        $this->assertSame([], $sessionids);
        $this->assertEmpty($messages);

        $offering = new course_offering($container, 'off-1', (object) [
            'sourcedId' => 'off-1',
            'organization' => 'org-a',
            'academicSession' => 'whatever-session',
        ]);

        $this->assertTrue($this->invoke_protected($client, 'offering_in_scope', [$offering, ['org-a'], $sessionids]));
    }

    /**
     * If the configured session id is not present among the fetched academic sessions at all (no fetch
     * failure, it is just genuinely not there), the result falls back to the configured id alone, with
     * a trace note.
     */
    public function test_configured_session_not_present_in_collection_falls_back_to_exact_match(): void {
        $container = $this->mock_container_with_academic_sessions_collection(function ($container) {
            return [
                new academic_session($container, 'other-1', (object) ['sourcedId' => 'other-1', 'children' => []]),
            ];
        });

        $client = new eduapi_client($container);
        $messages = [];
        $errors = 0;
        $sessionids = $this->invoke_protected(
            $client,
            'resolve_academic_session_scope',
            ['missing-id', $this->capturing_trace($messages), &$errors]
        );

        $this->assertSame(['missing-id'], $sessionids);
        $this->assertNotEmpty($messages);
    }

    /**
     * A genuine failure fetching the academic sessions collection (the very first page fetch throws,
     * before any item is yielded) increments $errors by exactly one, logs that expansion may be
     * incomplete, and falls back to exact match on the configured id.
     */
    public function test_academic_sessions_fetch_failure_increments_errors_and_falls_back_to_exact_match(): void {
        $container = $this->mock_container_with_academic_sessions_collection(fn() => [], 0);
        $client = new eduapi_client($container);
        $messages = [];
        $errors = 0;

        $sessionids = $this->invoke_protected(
            $client,
            'resolve_academic_session_scope',
            ['sy-broken', $this->capturing_trace($messages), &$errors]
        );

        $this->assertSame(['sy-broken'], $sessionids);
        $this->assertSame(1, $errors);
        $this->assertStringContainsString('Expansion may be incomplete', implode(' ', $messages));
    }

    /**
     * A cycle in the children map (A's child is B, whose child is A) does not loop forever: the
     * visited-set guard stops expansion once every reachable session has been seen. With a finite
     * in-memory map built from a single collection fetch, no depth bound is needed for this.
     */
    public function test_cycle_in_children_does_not_loop_forever(): void {
        $container = $this->mock_container_with_academic_sessions_collection(function ($container) {
            return [
                new academic_session($container, 'a', (object) ['sourcedId' => 'a', 'children' => ['b']]),
                new academic_session($container, 'b', (object) ['sourcedId' => 'b', 'children' => ['a']]),
            ];
        });

        $client = new eduapi_client($container);
        $messages = [];
        $errors = 0;
        $sessionids = $this->invoke_protected(
            $client,
            'resolve_academic_session_scope',
            ['a', $this->capturing_trace($messages), &$errors]
        );

        $this->assertEqualsCanonicalizing(['a', 'b'], $sessionids);
    }
}
