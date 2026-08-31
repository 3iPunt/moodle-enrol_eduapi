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

use core_text;
use enrol_eduapi\local\interfaces\container as container_interface;
use enrol_eduapi\local\v1p0\entities\component_offering;
use enrol_eduapi\local\v1p0\entities\course_offering;

/**
 * Tests for offering_converter::get_course_data() (pure fullname/summary derivation),
 * convert_to_group() (group name derivation) and convert() (course summary create/update
 * behaviour), all driven by the sync_description and multilang settings.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\converters\offering_converter
 */
final class offering_converter_test extends \advanced_testcase {
    /**
     * Build a CourseOffering entity pre-seeded with data, so get()/get_id() never trigger a fetch.
     *
     * @param   string $sourcedid
     * @param   array $data Raw fields, merged over sensible defaults
     * @return  course_offering
     */
    protected function make_course_offering(string $sourcedid, array $data = []): course_offering {
        $container = $this->createMock(container_interface::class);
        $defaults = [
            'sourcedId' => $sourcedid,
            'recordStatus' => 'active',
            'title' => [],
            'startDate' => null,
            'endDate' => null,
        ];

        return new course_offering($container, $sourcedid, (object) array_merge($defaults, $data));
    }

    /**
     * Build a ComponentOffering entity pre-seeded with data, so get()/get_id() never trigger a fetch.
     *
     * @param   string $sourcedid
     * @param   array $data Raw fields, merged over sensible defaults
     * @return  component_offering
     */
    protected function make_component_offering(string $sourcedid, array $data = []): component_offering {
        $container = $this->createMock(container_interface::class);
        $defaults = [
            'sourcedId' => $sourcedid,
            'recordStatus' => 'active',
            'title' => [],
        ];

        return new component_offering($container, $sourcedid, (object) array_merge($defaults, $data));
    }

    /**
     * get_course_data() picks the title matching the site's default language ($CFG->lang) when
     * multilang is off, ignoring the acting user's own session language.
     */
    public function test_get_course_data_fullname_uses_site_lang_when_multilang_off(): void {
        $this->resetAfterTest();
        set_config('multilang', 0, 'enrol_eduapi');
        global $CFG, $SESSION;
        $CFG->lang = 'en';
        // A different session/current language must not affect the resolved fullname.
        $SESSION->lang = 'el';

        $offering = $this->make_course_offering('src-1', [
            'title' => [
                (object) ['recordLanguage' => 'en-US', 'value' => 'English Title'],
                (object) ['recordLanguage' => 'el', 'value' => 'Greek Title'],
            ],
        ]);

        $data = offering_converter::get_course_data($offering);

        $this->assertSame('English Title', $data->fullname);
    }

    /**
     * get_course_data() truncates a multilang fullname to 254 characters, since course.fullname and
     * groups.name cannot hold arbitrarily long concatenated markup.
     */
    public function test_get_course_data_truncates_fullname_to_254_chars(): void {
        $this->resetAfterTest();
        set_config('multilang', 1, 'enrol_eduapi');

        $longvalue = str_repeat('A', 300);
        $offering = $this->make_course_offering('src-long-1', [
            'title' => [(object) ['recordLanguage' => 'en-US', 'value' => $longvalue]],
        ]);

        $data = offering_converter::get_course_data($offering);

        $this->assertLessThanOrEqual(254, core_text::strlen($data->fullname));
    }

    /**
     * get_course_data() wraps every title in a multilang span when multilang is on.
     */
    public function test_get_course_data_fullname_wraps_titles_when_multilang_on(): void {
        $this->resetAfterTest();
        set_config('multilang', 1, 'enrol_eduapi');

        $offering = $this->make_course_offering('src-2', [
            'title' => [
                (object) ['recordLanguage' => 'en-US', 'value' => 'English Title'],
                (object) ['recordLanguage' => 'el', 'value' => 'Greek Title'],
            ],
        ]);

        $data = offering_converter::get_course_data($offering);

        $expected = '<span lang="en" class="multilang">English Title</span>'
            . '<span lang="el" class="multilang">Greek Title</span>';

        $this->assertSame($expected, $data->fullname);
    }

