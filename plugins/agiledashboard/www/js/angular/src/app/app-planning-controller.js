(function () {
    angular
        .module('planning')
        .controller('PlanningCtrl', PlanningCtrl);

    PlanningCtrl.$inject = [
        '$scope',
        'SharedPropertiesService',
        'BacklogItemService',
        'MilestoneService',
        'ProjectService',
        'DroppedService'
    ];

    function PlanningCtrl($scope, SharedPropertiesService, BacklogItemService, MilestoneService, ProjectService, DroppedService) {

        var project_id                  = SharedPropertiesService.getProjectId(),
            milestone_id                = SharedPropertiesService.getMilestoneId(),
            pagination_limit            = 50,
            pagination_offset           = 0,
            show_closed_milestone_items = true;

        _.extend($scope, {
            rest_error_occured         : false,
            rest_error                 : "",
            backlog_items              : [],
            milestones                 : [],
            backlog                    : {},
            loading_backlog_items      : true,
            loading_milestones         : true,
            toggle                     : toggle,
            showChildren               : showChildren,
            toggleClosedMilestoneItems : toggleClosedMilestoneItems,
            canShowBacklogItem         : canShowBacklogItem,
            generateMilestoneLinkUrl   : generateMilestoneLinkUrl
        });

        $scope.treeOptions = {
            accept : isItemDroppable,
            dropped: dropped
        };

        loadBacklog();
        displayBacklogItems();
        displayMilestones();

        function loadBacklog() {
            if (! angular.isDefined(milestone_id)) {
                $scope.backlog = {
                    rest_base_route : 'projects',
                    rest_route_id   : project_id,
                    accepted_types  : ''
                };
                fetchProjectBacklogAcceptedTypes(project_id);
            } else {
                MilestoneService.getMilestone(milestone_id).then(function(milestone) {
                    $scope.backlog = {
                        rest_base_route : 'milestones',
                        rest_route_id   : milestone_id,
                        accepted_types  : milestone.results.accepted_types
                    };
                });
            }
        }

        function fetchProjectBacklogAcceptedTypes(project_id) {
            return  ProjectService.getProjectBacklogAcceptedTypes(project_id).then(function(data) {
                $scope.backlog.accepted_types = data.results;
            });
        }

        function displayBacklogItems() {
            if (! angular.isDefined(milestone_id)) {
                fetchProjectBacklogItems(project_id, pagination_limit, pagination_offset);
            } else {
                fetchMilestoneBacklogItems(milestone_id, pagination_limit, pagination_offset);
            }
        }

        function fetchProjectBacklogItems(project_id, limit, offset) {
            return BacklogItemService.getProjectBacklogItems(project_id, limit, offset).then(function(data) {
                $scope.backlog_items = $scope.backlog_items.concat(data.results);

                if ($scope.backlog_items.length < data.total) {
                    fetchProjectBacklogItems(project_id, limit, offset + limit);
                } else {
                    $scope.loading_backlog_items = false;
                }
            });
        }

        function fetchMilestoneBacklogItems(milestone_id, limit, offset) {
            return BacklogItemService.getMilestoneBacklogItems(milestone_id, limit, offset).then(function(data) {
                $scope.backlog_items = $scope.backlog_items.concat(data.results);

                if ($scope.backlog_items.length < data.total) {
                    fetchMilestoneBacklogItems(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_backlog_items = false;
                }
            });
        }

        function displayMilestones() {
            if (! angular.isDefined(milestone_id)) {
                fetchMilestones(project_id, pagination_limit, pagination_offset);
            } else {
                fetchSubMilestones(milestone_id, pagination_limit, pagination_offset);
            }
        }

        function fetchMilestones(project_id, limit, offset) {
            return MilestoneService.getMilestones(project_id, limit, offset).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ($scope.milestones.length < data.total) {
                    fetchMilestones(project_id, limit, offset + limit);
                } else {
                    $scope.loading_milestones = false;
                }
            });
        }

        function fetchSubMilestones(milestone_id, limit, offset) {
            return MilestoneService.getSubMilestones(milestone_id, limit, offset).then(function(data) {
                $scope.milestones = $scope.milestones.concat(data.results);

                if ($scope.milestones.length < data.total) {
                    fetchSubMilestones(milestone_id, limit, offset + limit);
                } else {
                    $scope.loading_milestones = false;
                }
            });
        }

        function generateMilestoneLinkUrl(milestone, pane) {
            return '?group_id=' + project_id + '&planning_id=' + milestone.planning.id + '&action=show&aid=' + milestone.id + '&pane=' + pane;
        }

        function toggle(milestone) {
            if (! milestone.alreadyLoaded && milestone.content.length === 0) {
                milestone.getContent();
            }

            if (milestone.collapsed) {
                return milestone.collapsed = false;
            }

            return milestone.collapsed = true;
        }

        function showChildren(scope, backlog_item) {
            scope.toggle();

            if (backlog_item.has_children && ! backlog_item.children.loaded) {
                backlog_item.loading = true;
                fetchBacklogItemChildren(backlog_item, pagination_limit, pagination_offset);
            }
        }

        function fetchBacklogItemChildren(backlog_item, limit, offset) {
            return BacklogItemService.getBacklogItemChildren(backlog_item.id, limit, offset).then(function(data) {
                backlog_item.children.data = backlog_item.children.data.concat(data.results);

                if (backlog_item.children.data.length < data.total) {
                    fetchBacklogItemChildren(backlog_item, limit, offset + limit);

                } else {
                    backlog_item.loading         = false;
                    backlog_item.children.loaded = true;
                }
            });
        }

        function toggleClosedMilestoneItems() {
            show_closed_milestone_items = (show_closed_milestone_items === true) ? false : true;
        }

        function canShowBacklogItem(backlog_item) {
            if (typeof backlog_item.isOpen === 'function') {
                return backlog_item.isOpen() || show_closed_milestone_items;
            }

            return true;
        }

        function isItemDroppable(sourceNodeScope, destNodesScope, destIndex) {
            if (typeof destNodesScope.$element.attr === 'undefined') {
                return;
            }

            var accepted     = destNodesScope.$element.attr('data-accept').split('|');
            var type         = sourceNodeScope.$element.attr('data-type');
            var is_droppable = false;

            for (var i = 0; i < accepted.length; i++) {
                if (accepted[i] === type) {
                    is_droppable = true;
                    continue;
                }
            }

            return is_droppable;
        }

        function dropped(event) {
            var source_list_element = event.source.nodesScope.$element,
                dest_list_element   = event.dest.nodesScope.$element,
                dropped_item_id     = event.source.nodeScope.$modelValue.id,
                compared_to         = DroppedService.defineComparedTo(event.dest.nodesScope.$modelValue, event.dest.index);

            saveChange();
            updateSubmilestonesInitialEffort();
            collapseSourceParentIfNeeded();
            removeFromDestinationIfNeeded();

            function saveChange() {
                switch(true) {
                    case movedInTheSameList():
                        if (source_list_element.hasClass('backlog')) {
                            DroppedService
                                .reorderBacklog(dropped_item_id, compared_to, $scope.backlog)
                                .then(function() {}, catchError);

                        } else if (source_list_element.hasClass('submilestone')) {
                            DroppedService
                                .reorderSubmilestone(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-submilestone-id'), 10))
                                .then(function() {}, catchError);

                        } else if (source_list_element.hasClass('backlog-item-children')) {
                            DroppedService
                                .reorderBacklogItemChildren(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-backlog-item-id'), 10))
                                .then(function() {}, catchError);
                        }
                        break;

                    case movedFromBacklogToSubmilestone():
                        DroppedService
                            .moveFromBacklogToSubmilestone(dropped_item_id, compared_to, parseInt(dest_list_element.attr('data-submilestone-id'), 10))
                            .then(function() {}, catchError);
                        break;

                    case movedFromChildrenToChildren():
                        DroppedService
                            .moveFromChildrenToChildren(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-backlog-item-id'), 10),
                                parseInt(dest_list_element.attr('data-backlog-item-id'), 10)
                            )
                            .then(function() {}, catchError);
                        break;

                    case movedFromSubmilestoneToBacklog():
                        DroppedService
                            .moveFromSubmilestoneToBacklog(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-submilestone-id'), 10),
                                $scope.backlog
                            )
                            .then(function() {}, catchError);
                        break;

                    case movedFromOneSubmilestoneToAnother():
                        DroppedService
                            .moveFromSubmilestoneToSubmilestone(
                                dropped_item_id,
                                compared_to,
                                parseInt(source_list_element.attr('data-submilestone-id'), 10),
                                parseInt(dest_list_element.attr('data-submilestone-id'), 10)
                            )
                            .then(function() {}, catchError);
                        break;
                }

                function catchError(data) {
                    $scope.rest_error_occured = true;
                    $scope.rest_error         = data.data.error.code + ' ' + data.data.error.message;
                }

                function movedInTheSameList() {
                    return event.source.nodesScope.$id === event.dest.nodesScope.$id;
                }

                function movedFromBacklogToSubmilestone() {
                    return source_list_element.hasClass('backlog') && dest_list_element.hasClass('submilestone');
                }

                function movedFromChildrenToChildren() {
                    return source_list_element.hasClass('backlog-item-children') && dest_list_element.hasClass('backlog-item-children');
                }

                function movedFromSubmilestoneToBacklog() {
                    return source_list_element.hasClass('submilestone') && dest_list_element.hasClass('backlog');
                }

                function movedFromOneSubmilestoneToAnother() {
                    return source_list_element.hasClass('submilestone') && dest_list_element.hasClass('submilestone');
                }
            }

            function updateSubmilestonesInitialEffort() {
                if (source_list_element.hasClass('submilestone')) {
                    MilestoneService.updateInitialEffort(_.find($scope.milestones, function(milestone) {
                        return milestone.id == source_list_element.attr('data-submilestone-id');
                    }));
                }

                if (dest_list_element.hasClass('submilestone')) {
                    MilestoneService.updateInitialEffort(_.find($scope.milestones, function(milestone) {
                        return milestone.id == dest_list_element.attr('data-submilestone-id');
                    }));
                }
            }

            function collapseSourceParentIfNeeded() {
                if (event.sourceParent && ! event.sourceParent.hasChild()) {
                    event.sourceParent.collapse();
                }
            }

            function removeFromDestinationIfNeeded() {
                if (event.dest.nodesScope.collapsed &&
                    event.dest.nodesScope.$nodeScope.$modelValue.has_children &&
                    ! event.dest.nodesScope.$nodeScope.$modelValue.children.loaded) {

                    event.dest.nodesScope.childNodes()[0].remove();
                }
            }
        }
    }
})();
