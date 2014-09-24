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
use Symfony\Component\Console\Input\ArrayInput;

class MembershipApplicationTest extends \PHPUnit_Framework_TestCase {

    public function testItIsASingleCommandApplication() {
        $app = new MembershipApplication('le config file');

        $input_definition = $app->getDefinition();

        $this->assertEmpty($input_definition->getArguments());
    }

    public function testTheCommandNameIsTheSameAsTheMembershipCommand() {
        $app = new MembershipApplicationExposedForTest('le config file');

        $input = new ArrayInput(array('name' => 'foo'));

        $this->assertEquals(MembershipCommand::NAME, $app->getCommandName($input));
    }
}

class MembershipApplicationExposedForTest extends MembershipApplication {

    public function getCommandName(InputInterface $input) {
        return parent::getCommandName($input);
    }
}