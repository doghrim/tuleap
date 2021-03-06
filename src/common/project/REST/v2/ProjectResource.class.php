<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 */

namespace Tuleap\Project\REST\v2;

use \ProjectManager;
use \UserManager;
use \Project;
use \EventManager;
use \Event;
use \Tuleap\REST\Header;
use \Tuleap\REST\ProjectAuthorization;
use \URLVerification;

/**
 * Wrapper for project related REST methods
 */

class ProjectResource {

    const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct() {
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
    }

    /**
     * @throws 403
     * @throws 404
     *
     * @return Project
     */
    private function getProjectForUser($id) {
        $project = $this->project_manager->getProject($id);
        $user    = $this->user_manager->getCurrentUser();

        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());
        return $project;
    }

    /**
     * Get backlog
     *
     * Get the backlog items that can be planned in a top-milestone
     *
     * @url GET {id}/backlog
     *
     * @param int $id     Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\AgileDashboard\REST\v2\BacklogRepresentation}
     *
     * @throws 406
     */
    protected function getBacklog($id, $limit = 10, $offset = 0) {
        $backlog_items = $this->backlogItems($id, $limit, $offset, Event::REST_GET_PROJECT_BACKLOG);
        $this->sendAllowHeadersForBacklog();

        return $backlog_items;
    }

    /**
     * @url OPTIONS {id}/backlog
     *
     * @param int $id Id of the project
     */
    public function optionsBacklog($id) {
        $this->sendAllowHeadersForBacklog();
    }


    private function backlogItems($id, $limit, $offset, $event) {
        $project = $this->getProjectForUser($id);
        $result  = array();

        EventManager::instance()->processEvent(
            $event,
            array(
                'version' => 'v2',
                'project' => $project,
                'limit'   => $limit,
                'offset'  => $offset,
                'result'  => &$result,
            )
        );

        return $result;
    }

    private function sendAllowHeadersForBacklog() {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
