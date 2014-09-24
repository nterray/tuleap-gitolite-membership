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

class ConfigurationLoader {

    private $expected_variables = array(
        'server' => array('host', 'user', 'password'),
        'client' => array('cache')
    );

    /** @return ServerConfiguration */
    public function getServerConfiguration($filename) {
        $config = $this->parseConfiguration($filename);
        $this->checkVarsAreDefined(
            $config,
            'server',
            array('host', 'user', 'password'),
            $filename
        );

        return new ServerConfiguration(
            $config['server']['host'],
            $config['server']['user'],
            $config['server']['password']
        );
    }

    /** @return ClientConfiguration */
    public function getClientConfiguration($filename) {
        $config = $this->parseConfiguration($filename);
        $this->checkVarsAreDefined(
            $config,
            'client',
            array('cache'),
            $filename
        );

        return new ClientConfiguration(
            $config['client']['cache']
        );
    }

    private function parseConfiguration($filename) {
        if (! file_exists($filename)) {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $filename));
        }

        $parsed_content = @parse_ini_file($filename, true);
        if (! $parsed_content) {
            throw new \RuntimeException(sprintf('The file "%s" is not valid.', $filename));
        }

        return $parsed_content;
    }

    private function checkVarsAreDefined($parsed_content, $filename) {
        foreach ($this->expected_variables as $section => $expected_variables) {
            foreach ($expected_variables as $expected_variable) {
                if (! isset($parsed_content[$section][$expected_variable])) {
                    throw new \RuntimeException(sprintf('The file "%s" is invalid.', $filename));
                }
            }
        }
    }
}