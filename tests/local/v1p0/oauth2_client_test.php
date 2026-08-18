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

use enrol_eduapi\local\command;
use enrol_eduapi\local\exceptions\unauthorized;
use moodle_url;
use stdClass;

/**
 * Tests for oauth2_client's token-lifecycle handling: proactive re-authentication when the tracked
 * token has expired, and reactive re-authentication + single retry on an actual 401 response.
 *
 * No real HTTP call is made: authenticate() and execute_once() (the two protected methods that talk
 * to the network) are mocked out, so only the orchestration logic in execute() itself is under test.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \enrol_eduapi\local\v1p0\oauth2_client
 */
final class oauth2_client_test extends \advanced_testcase {
    /**
     * Build an oauth2_client with authenticate() and execute_once() mocked out.
     *
     * @return  oauth2_client
     */
    protected function mock_client(): oauth2_client {
        return $this->getMockBuilder(oauth2_client::class)
            ->setConstructorArgs(['https://example.invalid/token', 'https://example.invalid/api', 'id', 'secret'])
            ->onlyMethods(['authenticate', 'execute_once'])
            ->getMock();
    }

    /**
     * A successful canned execute_once() response, in the shape execute() expects to return verbatim.
     *
     * @return  stdClass
     */
    protected function canned_response(): stdClass {
        return (object) ['info' => ['http_code' => 200], 'response' => (object) ['sourcedId' => 'ok']];
    }

    /**
     * execute() re-authenticates proactively (before ever calling execute_once()) when no access
     * token has been obtained yet.
     */
    public function test_execute_authenticates_when_no_token_yet(): void {
        $this->resetAfterTest();

        $client = $this->mock_client();
        $client->expects($this->once())->method('authenticate')->willReturnCallback(function () use ($client) {
            $client->set_access_token('fresh-token', time() + 3600, 'scope');
        });
        $client->expects($this->once())->method('execute_once')->willReturn($this->canned_response());

        $command = $this->createMock(command::class);
        $result = $client->execute($command);

        $this->assertSame('ok', $result->response->sourcedId);
    }

    /**
     * execute() re-authenticates proactively when the tracked token has expired, without waiting for
     * a 401 from the provider.
     */
    public function test_execute_reauthenticates_when_token_expired(): void {
        $this->resetAfterTest();

        $client = $this->mock_client();
        $client->set_access_token('stale-token', time() - 3600, 'scope');

        $client->expects($this->once())->method('authenticate')->willReturnCallback(function () use ($client) {
            $client->set_access_token('fresh-token', time() + 3600, 'scope');
        });
        $client->expects($this->once())->method('execute_once')->willReturn($this->canned_response());

        $command = $this->createMock(command::class);
        $client->execute($command);

        $this->assertSame('fresh-token', $client->get_access_token()->token);
    }

    /**
     * execute() does NOT re-authenticate when the tracked token is still valid.
     */
    public function test_execute_does_not_reauthenticate_when_token_is_fresh(): void {
        $this->resetAfterTest();

        $client = $this->mock_client();
        $client->set_access_token('fresh-token', time() + 3600, 'scope');

        $client->expects($this->never())->method('authenticate');
        $client->expects($this->once())->method('execute_once')->willReturn($this->canned_response());

        $command = $this->createMock(command::class);
        $client->execute($command);
    }

    /**
     * A single 401 Unauthorized from execute_once() triggers exactly one re-authentication and one
     * retry of the same request; the retried request's result is returned normally.
     */
    public function test_execute_retries_once_on_401(): void {
        $this->resetAfterTest();

        $client = $this->mock_client();
        $client->set_access_token('valid-looking-token', time() + 3600, 'scope');

        $client->expects($this->once())->method('authenticate')->willReturnCallback(function () use ($client) {
            $client->set_access_token('rotated-token', time() + 3600, 'scope');
        });

        $unauthorizedexception = new unauthorized(
            'Unauthorized',
            ['http_code' => 401],
            new moodle_url('https://example.invalid/api')
        );
        $attempt = 0;
        $client->expects($this->exactly(2))
            ->method('execute_once')
            ->willReturnCallback(function () use (&$attempt, $unauthorizedexception) {
                $attempt++;
                if ($attempt === 1) {
                    throw $unauthorizedexception;
                }
                return $this->canned_response();
            });

        $command = $this->createMock(command::class);
        $result = $client->execute($command);

        $this->assertSame('ok', $result->response->sourcedId);
        $this->assertSame('rotated-token', $client->get_access_token()->token);
    }

    /**
     * A second consecutive 401 (even after the one retry) is not swallowed: it propagates to the
     * caller instead of retrying forever.
     */
    public function test_execute_does_not_retry_a_second_401(): void {
        $this->resetAfterTest();

        $client = $this->mock_client();
        $client->set_access_token('valid-looking-token', time() + 3600, 'scope');

        $client->expects($this->once())->method('authenticate');

        $unauthorizedexception = new unauthorized(
            'Unauthorized',
            ['http_code' => 401],
            new moodle_url('https://example.invalid/api')
        );
        $client->expects($this->exactly(2))
            ->method('execute_once')
            ->willThrowException($unauthorizedexception);

        $command = $this->createMock(command::class);

        $this->expectException(unauthorized::class);
        $client->execute($command);
    }
}
