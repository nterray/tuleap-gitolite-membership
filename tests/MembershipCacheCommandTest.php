<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
*
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
*
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace TuleapClient\Gitolite;

use Symfony\Component\Console\Tester\CommandTester;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Plugin\Mock\MockPlugin;

class MembershipCacheCommandTest extends \PHPUnit_Framework_TestCase {

    /** @var MembershipRetrieveCommand */
    private $command;

    /** @var MockPlugin */
    private $plugin;

    /** @var Response */
    private $invalid_token_response;

    /** @var Response */
    private $invalid_password_response;

    /** @var Response */
    private $new_token_response;

    /** @var Response */
    private $users_memberships_response_01;

    /** @var Response */
    private $users_memberships_response_02;

    /** @var Response */
    private $users_memberships_response_03;

    /** @var string */
    private $users_memberships_response_content;

    /** @var string */
    private $fixture_dir;

    /** @var string */
    private $config_file;

    /** @var string */
    private $token_file;

    /** @var string */
    private $keydir_path;

    /** @var string */
    private $membership_cache;

    /** @var string */
    private $original_config_file_content;

    public function setUp() {
        parent::setUp();
        $this->plugin = new MockPlugin();

        $client = new Client();
        $client->addSubscriber($this->plugin);

        $this->fixture_dir      = __DIR__ .'/_fixtures';
        $this->config_file      = $this->fixture_dir .'/config.ini';
        $this->token_file       = $this->fixture_dir .'/token.json';
        $this->keydir_path      = $this->fixture_dir .'/keydir';
        $this->membership_cache = $this->fixture_dir .'/users.json';

        $this->original_config_file_content = $this->createConfigFile();

        $this->command = new MembershipCacheCommand(
            $client,
            new ConfigurationLoader(),
            $this->config_file
        );

        $this->invalid_token_response    = new Response(401);
        $this->invalid_password_response = new Response(401);
        $this->new_token_response        = new Response(
            200,
            array('Content-Type' => 'application/json'),
            '{"user_id":666,"token":"new_token","uri":"tokens/new_token"}'
        );

        $this->users_memberships_response_content = '
            {
                "user01" : [
                    "site_active",
                    "project01_project_members",
                    "project01_project_admin",
                    "ug_101"
                ],
                "user02" : [
                    "site_active",
                    "project01_project_members"
                ],
                "user03" : [
                    "site_active",
                    "project01_project_members"
                ],
                "user04" : [
                    "site_active"
                ],
                "jcdusse" : [
                    "site_active",
                    "tuleap_project_members",
                    "tuleap_project_admin"
                ]
            }';

        $this->users_memberships_response_01 = new Response(
            200,
            array(
                'Content-Type' => 'application/json',
                'X-PAGINATION-SIZE' => '2000'
            ),
            '[{
                "username": "user01",
                "user_groups": [
                    "site_active",
                    "project01_project_members",
                    "project01_project_admin",
                    "ug_101"
                ]
              },
              {
                "username": "user02",
                "user_groups": [
                    "site_active",
                    "project01_project_members"
                ]
              },
              {
                "username": "user03",
                "user_groups": [
                    "site_active",
                    "project01_project_members"
                ]
              }
            ]'
        );

        $this->users_memberships_response_02 = new Response(
            200,
            array(
                'Content-Type' => 'application/json',
                'X-PAGINATION-SIZE' => '2000'
            ),
            '[{
                "username": "user04",
                "user_groups": [
                    "site_active"
                ]
              },
              {
                "username": "jcdusse",
                "user_groups": [
                    "site_active",
                    "tuleap_project_members",
                    "tuleap_project_admin"
                ]
              }
            ]'
        );

        $this->users_memberships_response_03 = new Response(
            200,
            array('Content-Type' => 'application/json'),
            '[{
                "username": "user04",
                "user_groups": [
                    "site_active"
                ]
              },
              {
                "username": "jcdusse",
                "user_groups": [
                    "site_active",
                    "tuleap_project_members",
                    "tuleap_project_admin"
                ]
              }
            ]'
        );
    }

    protected function tearDown() {
        $files_to_be_removed = array(
            $this->config_file,
            $this->token_file,
            $this->membership_cache
        );
        foreach ($files_to_be_removed as $filename) {
            if (is_file($filename)) {
                unlink($filename);
            }
        }

        parent::tearDown();
    }

    private function createConfigFile() {
        $original = __DIR__ .'/../config.ini';

        $content = str_replace(
            '/var/cache/tuleap-gitolite-membership/token.json',
            $this->token_file,
            file_get_contents($original)
        );

        $content_with_keydir = str_replace(
            '/var/lib/gitolite/admin/keydir/',
            $this->keydir_path,
            $content
        );

        $content_with_keydir_and_users_file = str_replace(
            '/var/cache/tuleap-gitolite-membership/users.json',
            $this->membership_cache,
            $content_with_keydir
        );

        file_put_contents($this->config_file, $content_with_keydir_and_users_file);

        return $content_with_keydir_and_users_file;
    }

    private function executeCommand($insecure = true) {
        $command_tester = new CommandTester($this->command);
        $command_tester->execute(
            array(
                '--insecure' => $insecure
            )
        );

        return $command_tester;
    }

    public function testItFailsIfThereIsNoConfigFile() {
        $this->command = new MembershipCacheCommand(
            new Client(),
            new ConfigurationLoader(),
            '/path/to/inexistant/file.ini'
        );

        $command_tester = $this->executeCommand();
        $this->assertEquals('', $command_tester->getDisplay());
        $this->assertEquals(1, $command_tester->getStatusCode());
    }

    public function testItFailsIfTheConfigFileIsNotValid() {
        file_put_contents($this->config_file, "toto = titi");

        $command_tester = $this->executeCommand();
        $this->assertEquals('', $command_tester->getDisplay());
        $this->assertEquals(1, $command_tester->getStatusCode());
    }

    public function testItAsksForANewTokenIfTheFirstRequestFailsFor401() {
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);

        $this->executeCommand();
        $requests = $this->plugin->getReceivedRequests();
        $second_request = $requests[1];

        $this->assertEquals(
            'https://tuleap.example.com/api/v1/tokens',
            $second_request->getUrl()
        );
        $this->assertEquals('POST',     $second_request->getMethod());
        $this->assertEquals('admin',    $second_request->getPostField('username'));
        $this->assertEquals('adminpwd', $second_request->getPostField('password'));
    }

    public function testItExitsWithStatusCodeIfPasswordIsWrong() {
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse(new Response(401));

        $command_tester = $this->executeCommand();
        $this->assertEquals('', $command_tester->getDisplay());
        $this->assertEquals(1, $command_tester->getStatusCode());
    }

    public function testItExitsWithStatusCodeIfCannotTrustCertificate() {
        $exception = new CurlException();
        $exception->setError('whatever', CURLE_SSL_CACERT);
        $this->plugin->addException($exception);

        $command_tester = $this->executeCommand(false);
        $this->assertEquals(1, $command_tester->getStatusCode());
    }

    public function testItRetriesWithTheNewToken() {
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);
        $this->plugin->addResponse($this->users_memberships_response_01);
        $this->plugin->addResponse($this->users_memberships_response_02);

        $this->executeCommand();
        $requests = $this->plugin->getReceivedRequests();
        /* @var $second_attempt \Guzzle\Http\Message\Request */
        $second_attempt = $requests[2];

        $this->assertEquals('666', (string)$second_attempt->getHeader('X-Auth-UserId'));
        $this->assertEquals('new_token', (string)$second_attempt->getHeader('X-Auth-Token'));
    }

    public function testItStoreTheTokenForFutureUse() {
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);
        $this->plugin->addResponse($this->users_memberships_response_01);
        $this->plugin->addResponse($this->users_memberships_response_02);

        $this->executeCommand();

        $expected_content = '{"user_id":666,"token":"new_token","uri":"tokens\/new_token"}';
        $this->assertEquals($expected_content, file_get_contents($this->token_file));
    }

    public function testItUsesTheStoredToken() {
        file_put_contents($this->token_file, '{"user_id":666,"token":"new_token","uri":"tokens\/new_token"}');
        $this->plugin->addResponse($this->users_memberships_response_01);
        $this->plugin->addResponse($this->users_memberships_response_02);

        $this->executeCommand();
        $requests = $this->plugin->getReceivedRequests();
        /* @var $second_attempt \Guzzle\Http\Message\Request */
        $request = $requests[0];

        $this->assertEquals('666', (string)$request->getHeader('X-Auth-UserId'));
        $this->assertEquals('new_token', (string)$request->getHeader('X-Auth-Token'));
    }

    public function testItDoesNotLoop() {
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);
        $this->plugin->addResponse($this->invalid_token_response);
        $this->plugin->addResponse($this->new_token_response);
        $this->plugin->addResponse($this->invalid_token_response);
        //… (loop that we would like to avoid)

        $command_tester = $this->executeCommand();
        $this->assertEquals(1, $command_tester->getStatusCode());
        $this->assertCount(3, $this->plugin->getReceivedRequests());
    }

    public function testItCreatesAMembershipCache() {
        $this->plugin->addResponse($this->users_memberships_response_01);
        $this->plugin->addResponse($this->users_memberships_response_02);

        $this->executeCommand();

        $this->assertEquals(
            json_decode($this->users_memberships_response_content),
            json_decode(file_get_contents($this->membership_cache))
        );
    }

    public function testItExitsWithStatusCodeIfCannotFindPaginationHeader() {
        $this->plugin->addResponse($this->users_memberships_response_03);

        $command_tester = $this->executeCommand();
        $this->assertEquals(1, $command_tester->getStatusCode());
    }
}
