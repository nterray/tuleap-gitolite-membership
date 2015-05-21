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

use DirectoryIterator;

class GitoliteUserFinder {

    const EXCLUDED_USER_NAMES_PATTERN = '/^forge__/';

    /**
     * @return string
     */
    public function getUserFromGitoliteKeydirPath($keydir_path) {
        $key_files = new DirectoryIterator($keydir_path);
        $users     = array();

        foreach ($key_files as $file) {
            if ($file->isFile()) {
                $username = $this->getFormattedUsernameByFilename($file->getBasename());

                if ($this->userCanBeAdded($users, $username)) {
                    $users[] = $username;
                }
            }
        }

        sort($users);
        return implode(',', $users);
    }

    private function userCanBeAdded(array $users, $username) {
        return ! in_array($username, $users) && ! preg_match(self::EXCLUDED_USER_NAMES_PATTERN, $username);
    }

    private function getFormattedUsernameByFilename($filename) {
        return trim(substr($filename, 0, strpos($filename, '@')));
    }
}