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

/**
 * Tests for converter's language helpers: to_moodle_lang() (Edu-API recordLanguage/locale to a
 * Moodle language code) and pick_language_value() (selecting or combining LanguageTypedString[]
 * entries, as returned by entity::get('title')/entity::get('description'), into one string).
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\converter
 */
final class converter_test extends \advanced_testcase {
    /**
     * to_moodle_lang() normalizes a hyphenated locale to its lowercase, underscored Moodle form.
     */
    public function test_to_moodle_lang_normalizes_hyphenated_locale(): void {
        $this->assertSame('en', converter::to_moodle_lang('en-US'));
    }

    /**
     * to_moodle_lang() leaves a bare, already-Moodle-shaped language code untouched.
     */
    public function test_to_moodle_lang_leaves_bare_code_untouched(): void {
        $this->assertSame('es', converter::to_moodle_lang('es'));
    }

    /**
     * to_moodle_lang() is case-insensitive.
     */
    public function test_to_moodle_lang_is_case_insensitive(): void {
        $this->assertSame('en', converter::to_moodle_lang('EN_us'));
    }

    /**
     * to_moodle_lang() keeps a code unchanged when it is itself an installed translation.
     */
    public function test_to_moodle_lang_keeps_installed_translation(): void {
        $this->assertSame('en', converter::to_moodle_lang('en'));
    }

    /**
     * to_moodle_lang() guards empty input by returning an empty string rather than guessing.
     */
    public function test_to_moodle_lang_returns_empty_for_empty_input(): void {
        $this->assertSame('', converter::to_moodle_lang(''));
    }

    /**
     * pick_language_value() returns an empty string for an empty entries array.
     */
    public function test_pick_language_value_returns_empty_for_empty_array(): void {
        $this->assertSame('', converter::pick_language_value([], false));
    }

    /**
     * pick_language_value() skips an entry whose value is empty, regardless of the multilang mode.
     */
    public function test_pick_language_value_skips_empty_value_entry(): void {
        $entries = [
            (object) ['recordLanguage' => 'en-US', 'value' => ''],
            (object) ['recordLanguage' => 'es', 'value' => 'Valor'],
        ];

        $this->assertSame('Valor', converter::pick_language_value($entries, false));
    }

    /**
     * pick_language_value() with multilang on wraps every non-empty entry in its own multilang span.
     */
    public function test_pick_language_value_multilang_on_wraps_every_entry(): void {
        $entries = [
            (object) ['recordLanguage' => 'en-US', 'value' => 'English'],
            (object) ['recordLanguage' => 'el', 'value' => 'Greek'],
        ];

        $expected = '<span lang="en" class="multilang">English</span>'
            . '<span lang="el" class="multilang">Greek</span>';

        $this->assertSame($expected, converter::pick_language_value($entries, true));
    }

    /**
     * pick_language_value() with multilang on omits the span wrapper for an entry whose
     * recordLanguage cannot be mapped to a Moodle language code.
     */
    public function test_pick_language_value_multilang_on_omits_unmappable_language(): void {
        $entries = [
            (object) ['recordLanguage' => '', 'value' => 'No language'],
            (object) ['recordLanguage' => 'en-US', 'value' => 'English'],
        ];

        $this->assertSame('<span lang="en" class="multilang">English</span>', converter::pick_language_value($entries, true));
    }

    /**
     * pick_language_value() with multilang on falls back to the first non-empty entry's raw value
     * when every entry's recordLanguage is unmappable, so the title is never lost entirely.
     */
    public function test_pick_language_value_multilang_on_falls_back_to_first_value_when_all_unmappable(): void {
        $entries = [(object) ['recordLanguage' => '', 'value' => 'Untranslated Title']];

        $this->assertSame('Untranslated Title', converter::pick_language_value($entries, true));
    }

    /**
     * pick_language_value() with multilang off picks the entry matching the explicit $sitelang param,
     * regardless of the array order.
     */
    public function test_pick_language_value_multilang_off_picks_explicit_sitelang(): void {
        $entries = [
            (object) ['recordLanguage' => 'en-US', 'value' => 'English'],
            (object) ['recordLanguage' => 'el', 'value' => 'Greek'],
        ];

        $this->assertSame('Greek', converter::pick_language_value($entries, false, 'el'));
    }

    /**
     * pick_language_value() with multilang off defaults $sitelang to $CFG->lang, not to the acting
     * user's session/current language, so the resolved value is stable across who triggers the sync.
     */
    public function test_pick_language_value_multilang_off_defaults_to_cfg_lang(): void {
        $this->resetAfterTest();
        global $CFG, $SESSION;
        $CFG->lang = 'el';
        // A different session language must not influence the result.
        $SESSION->lang = 'fr';

        $entries = [
            (object) ['recordLanguage' => 'en-US', 'value' => 'English'],
            (object) ['recordLanguage' => 'el', 'value' => 'Greek'],
        ];

        $this->assertSame('Greek', converter::pick_language_value($entries, false));
    }

    /**
     * pick_language_value() with multilang off ignores the session language entirely once an explicit
     * $sitelang is given, proving the result no longer depends on current_language().
     */
    public function test_pick_language_value_multilang_off_ignores_session_language(): void {
        $this->resetAfterTest();
        global $SESSION;
        $SESSION->lang = 'fr';

        $entries = [
            (object) ['recordLanguage' => 'en-US', 'value' => 'English'],
            (object) ['recordLanguage' => 'el', 'value' => 'Greek'],
        ];

        $this->assertSame('Greek', converter::pick_language_value($entries, false, 'el'));
    }

    /**
     * pick_language_value() with multilang off falls back to English when $sitelang has no matching
     * entry.
     */
    public function test_pick_language_value_multilang_off_falls_back_to_english(): void {
        $entries = [
            (object) ['recordLanguage' => 'en-US', 'value' => 'English'],
            (object) ['recordLanguage' => 'es', 'value' => 'Spanish'],
        ];

        $this->assertSame('English', converter::pick_language_value($entries, false, 'pt'));
    }

    /**
     * pick_language_value() with multilang off falls back to the first non-empty entry when neither
     * $sitelang nor English is present.
     */
    public function test_pick_language_value_multilang_off_falls_back_to_first_entry(): void {
        $entries = [
            (object) ['recordLanguage' => 'fr', 'value' => 'French'],
            (object) ['recordLanguage' => 'de', 'value' => 'German'],
        ];

        $this->assertSame('French', converter::pick_language_value($entries, false, 'pt'));
    }
}
