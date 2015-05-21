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

class GitoliteUserFinderTest extends \PHPUnit_Framework_TestCase {

    /** @var string */
    private $keydir_path;

    /** @var GitoliteUserFinder */
    private $finder;

    public function setUp() {
        parent::setUp();

        $this->keydir_path = __DIR__ .'/_fixtures/keydir';
        $this->finder      = new GitoliteUserFinder();
    }

    public function testItReturnsUniqueUsernamesFromKeydirPath() {
        $expect_result = "user01,user02,user03,user04";
        $result        = $this->finder->getUserFromGitoliteKeydirPath(
            $this->keydir_path
        );

        $this->assertEquals($result, $expect_result);
    }

}