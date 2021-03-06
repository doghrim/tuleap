<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 */

class AgileDashboard_KanbanFactory {

    /** @var AgileDashboard_KanbanDao */
    private $dao;

    public function __construct(AgileDashboard_KanbanDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return AgileDashboard_Kanban[]
     */
    public function getListOfKanbansForProject($project_id) {
        $rows    = $this->dao->getKanbansForProject($project_id);
        $kanbans = array();

        foreach ($rows as $kanban_data) {
            $kanbans[] = $this->instantiateFromRow($kanban_data);
        }

        return $kanbans;
    }

    public function getKanban($tracker_id) {
        $row = $this->dao->getKanbanByTrackerId($tracker_id)->getRow();

        return $this->instantiateFromRow($row);
    }

    /**
     * @return int[]
     */
    public function getKanbanTrackerIds($project_id) {
        $rows               = $this->dao->getKanbansForProject($project_id);
        $kanban_tracker_ids = array();

        foreach ($rows as $kanban_data) {
            $kanban_tracker_ids[] = $kanban_data['tracker_id'];
        }

        return $kanban_tracker_ids;
    }

    private function instantiateFromRow($kanban_data) {
        return new AgileDashboard_Kanban(
            $kanban_data['name'],
            $kanban_data['tracker_id'],
            $kanban_data['group_id']
        );
    }
}
