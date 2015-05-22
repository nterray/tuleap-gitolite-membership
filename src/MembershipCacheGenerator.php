<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class MembershipCacheGenerator {

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
     * @var RESTHandler
     */
    private $rest_handler;

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

        $this->rest_handler = new RESTHandler(
            $client,
            $server_config,
            $client_configuration,
            $logger
        );

        $this->rest_handler->setTokenFromCache();
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

                $pagination_header = $response->getHeader('X-PAGINATION-SIZE');
                if ($pagination_header === null) {
                    throw new PaginationHeaderNotFoundException('Header X-PAGINATION-SIZE not found');
                }

                $pagination_header_value = $pagination_header->toArray();

                $max     = (int) $pagination_header_value[0];
                $offset += $limit;
            } while ($offset < $max);

            $this->generateMembershipCache($users_groups);

        } catch (ClientErrorResponseException $exception) {
            $status_code = $exception->getResponse()->getStatusCode();
            if ($status_code == 401 && $this->nb_attempt < self::NB_MAX_ATTEMPT) {
                $this->rest_handler->generateNewToken($input);
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
            $this->rest_handler->getHeadersForRESTRequests(),
            $this->rest_handler->getOptionsForRESTRequests($input)
        )->send();
        $this->logger->debug('Raw response from the server: '. $response->getBody());

        return $response;
    }

    private function generateMembershipCache(array $users_groups) {
        $hash_map = array();

        foreach ($users_groups as $user_groups) {
            $hash_map[$user_groups['username']] = $user_groups['user_groups'];
        }

        file_put_contents(
            $this->client_configuration->membership_cache,
            json_encode($hash_map)
        );
   }
}