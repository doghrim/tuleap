<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 *
 * Rename project in gitolite configuration
 */
 
require_once 'pre.php';
require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';
require_once dirname(__FILE__).'/../include/GitRepositoryUrlManager.class.php';

if ($argc !== 3) {
    echo "Usage: ".$argv[0]." oldname newname".PHP_EOL;
    exit(1);
}

/* @var $git_plugin GitPlugin */
$git_plugin  = PluginManager::instance()->getPluginByName('git');
$url_manager = new Git_GitRepositoryUrlManager($git_plugin);
$driver      = new Git_GitoliteDriver(
    $git_plugin->getLogger(),
    $git_plugin->getGitSystemEventManager(),
    $url_manager
);
if ($driver->renameProject($argv[1], $argv[2])) {
    echo "Rename done!\n";
    exit(0);
} else {
    echo "*** ERROR: Fail to rename project ".$argv[1]." into ".$argv[2]." gitolite repositories".PHP_EOL;
    exit(1);
}

?>