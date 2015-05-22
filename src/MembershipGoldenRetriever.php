<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Psr\Log\LoggerInterface;

class MembershipGoldenRetriever {

    const NB_MAX_ATTEMPT = 2;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     *  @var ServerConfiguration
     */
    private $server_configuration;

    /**
     * @var ClientConfiguration
     */
    private $client_configuration;

    /**
     * @var GitoliteUserFinder
     */
    private $finder;

    /**
     * @var int
     */
    private $nb_attempt = 0;

    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $user_id;

    public function __construct(
        Client $client,
        ServerConfiguration $server_config,
        ClientConfiguration $client_configuration,
        LoggerInterface $logger,
        GitoliteUserFinder $finder
    ) {
        $this->server_configuration = $server_config;
        $this->client_configuration = $client_configuration;

        $this->finder = $finder;
        $this->client = $client;
        $this->logger = $logger;
        $this->setTokenFromCache();
    }

    public function generateCache(InputInterface $input, OutputInterface $output) {
        $users = $this->finder->getUserFromGitoliteKeydirPath(
            $this->client_configuration->keydir_path
        );

        $limit  = 1000;
        $offset = 0;
        $max    = 0;

        try {
            $this->nb_attempt++;

            $users_groups = array();
            do {
                $response     = $this->getAllMembershipInformationForCache($input, $users, $limit, $offset);
                $users_groups = array_merge($users_groups, $response->json());

                $headers = $response->getHeader('X-PAGINATION-SIZE')->toArray();
                if (! isset($headers[0])) {
                    throw new Exception('Header X-PAGINATION-SIZE not found');
                }

                $max = (int) $headers[0];
                $offset += $limit;
            } while ($offset < $max);

            $this->generateMembershipCache($users_groups);

        } catch (ClientErrorResponseException $exception) {
            $status_code = $exception->getResponse()->getStatusCode();
            if ($status_code == 401 && $this->nb_attempt < self::NB_MAX_ATTEMPT) {
                $this->generateNewToken($input, $output);
                return $this->generateCache($input, $output);
            } else {
                throw $exception;
            }
        }

        return 0;
    }

    private function getAllMembershipInformationForCache(
        InputInterface $input,
        $users,
        $limit,
        $offset
    ) {
        $url = "/api/v1/users_memberships?limit=$limit&offset=$offset&users=$users";
        $this->logger->debug('GET '. $url);
        $response = $this->client->get(
            $this->server_configuration->host . $url,
            $this->getHeadersForRESTRequests(),
            $this->getOptionsForRESTRequests($input)
        )->send();
        $this->logger->debug('Raw response from the server: '. $response->getBody());

        return $response;
    }

    private function generateMembershipCache(array $users_groups) {
        file_put_contents(
            $this->client_configuration->membership_cache,
            json_encode($users_groups)
        );
    }

    public function displayMembership(InputInterface $input, OutputInterface $output) {
        $username = $this->getUsername($input);

        try {
            if ($this->client_configuration->use_cache) {
                $user_groups = $this->getMembershipInformationFromCache($username);
            } else {
                $this->nb_attempt++;
                $user_groups = $this->getMembershipInformation($input, $username);
            }

            $output->writeln(implode(' ', $user_groups));
        } catch (ClientErrorResponseException $exception) {
            $status_code = $exception->getResponse()->getStatusCode();
            if ($status_code == 401 && $this->nb_attempt < self::NB_MAX_ATTEMPT) {
                $this->generateNewToken($input, $output);
                return $this->displayMembership($input, $output);
            } else {
                throw $exception;
            }
        } catch (UserNotFoundException $exception) {
            $this->logger->debug('User does not exist.');
        }
        return 0;
    }

    private function getMembershipInformationFromCache($username) {
            $memberships = file_get_contents($this->client_configuration->membership_cache);

            foreach (json_decode($memberships) as $user) {
                if ($user->username == $username) {
                    return $user->user_groups;
                }
            }

            throw new UserNotFoundException();
    }

    private function getMembershipInformation(InputInterface $input, $username) {
        $user_info = $this->getUserInformation($input, $username);
        $user_id   = $user_info['id'];

        return $this->getMemberships($input, $user_id);
    }

    private function getMemberships(InputInterface $input, $user_id) {
        $url = '/api/v1/users/'. $user_id .'/membership';
        $this->logger->debug('GET '. $url);
        $response = $this->client->get(
            $this->server_configuration->host . $url,
            $this->getHeadersForRESTRequests(),
            $this->getOptionsForRESTRequests($input)
        )->send();
        $this->logger->debug('Raw response from the server: '. $response->getBody());

        return $response->json();
    }

    private function getUserInformation(InputInterface $input, $username) {
        $url = '/api/v1/users?query='. urlencode(json_encode(array('username' => $username)));
        $this->logger->debug('GET '. $url);
        $response = $this->client->get(
            $this->server_configuration->host . $url,
            $this->getHeadersForRESTRequests(),
            $this->getOptionsForRESTRequests($input)
        )->send();
        $this->logger->debug('Raw response from the server: '. $response->getBody());

        $json = $response->json();
        if (count($json) === 0) {
            throw new UserNotFoundException();
        }

        return $json[0];
    }

    private function getUsername(InputInterface $input) {
        $username = $input->getArgument('username');
        $this->logger->debug('Retrieving membership information for "'. $username .'"');

        return $username;
    }

    private function generateNewToken(InputInterface $input, OutputInterface $output) {
        $this->logger->debug('Token is expired. Generating a new one.');
        $response = $this->client->post(
            $this->server_configuration->host. '/api/v1/tokens',
            $this->getHeadersForRESTTokenRequests(),
            array(
                'username' => $this->server_configuration->user,
                'password' => $this->server_configuration->password
            ),
            $this->getOptionsForRESTRequests($input)
        )->send();

        $json = $response->json();
        $this->setTokenFromJson($json);

        $this->storeTokenForFutureUse($json);
    }

    private function setTokenFromCache() {
        if (! is_file($this->client_configuration->cache)) {
            return;
        }

        $this->logger->debug('Reading token from cache.');
        $json = json_decode(file_get_contents($this->client_configuration->cache), 1);
        if ($json) {
            $this->setTokenFromJson($json);
        }
    }

    private function setTokenFromJson($json) {
        $this->token   = $json['token'];
        $this->user_id = $json['user_id'];
    }

    private function storeTokenForFutureUse($token_json) {
        $this->logger->debug('Saving token to cache.');
        file_put_contents($this->client_configuration->cache, json_encode($token_json));
    }

    private function getHeadersForRESTTokenRequests() {
        $headers = array();
        $this->addContentTypeJsonHeader($headers);

        return $headers;
    }

    private function getHeadersForRESTRequests() {
        $headers = array();
        $this->addContentTypeJsonHeader($headers);
        $this->addAuthHeaders($headers);

        return $headers;
    }

    private function getOptionsForRESTRequests(InputInterface $input) {
        $options = array();

        if ($input->getOption('insecure')) {
            $this->addInsecureOptions($options);
        }

        return $options;
    }

    private function addContentTypeJsonHeader(array &$headers) {
        $headers['Content-Type'] = 'application/json';
    }

    private function addAuthHeaders(array &$headers) {
        $headers['X-Auth-Token']  = $this->token;
        $headers['X-Auth-UserId'] = $this->user_id;
    }

    private function addInsecureOptions(array &$options) {
        $options['verify'] = false;
    }
}