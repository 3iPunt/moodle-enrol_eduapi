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

namespace enrol_eduapi;

use progress_trace;

/**
 * A progress_trace that buffers messages in memory, for returning them in a web service response.
 *
 * Replicates enrol_oneroster\json_progress_trace, used by offering_webhook.php (development phase 6)
 * to surface the sync log of a single-offering sync back to the caller.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class json_progress_trace extends progress_trace {
    /** @var string[] Buffered messages */
    protected array $messages = [];

    /**
     * Constructor.
     */
    public function __construct() {
        // Nothing to initialise.
    }

    /**
     * Output a progress message: buffer it instead of printing it.
     *
     * @param   string $message
     * @param   int $depth
     */
    public function output(string $message, int $depth = 0): void {
        $this->messages[] = $message;
    }

    /**
     * Get the buffered, non-empty messages.
     *
     * @return  string[]
     */
    public function get_data(): array {
        return array_values(array_filter($this->messages, fn($line) => trim($line) !== ''));
    }
}
