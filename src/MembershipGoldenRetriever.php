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
     * @var int
     */
    private $nb_attempt = 0;

    /**
     * @var RESTHandler
     */
    private $rest_handler;

    public function __construct(
        Client $client,
        ServerConfiguration $server_config,
        ClientConfiguration $client_configuration,
        LoggerInterface $logger
    ) {
        $this->server_configuration = $server_config;
        $this->client_configuration = $client_configuration;

        $this->client = $client;
        $this->logger = $logger;

        $this->rest_handler = new RESTHandler(
            $client,
            $server_config,
            $client_configuration,
            $logger
        );

        $this->rest_handler->setTokenFromCache();
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
                $this->rest_handler->generateNewToken($input);
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
        $this->logger->debug("Retrieving $username membership from cache");
        $memberships = json_decode(
            file_get_contents($this->client_configuration->membership_cache),
            true
        );

        if (! isset($memberships[$username])) {
            throw new UserNotFoundException();
        }

        return $memberships[$username];
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
            $this->rest_handler->getHeadersForRESTRequests(),
            $this->rest_handler->getOptionsForRESTRequests($input)
        )->send();
        $this->logger->debug('Raw response from the server: '. $response->getBody());

        return $response->json();
    }

    private function getUserInformation(InputInterface $input, $username) {
        $url = '/api/v1/users?query='. urlencode(json_encode(array('username' => $username)));
        $this->logger->debug('GET '. $url);
        $response = $this->client->get(
            $this->server_configuration->host . $url,
            $this->rest_handler->getHeadersForRESTRequests(),
            $this->rest_handler->getOptionsForRESTRequests($input)
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
}