    /**
     * get_course_data() falls back to the sourcedId when the offering has no title at all.
     */
    public function test_get_course_data_fullname_falls_back_to_sourcedid_without_titles(): void {
        $this->resetAfterTest();

        $offering = $this->make_course_offering('src-3', ['title' => []]);

        $data = offering_converter::get_course_data($offering);

        $this->assertSame('src-3', $data->fullname);
    }

    /**
     * get_course_data() sets summary/summaryformat from the description when sync_description is on.
     *
     * The summary is stored as FORMAT_MOODLE (not FORMAT_HTML) so the provider's plain-text line
     * breaks are preserved (nl2br) while the multilang filter still runs.
     */
    public function test_get_course_data_sets_summary_when_sync_description_enabled(): void {
        $this->resetAfterTest();
        set_config('sync_description', 1, 'enrol_eduapi');

        $offering = $this->make_course_offering('src-4', [
            'description' => [(object) ['recordLanguage' => 'en-US', 'value' => 'A description']],
        ]);

        $data = offering_converter::get_course_data($offering);

        $this->assertSame('A description', $data->summary);
        $this->assertEquals(FORMAT_MOODLE, $data->summaryformat);
    }

    /**
     * get_course_data() does not set summary/summaryformat at all when sync_description is off.
     */
    public function test_get_course_data_does_not_set_summary_when_sync_description_disabled(): void {
        $this->resetAfterTest();
        set_config('sync_description', 0, 'enrol_eduapi');

        $offering = $this->make_course_offering('src-5', [
            'description' => [(object) ['recordLanguage' => 'en-US', 'value' => 'A description']],
        ]);

        $data = offering_converter::get_course_data($offering);

        $this->assertFalse(property_exists($data, 'summary'));
        $this->assertFalse(property_exists($data, 'summaryformat'));
    }

    /**
     * get_course_data() does not set summary/summaryformat when the offering has no description,
     * even with sync_description on, so an existing course summary is never blanked.
     */
    public function test_get_course_data_does_not_set_summary_without_description(): void {
        $this->resetAfterTest();
        set_config('sync_description', 1, 'enrol_eduapi');

        $offering = $this->make_course_offering('src-6', ['description' => []]);

        $data = offering_converter::get_course_data($offering);

        $this->assertFalse(property_exists($data, 'summary'));
    }

    /**
     * convert_to_group() wraps the component's title in multilang spans when multilang is on.
     */
    public function test_convert_to_group_name_wraps_titles_when_multilang_on(): void {
        $this->resetAfterTest();
        set_config('sync_groups', 1, 'enrol_eduapi');
        set_config('multilang', 1, 'enrol_eduapi');

        $course = $this->getDataGenerator()->create_course();
        $component = $this->make_component_offering('comp-1', [
            'title' => [
                (object) ['recordLanguage' => 'en-US', 'value' => 'English Component'],
                (object) ['recordLanguage' => 'el', 'value' => 'Greek Component'],
            ],
        ]);

        $group = offering_converter::convert_to_group($component, $course);

        $expected = '<span lang="en" class="multilang">English Component</span>'
            . '<span lang="el" class="multilang">Greek Component</span>';

        $this->assertSame($expected, $group->name);
    }

    /**
     * convert_to_group() falls back to the sourcedId when the component has no title.
     */
    public function test_convert_to_group_name_falls_back_to_sourcedid_without_titles(): void {
        $this->resetAfterTest();
        set_config('sync_groups', 1, 'enrol_eduapi');

        $course = $this->getDataGenerator()->create_course();
        $component = $this->make_component_offering('comp-2', ['title' => []]);

        $group = offering_converter::convert_to_group($component, $course);

        $this->assertSame('comp-2', $group->name);
    }

