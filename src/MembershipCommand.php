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

// TODO: check malformed json ?

namespace TuleapClient\Gitolite;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Exception;

class MembershipCommand extends Command {

    const NAME = 'tuleap-gitolite-membership';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $config_file;

    /**
     * @var ConfigurationLoader
     */
    private $loader;

    public function __construct(
        Client $client,
        ConfigurationLoader $loader,
        $config_file
    ) {
        parent::__construct(self::NAME);

        $this->client      = $client;
        $this->loader      = $loader;
        $this->config_file = $config_file;
    }

    protected function configure() {
        $this->setName(self::NAME)
            ->setDescription('Retrieve the membership of a given user')
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                'The user to retrieve membership information'
            )
            ->addOption(
                'insecure',
                'k',
                InputOption::VALUE_NONE,
                'Allow connections to SSL sites without certs'
            )
            ->addOption(
                'create-cache',
                'c',
                InputOption::VALUE_NONE,
                'Create a user membership cache'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $logger = new ConsoleLogger($output);

        try {
            $server_config = $this->loader->getServerConfiguration($this->config_file);
            $client_config = $this->loader->getClientConfiguration($this->config_file);
            $finder        = new GitoliteUserFinder();

            $membership = new MembershipGoldenRetriever(
                $this->client,
                $server_config,
                $client_config,
                $logger,
                $finder
            );

            if ($input->getOption('insecure')) {
                $logger->debug('Allowing connections to SSL sites without certs');
            }


            if ($input->getArgument('username')) {
                return $membership->displayMembership($input, $output);
            } else if($input->getOption('create-cache')) {
                return $membership->generateCache($input, $output);
            }

        } catch (CurlException $exception) {
            if ($exception->getErrorNo() == CURLE_SSL_CACERT) {
                $logger->debug('Peer certificate cannot be authenticated with known CA certificates.');
                $logger->debug('If you trust the server, please consider using --insecure option.');
            } else {
                $logger->debug($exception->getMessage());
            }
        } catch (Exception $exception) {
            $logger->debug($exception->getMessage());
        }

        return 1;
    }
}