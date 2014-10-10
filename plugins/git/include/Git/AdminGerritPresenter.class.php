<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Git_AdminGerritPresenter extends Git_AdminPresenter {

    public $manage_gerrit = true;

    public $gerrit_active = 'active';

    public $list_of_servers;

    public $can_use_gerrit_2_8;

    public $btn_submit;

    public function __construct($title, CSRFSynchronizerToken $csrf, array $list_of_gerrits) {
        parent::__construct($title, $csrf);

        $this->list_of_servers    = $list_of_gerrits;
        $this->can_use_gerrit_2_8 = server_is_php_version_equal_or_greater_than_53();
        $this->btn_submit         = $GLOBALS['Language']->getText('global', 'btn_submit');
    }
}