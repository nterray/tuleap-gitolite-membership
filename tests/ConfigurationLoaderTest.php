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

class ConfigurationLoaderTest extends \PHPUnit_Framework_TestCase {

    const WELL_FORMATTED_FILE = <<<EOS
[server]
host = http://tuleap.example.com
user = admin
password = adminpwd
[client]
cache = /path/to/file
keydir_path = /path/to/keydir
membership_cache = /path/to/membership_cache

EOS;

    private $config_file;

    protected function setUp() {
        parent::setUp();
        $this->fixtures_dir = __DIR__ .'/_fixtures';
        $this->config_file = $this->fixtures_dir .'/config.ini';
    }

    protected function tearDown() {
        if (is_file($this->config_file)) {
            unlink($this->config_file);

        }
        parent::tearDown();
    }

    public function testItParsesTheClientConfiguration() {
        file_put_contents($this->config_file, self::WELL_FORMATTED_FILE);

        $loader = new ConfigurationLoader();
        $config = $loader->getClientConfiguration($this->config_file);

        $this->assertEquals('/path/to/file', $config->cache);
    }

    public function testItParsesTheServerConfiguration() {
        file_put_contents($this->config_file, self::WELL_FORMATTED_FILE);

        $loader = new ConfigurationLoader();
        $config = $loader->getServerConfiguration($this->config_file);

        $this->assertEquals('http://tuleap.example.com', $config->host);
        $this->assertEquals('admin', $config->user);
        $this->assertEquals('adminpwd', $config->password);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotExist() {
        $loader = new ConfigurationLoader();
        $loader->getServerConfiguration('/path/to/inexistant/file');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileIsNotWellFormed() {
        file_put_contents(
            $this->config_file,
            self::WELL_FORMATTED_FILE . "\n="
        );
        $loader = new ConfigurationLoader();
        $loader->getServerConfiguration($this->config_file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotContainServerHost() {
        file_put_contents(
            $this->config_file,
            preg_replace('/\nhost\s*=.*\n/', "\n", self::WELL_FORMATTED_FILE)
        );
        $loader = new ConfigurationLoader();
        $loader->getServerConfiguration($this->config_file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotContainServerUser() {
        file_put_contents(
            $this->config_file,
            preg_replace('/\nuser\s*=.*\n/', "\n", self::WELL_FORMATTED_FILE)
        );
        $loader = new ConfigurationLoader();
        $loader->getServerConfiguration($this->config_file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotContainServerPassword() {
        file_put_contents(
            $this->config_file,
            preg_replace('/\npassword\s*=.*\n/', "\n", self::WELL_FORMATTED_FILE)
        );
        $loader = new ConfigurationLoader();
        $loader->getServerConfiguration($this->config_file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotContainServerSection() {
        file_put_contents(
            $this->config_file,
            preg_replace('/^\[server\]\n/', "\n", self::WELL_FORMATTED_FILE)
        );
        $loader = new ConfigurationLoader();
        $loader->getServerConfiguration($this->config_file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotContainClientCache() {
        file_put_contents(
            $this->config_file,
            preg_replace('/\ncache\s*=.*\n/', "\n", self::WELL_FORMATTED_FILE)
        );
        $loader = new ConfigurationLoader();
        $loader->getClientConfiguration($this->config_file);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testItRaiseExceptionIfFileDoesNotContainClientSection() {
        file_put_contents(
            $this->config_file,
            preg_replace('/\n\[client\]\n/', "\n", self::WELL_FORMATTED_FILE)
        );
        $loader = new ConfigurationLoader();
        $loader->getClientConfiguration($this->config_file);
    }
}
