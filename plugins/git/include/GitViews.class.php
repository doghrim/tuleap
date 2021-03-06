<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/
 */

require_once 'www/project/admin/permissions.php';
require_once 'common/include/CSRFSynchronizerToken.class.php';

/**
 * GitViews
 */
class GitViews extends PluginViews {

    /** @var Project */
    private $project;

    /** @var GitPermissionsManager */
    private $git_permissions_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    public function __construct($controller, Git_GitRepositoryUrlManager $url_manager) {
        parent::__construct($controller);
        $this->groupId                 = (int)$this->request->get('group_id');
        $this->project                 = ProjectManager::instance()->getProject($this->groupId);
        $this->projectName             = $this->project->getUnixName();
        $this->userName                = $this->user->getName();
        $this->git_permissions_manager = new GitPermissionsManager();
        $this->ugroup_manager          = new UGroupManager();
        $this->url_manager             = $url_manager;
    }

    public function header() {
        $title = $GLOBALS['Language']->getText('plugin_git','title');

        $this->getToolbar();

        $GLOBALS['HTML']->header(array('title'=>$title, 'group'=>$this->groupId, 'toptab'=>'plugin_git'));
    }

    public function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    public function getText($key, $params=array() ) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }

    protected function getToolbar() {
        $GLOBALS['HTML']->addToolbarItem($this->linkTo($this->getText('bread_crumb_home'), '/plugins/git/?group_id='.$this->groupId));
        $GLOBALS['HTML']->addToolbarItem($this->linkTo($this->getText('fork_repositories'), '/plugins/git/?group_id='.$this->groupId .'&action=fork_repositories'));
        $GLOBALS['HTML']->addToolbarItem($this->linkTo($this->getText('bread_crumb_help'), 'javascript:help_window(\'/doc/'.$this->user->getShortLocale().'/user-guide/git.html\')'));

        if ($this->git_permissions_manager->userIsGitAdmin($this->user, $this->project)) {
            $GLOBALS['HTML']->addToolbarItem($this->linkTo($this->getText('bread_crumb_admin'), '/plugins/git/?group_id='.$this->groupId .'&action=admin'));
        }
    }

    /**
     * HELP VIEW
     */
    public function help($topic, $params=array()) {
        if ( empty($topic) ) {
            return false;
        }
        $display = 'block';
        if ( !empty($params['display']) ) {
            $display = $params['display'];
        }
        switch( $topic ) {
                case 'init':
             ?>
<div id="help_init" class="alert alert-info" style="display:<?php echo $display?>">
    <h3><?php echo $this->getText('help_reference_title'); ?></h3>
    <p>
                       <?php
                       echo '<ul>'.$this->getText('help_init_reference').'</ul>';
                       ?>
    </p>
    </div>
                    <?php
                    break;
                    case 'create':
                        ?>                        
                        <div id="help_create" class="alert alert-info" style="display:<?php echo $display?>">
                            <h3><?php echo $this->getText('help_create_reference_title'); ?></h3>
                        <?php
                        echo '<ul>'.$this->getText('help_create_reference').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'tree':
                        ?>
                        <div id="help_tree" class="alert alert-info" style="display:<?php echo $display?>">
                        <?php
                        echo '<ul>'.$this->getText('help_tree').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'fork':
                        ?>
                        <div id="help_fork" class="alert alert-info" style="display:<?php echo $display?>">
                        <?php
                        echo '<ul>'.$this->getText('help_fork').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                default:
                    break;
            }            
        }      

    /**
     * REPO VIEW
     */
    public function view() {
        $params     = $this->getData();
        $repository = $params['repository'];
        $request    = $this->controller->getRequest();

        $index_view = new GitViews_ShowRepo(
            $repository,
            $this->controller,
            $this->url_manager,
            $this->controller->getRequest(),
            $params['driver_factory'],
            $params['gerrit_usermanager'],
            $params['gerrit_servers']
        );
        $index_view->display();
    }

    /**
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement() {
        $params = $this->getData();
        $repository   = $params['repository'];

        echo '<h1>'. $repository->getHTMLLink($this->url_manager) .' - '. $GLOBALS['Language']->getText('global', 'Settings') .'</h1>';
        $repo_management_view = new GitViews_RepoManagement(
            $repository,
            $this->controller->getRequest(),
            $params['driver_factory'],
            $params['gerrit_servers'],
            $params['gerrit_templates']
        );
        $repo_management_view->display();
    }
    
    /**
     * FORK VIEW
     */
    public function fork() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $initialized  = $repository->isInitialized();

        echo "<h1>". $repository->getHTMLLink($this->url_manager) ."</h1>";
        ?>
        <form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId?>">
        <input type="hidden" id="action" name="action" value="edit" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId?>" />
        <?php
        if ( $initialized && $this->getController()->isAPermittedAction('clone') ) :
        ?>
            <p id="plugin_git_fork_form">
                <input type="hidden" id="parent_id" name="parent_id" value="<?php echo $repoId?>">
                <label for="repo_name"><?php echo $this->getText('admin_fork_creation_input_name');
        ?>:     </label>
                <input type="text" id="repo_name" name="repo_name" value="" /><input type="submit" class="btn btn-default" name="clone" value="<?php echo $this->getText('admin_fork_creation_submit');?>" />
                <a href="#" onclick="$('help_fork').toggle();"> [?]</a>
            </p>
        </form>
        <?php
        endif;
        $this->help('fork', array('display'=>'none'));
    }

    /**
     * CONFIRM PRIVATE
     */
    public function confirmPrivate() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        $mails        = $params['mails'];
        if ( $this->getController()->isAPermittedAction('save') ) :
        ?>
        <div class="confirm">
        <h3><?php echo $this->getText('set_private_confirm'); ?></h3>
        <form id="confirm_private" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId; ?>" >
        <input type="hidden" id="action" name="action" value="set_private" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId; ?>" />
        <input type="submit" id="submit" name="submit" value="<?php echo $this->getText('yes') ?>"/><span><input type="button" value="<?php echo $this->getText('no')?>" onclick="window.location='/plugins/git/?action=view&group_id=<?php echo $this->groupId;?>&repo_id=<?php echo $repoId?>'"/> </span>
        </form>
        <h3><?php echo $this->getText('set_private_mails'); ?></h3>
    <table>
        <?php
        $i = 0;
        foreach ($mails as $mail) {
            echo '<tr class="'.html_get_alt_row_color(++$i).'">';
            echo '<td>'.$mail.'</td>';
            echo '</tr>';
        }
        ?>
    </table>
    </div>
        <?php
        endif;
    }

    /**
     * TREE VIEW
     */
    public function index() {
        $params = $this->getData();

        $this->_tree($params);
        if ( $this->getController()->isAPermittedAction('add') ) {
            $this->_createForm();
        }
    }

    /**
     * CREATE REF FORM
     */
    protected function _createForm() {
        $user = UserManager::instance()->getCurrentUser();
        ?>
<h2><?php echo $this->getText('admin_reference_creation_title');
        ?> <a href="#" onclick="$('help_create').toggle();$('help_init').toggle()"><i class="icon-question-sign"></i></a></h2>
<form id="addRepository" action="/plugins/git/?group_id=<?php echo $this->groupId ?>" method="POST" class="form-inline">
    <input type="hidden" id="action" name="action" value="add" />
    
    <label for="repo_name"><?= $this->getText('admin_reference_creation_input_name'); ?></label>
    <input id="repo_name" name="repo_name" class="" type="text" value=""/>

    <input type="submit" id="repo_add" name="repo_add" value="<?php echo $this->getText('admin_reference_creation_submit')?>" class="btn btn-primary">
</form>
        <?php
        $this->help('create', array('display'=>'none')) ;
        $this->help('init', array('display'=>'none')) ;
    }

    /**
     * @todo several cases ssh, http ...
     * @param <type> $repositoryName
     * @return <type>
     */
    protected function _getRepositoryUrl($repositoryName) {
        $serverName  = $_SERVER['SERVER_NAME'];
        return  $this->userName.'@'.$serverName.':/gitroot/'.$this->projectName.'/'.$repositoryName.'.git';
    }

    protected function forkRepositories() {
        $params = $this->getData();

        echo '<h1>'. $this->getText('fork_repositories') .'</h1>';
        if ($this->user->isMember($this->groupId)) {
            echo $this->getText('fork_personal_repositories_desc');
        }
        echo $this->getText('fork_project_repositories_desc');
        if ( !empty($params['repository_list']) ) {
            echo '<form action="" method="POST">';
            echo '<input type="hidden" name="group_id" value="'. (int)$this->groupId .'" />';
            echo '<input type="hidden" name="action" value="fork_repositories_permissions" />';
            $token = new CSRFSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=fork_repositories');
            echo $token->fetchHTMLInput();

            echo '<table id="fork_repositories" cellspacing="0">';
            echo '<thead>';
            echo '<tr valign="top">';
            echo '<td class="first">';
            echo '<label style="font-weight: bold;">'. $this->getText('fork_repositories_select') .'</label>';
            echo '</td>';
            echo '<td>';
            echo '<label style="font-weight: bold;">'. $this->getText('fork_destination_project') .'</label>';
            echo '</td>';
            echo '<td>';
            echo '<label style="font-weight: bold;">'. $this->getText('fork_repositories_path') .'</label>';
            echo '</td>';
            echo '<td class="last">&nbsp;</td>';
            echo '</tr>';
            echo '</thead>';

            echo '<tbody><tr valign="top">';
            echo '<td class="first">';
            $strategy = new GitViewsRepositoriesTraversalStrategy_Selectbox($this);
            echo $strategy->fetch($params['repository_list'], $this->user);
            echo '</td>';

            echo '<td>';
            $options = ' disabled="true" ';
            if ($this->user->isMember($this->groupId)) {
                $options = ' checked="true" ';
            }
            echo '<div>
                <input id="choose_personal" type="radio" name="choose_destination" value="'. Git::SCOPE_PERSONAL .'" '.$options.' />
                <label class="radio" for="choose_personal">'.$this->getText('fork_choose_destination_personal').'</label>
            </div>';

            echo $this->fetchCopyToAnotherProject();

            echo '</td>';

            echo '<td>';
            $placeholder = $this->getText('fork_repositories_placeholder');
            echo '<input type="text" title="'. $placeholder .'" placeholder="'. $placeholder .'" id="fork_repositories_path" name="path" />';
            echo '<input type="hidden" id="fork_repositories_prefix" value="u/'. $this->user->getName() .'" />';
            echo '</td>';

            echo '<td class="last">';
            echo '<input type="submit" class="btn btn-primary" value="'. $this->getText('fork_repositories') .'" />';
            echo '</td>';

            echo '</tr></tbody></table>';

            echo '</form>';
        }
        echo '<br />';
    }

    protected function adminGitAdminsView() {
        $params = $this->getData();

        $presenter = new GitPresenters_AdminGitAdminsPresenter(
            $this->groupId,
            $this->ugroup_manager->getStaticUGroups($this->project),
            $this->git_permissions_manager->getCurrentGitAdminUgroups($this->project->getId())
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    protected function adminGerritTemplatesView() {
        $params = $this->getData();


        $repository_list = (isset($params['repository_list'])) ? $params['repository_list'] : array();
        $templates_list  = (isset($params['templates_list'])) ? $params['templates_list'] : array();
        $parent_templates_list  = (isset($params['parent_templates_list'])) ? $params['parent_templates_list'] : array();

        $presenter = new GitPresenters_AdminGerritTemplatesPresenter(
            $repository_list,
            $templates_list,
            $parent_templates_list,
            $this->groupId,
            $params['has_gerrit_servers_set_up']
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    protected function adminMassUpdateSelectRepositoriesView() {
        $params = $this->getData();

        $repository_list = $this->getGitRepositoryFactory()->getAllRepositories($this->project);
        $presenter       = new GitPresenters_AdminMassUpdateSelectRepositoriesPresenter(
            $this->generateMassUpdateCSRF(),
            $this->groupId,
            $repository_list
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    protected function adminMassUpdateView() {
        $params = $this->getData();

        $repositories = $params['repositories'];
        $presenter    = new GitPresenters_AdminMassUpdatePresenter(
            $this->generateMassUpdateCSRF(),
            $this->groupId,
            $repositories,
            new GitPresenters_AdminMassUdpdateMirroringPresenter($this->getAdminMassUpdateMirrorPresenters())
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    private function generateMassUpdateCSRF() {
        return new CSRFSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=admin-mass-update');
    }

    private function getAdminMassUpdateMirrorPresenters() {
        $mirror_data_mapper = new Git_Mirror_MirrorDataMapper(
            new Git_Mirror_MirrorDao(),
            UserManager::instance()
        );

        $mirrors           = $mirror_data_mapper->fetchAll();
        $mirror_presenters = array();

        foreach($mirrors as $mirror) {
            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, false);
        }

        return $mirror_presenters;
    }

    /**
     * Creates form to set permissions when fork repositories is performed
     *
     * @return void
     */
    protected function forkRepositoriesPermissions() {
        $params = $this->getData();


        if ($params['scope'] == 'project') {
            $groupId = $params['group_id'];
        } else {
            $groupId = (int)$this->groupId;
        }
        $repositories = explode(',', $params['repos']);
        $repository   = $this->getGitRepositoryFactory()->getRepositoryById($repositories[0]);
        if (!empty($repository)) {
            $forkPermissionsManager = new GitForkPermissionsManager($repository);
            $userName               = $this->user->getName();
            echo $forkPermissionsManager->displayRepositoriesPermissionsForm($params, $groupId, $userName);
        }
    }

    private function getGitRepositoryFactory() {
        return new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
    }

    private function fetchCopyToAnotherProject() {
        $html = '';
        $userProjectOptions = $this->getUserProjectsAsOptions($this->user, ProjectManager::instance(), $this->groupId);
        if ($userProjectOptions) {
            $options = ' checked="true" ';
            if ($this->user->isMember($this->groupId)) {
                $options = '';
            }
            $html .= '<div>
            <label class="radio">
                <input id="choose_project" type="radio" name="choose_destination" value="project" '.$options.' />
                '.$this->getText('fork_choose_destination_project').'</label>
            </div>';
            
            $html .= '<select name="to_project" id="fork_destination">';
            $html .= $userProjectOptions;
            $html .= '</select>';
        }
        return $html;
    }

    public function getUserProjectsAsOptions(PFUser $user, ProjectManager $manager, $currentProjectId) {
        $purifier   = Codendi_HTMLPurifier::instance();
        $html       = '';
        $option     = '<option value="%d" title="%s">%s</option>';
        $usrProject = array_diff($user->getAllProjects(), array($currentProjectId));
        
        foreach ($usrProject as $projectId) {
            $project = $manager->getProject($projectId);
            if ($user->isMember($projectId, 'A') && $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
                $projectName     = $project->getPublicName();
                $projectUnixName = $purifier->purify($project->getUnixName()); 
                $html           .= sprintf($option, $projectId, $projectUnixName, $projectName);
            }
        }
        return $html;
    }
    
    /**
     * TREE SUBVIEW
     */
    protected function _tree($params=array()) {
        if ( empty($params) ) {
            $params = $this->getData();
        }
        if (!empty($params['repository_list']) || (isset($params['repositories_owners']) && $params['repositories_owners']->rowCount() > 0)) {
            echo '<h1>'.$this->getText('tree_title_available_repo').' <a href="#" onclick="$(\'help_tree\').toggle();"><i class="icon-question-sign"></i></a></h1>';
            if (isset($params['repositories_owners']) && $params['repositories_owners']->rowCount() > 0) {
                $current_id = null;
                if (!empty($params['user'])) {
                    $current_id = (int)$params['user'];
                }
                $select = '<select name="user" onchange="this.form.submit()">';
                $uh = UserHelper::instance();
                $selected = 'selected="selected"';
                $select .= '<option value="" '. ($current_id ? '' : $selected) .'>'. $this->getText('tree_title_available_repo') .'</option>';
                foreach ($params['repositories_owners'] as $owner) {
                    $select .= '<option value="'. (int)$owner['repository_creation_user_id'] .'" '. ($owner['repository_creation_user_id'] == $current_id ? $selected : '') .'>'. $uh->getDisplayName($owner['user_name'], $owner['realname']) .'</option>';
                }
                $select .= '</select>';
                echo '<form action="" class="form-tree" method="GET">';
                echo '<input type="hidden" name="action" value="index" />';
                echo '<input type="hidden" name="group_id" value="'. (int)$this->groupId .'" />';
                echo $select;
                echo '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
                echo '</form>';
            }
            $this->help('tree', array('display' => 'none'));


            $lastPushes = array();
            $dao = new Git_LogDao();
            foreach ($params['repository_list'] as $repository) {
                $id  = $repository['repository_id'];
                $dar = $dao->searchLastPushForRepository($id);
                if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                    $lastPushes[$id] = $dar->getRow();
                }
            }
            $strategy = new GitViewsRepositoriesTraversalStrategy_Tree($lastPushes, $this->url_manager);
            echo $strategy->fetch($params['repository_list'], $this->user);
        }
        else {
            echo "<h3>".$this->getText('tree_msg_no_available_repo')."</h3>";
        }
    }
}

?>
