<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace TuleapClient\Gitolite;

use Symfony\Component\Console\Input\InputInterface;
use Guzzle\Http\Client;
use Psr\Log\LoggerInterface;

class RESTHandler {

    /**
     * @var string
     */
    private $token;

    /**
     * @var int
     */
    private $user_id;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ServerConfiguration
     */
    private $server_configuration;

    /**
     * @var ClientConfiguration
     */
    private $client_configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Client $client,
        ServerConfiguration $server_config,
        ClientConfiguration $client_configuration,
        LoggerInterface $logger
    ) {
        $this->client               = $client;
        $this->server_configuration = $server_config;
        $this->client_configuration = $client_configuration;
        $this->logger               = $logger;
    }

    public function generateNewToken(InputInterface $input) {
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

    public function setTokenFromCache() {
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

    public function getHeadersForRESTRequests() {
        $headers = array();
        $this->addContentTypeJsonHeader($headers);
        $this->addAuthHeaders($headers);

        return $headers;
    }

    public function getOptionsForRESTRequests(InputInterface $input) {
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