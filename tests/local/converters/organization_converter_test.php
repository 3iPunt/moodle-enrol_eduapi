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

namespace enrol_eduapi\local\converters;

use coding_exception;
use enrol_eduapi\local\factories\entity_factory;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\entities\organization;
use progress_trace;

/**
 * Tests for organization_converter: the recursive parent-hierarchy cycle guard, and a plain
 * (non-cyclic) conversion against a real (test) database.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\converters\organization_converter
 */
final class organization_converter_test extends \advanced_testcase {
    /**
     * Build a container whose entity factory resolves fetch_organization_by_id() from an in-memory
     * registry of pre-seeded organization entities - so get_parent() traversal never hits the network.
     * $registry is taken by reference so the caller can populate it with entities AFTER building the
     * container (the entities themselves need this container to construct, so the registry cannot be
     * fully populated beforehand).
     *
     * @param   array $registry sourcedId => organization, populated by the caller after this call
     * @return  container_interface
     */
    protected function mock_container_with_organizations(array &$registry): container_interface {
        $container = $this->createMock(container_interface::class);

        $factory = $this->getMockBuilder(entity_factory::class)
            ->setConstructorArgs([$container])
            ->onlyMethods(['fetch_organization_by_id'])
            ->getMock();
        $factory->method('fetch_organization_by_id')->willReturnCallback(
            function (string $id) use (&$registry) {
                return $registry[$id];
            }
        );

        $container->method('get_entity_factory')->willReturn($factory);

        return $container;
    }

    /**
     * A circular Organization parent chain (A's parent is B, whose parent is A) throws a
     * coding_exception instead of recursing forever, and never reaches the database.
     */
    public function test_convert_throws_on_circular_parent_chain(): void {
        global $DB;

        $this->resetAfterTest();

        $registry = [];
        $container = $this->mock_container_with_organizations($registry);

        $orga = new organization($container, 'org-a', (object) [
            'sourcedId' => 'org-a',
            'name' => [(object) ['value' => 'Org A']],
            'recordStatus' => 'active',
            'parent' => 'org-b',
        ]);
        $orgb = new organization($container, 'org-b', (object) [
            'sourcedId' => 'org-b',
            'name' => [(object) ['value' => 'Org B']],
            'recordStatus' => 'active',
            'parent' => 'org-a',
        ]);
        $registry['org-a'] = $orga;
        $registry['org-b'] = $orgb;

        $categorycountbefore = $DB->count_records('course_categories');

        try {
            organization_converter::convert($orga);
            $this->fail('Expected a coding_exception for the circular parent chain.');
        } catch (coding_exception $e) {
            $this->assertEquals($categorycountbefore, $DB->count_records('course_categories'));
        }
    }

    /**
     * The cycle guard reports a clear message (naming the chain) to the sync trace before throwing.
     */
    public function test_convert_circular_parent_chain_reports_to_trace(): void {
        $registry = [];
        $container = $this->mock_container_with_organizations($registry);

        // A self-referencing Organization (its own parent) is the simplest possible cycle.
        $orga = new organization($container, 'org-a', (object) [
            'sourcedId' => 'org-a',
            'name' => [(object) ['value' => 'Org A']],
            'recordStatus' => 'active',
            'parent' => 'org-a',
        ]);
        $registry['org-a'] = $orga;

        $messages = [];
        $trace = $this->createMock(progress_trace::class);
        $trace->method('output')->willReturnCallback(function ($message) use (&$messages) {
            $messages[] = $message;
        });

        try {
            organization_converter::convert($orga, $trace);
            $this->fail('Expected a coding_exception for the self-referencing parent chain.');
        } catch (coding_exception $e) {
            $this->assertNotEmpty($messages);
            $this->assertStringContainsString('org-a', implode(' ', $messages));
        }
    }

    /**
     * A non-cyclic Organization with no parent is converted into a real course category.
     */
    public function test_convert_creates_root_category(): void {
        global $DB;

        $this->resetAfterTest();

        $container = $this->createMock(container_interface::class);
        $org = new organization($container, 'root-org-1', (object) [
            'sourcedId' => 'root-org-1',
            'name' => [(object) ['value' => 'Root Organization']],
            'recordStatus' => 'active',
            'parent' => null,
        ]);

        $category = organization_converter::convert($org);

        $this->assertSame('root-org-1', $category->idnumber);
        $this->assertTrue($DB->record_exists('course_categories', ['idnumber' => 'root-org-1']));
    }
}