    /**
     * convert_to_group() creates a group without a DML error when a multilang name would otherwise
     * exceed the groups.name column length (254 chars), by truncating it first.
     */
    public function test_convert_to_group_name_truncated_avoids_dml_error_over_254_chars(): void {
        $this->resetAfterTest();
        set_config('sync_groups', 1, 'enrol_eduapi');
        set_config('multilang', 1, 'enrol_eduapi');

        $course = $this->getDataGenerator()->create_course();
        $longvalue = str_repeat('C', 300);
        $component = $this->make_component_offering('comp-long-1', [
            'title' => [
                (object) ['recordLanguage' => 'en-US', 'value' => $longvalue],
                (object) ['recordLanguage' => 'el', 'value' => $longvalue],
            ],
        ]);

        $group = offering_converter::convert_to_group($component, $course);

        $this->assertLessThanOrEqual(254, core_text::strlen($group->name));
    }

    /**
     * convert() writes the resolved description as the summary (FORMAT_MOODLE) on a newly created
     * course.
     */
    public function test_convert_creates_course_with_summary_when_sync_description_enabled(): void {
        $this->resetAfterTest();
        set_config('sync_description', 1, 'enrol_eduapi');

        $offering = $this->make_course_offering('src-summary-1', [
            'title' => [(object) ['recordLanguage' => 'en-US', 'value' => 'Course With Summary']],
            'description' => [(object) ['recordLanguage' => 'en-US', 'value' => 'Course description']],
        ]);

        $course = offering_converter::convert($offering);

        $this->assertSame('Course description', $course->summary);
        $this->assertEquals(FORMAT_MOODLE, $course->summaryformat);
    }

    /**
     * convert() creates a course without a DML error when a multilang fullname would otherwise exceed
     * the course.fullname column length, by truncating it to 254 chars first.
     */
    public function test_convert_creates_course_without_dml_error_when_fullname_exceeds_254_chars(): void {
        $this->resetAfterTest();
        set_config('multilang', 1, 'enrol_eduapi');

        $longvalue = str_repeat('B', 300);
        $offering = $this->make_course_offering('src-long-2', [
            'title' => [
                (object) ['recordLanguage' => 'en-US', 'value' => $longvalue],
                (object) ['recordLanguage' => 'el', 'value' => $longvalue],
            ],
        ]);

        $course = offering_converter::convert($offering);

        $this->assertLessThanOrEqual(254, core_text::strlen($course->fullname));
    }

    /**
     * convert() leaves the course summary at its default when sync_description is off.
     */
    public function test_convert_does_not_write_summary_when_sync_description_disabled(): void {
        $this->resetAfterTest();
        set_config('sync_description', 0, 'enrol_eduapi');

        $offering = $this->make_course_offering('src-summary-2', [
            'title' => [(object) ['recordLanguage' => 'en-US', 'value' => 'Course Without Summary']],
            'description' => [(object) ['recordLanguage' => 'en-US', 'value' => 'Should not be used']],
        ]);

        $course = offering_converter::convert($offering);

        $this->assertEmpty($course->summary);
    }

    /**
     * convert() never blanks an already-synced course's summary when a later sync carries no
     * description.
     */
    public function test_convert_does_not_blank_existing_summary_without_description(): void {
        $this->resetAfterTest();
        set_config('sync_description', 1, 'enrol_eduapi');

        $this->getDataGenerator()->create_course([
            'idnumber' => 'src-summary-3',
            'summary' => 'Existing summary text',
            'summaryformat' => FORMAT_HTML,
        ]);

        $offering = $this->make_course_offering('src-summary-3', [
            'title' => [(object) ['recordLanguage' => 'en-US', 'value' => 'Updated Title']],
        ]);

        $course = offering_converter::convert($offering);

        $this->assertSame('Existing summary text', $course->summary);
    }
}
