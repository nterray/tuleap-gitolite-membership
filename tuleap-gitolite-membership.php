#!/usr/bin/env php
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

require 'vendor/autoload.php';

use TuleapClient\Gitolite\MembershipApplication;
use TuleapClient\Gitolite\MembershipCommand;
use TuleapClient\Gitolite\ConfigurationLoader;
use Guzzle\Http\Client;

$membership = new MembershipApplication('Tuleap/Gitolite Membership', '0.1');
$membership->add(
    new MembershipCommand(
        new Client(),
        new ConfigurationLoader(),
        '/etc/tuleap-gitolite-membership.ini'
    )
);
$membership->run();