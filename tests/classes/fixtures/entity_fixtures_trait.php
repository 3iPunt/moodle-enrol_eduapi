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

use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\entities\person;
use progress_trace;

/**
 * Shared PHPUnit fixture helpers: a pre-seeded Person entity builder and a message-capturing
 * progress_trace mock. Used by enrollment_converter_test, person_converter_test and eduapi_client_test
 * to avoid keeping three copies of the same test doubles in sync.
 *
 * Only usable inside a \PHPUnit\Framework\TestCase subclass (relies on $this->createMock()).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait entity_fixtures_trait {
    /**
     * Build a Person entity pre-seeded with data, so get()/get_id() never trigger a network fetch.
     *
     * @param   string $sourcedid
     * @param   array $data Raw fields, merged over sensible defaults
     * @return  person
     */
    protected function make_person(string $sourcedid, array $data = []): person {
        $container = $this->createMock(container_interface::class);
        $defaults = [
            'sourcedId' => $sourcedid,
            'recordStatus' => 'active',
            'legalName' => (object) ['givenName' => 'Test', 'familyName' => 'Person'],
            'primaryEmail' => null,
            'otherIdentifiers' => [],
        ];

        return new person($container, $sourcedid, (object) array_merge($defaults, $data));
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
}
