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

namespace TuleapClient\Gitolite;

class ClientConfiguration {

    /** @var string */
    public $cache;

    /** @var string */
    public $keydir_path;

    /** @var string */
    public $membership_cache;

    /** @var boolean */
    public $use_cache;

    public function __construct($cache, $keydir_path, $membership_cache, $use_cache) {
        $this->cache            = $cache;
        $this->keydir_path      = $keydir_path;
        $this->membership_cache = $membership_cache;
        $this->use_cache        = (bool) $use_cache;
    }
}
