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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_URLTest extends TuleapTestCase {

    /** @var ProjectManager **/
    protected $project_manager;

    /** @var GitRepositoryFactory **/
    protected $repository_factory;

    /** @var Project */
    protected $gpig_project;

    /** @var GitRepository */
    protected $goldfish_repository;

    protected $gpig_project_name = 'gpig';
    protected $gpig_project_id   = '111';
    protected $repository_id     = '43';

    public function setUp() {
        parent::setUp();
        $this->project_manager     = mock('ProjectManager');
        $this->repository_factory  = mock('GitRepositoryFactory');
        $this->gpig_project        = mock('Project');
        stub($this->gpig_project)->getId()->returns($this->gpig_project_id);
        stub($this->gpig_project)->getUnixName()->returns($this->gpig_project_name);

        $this->goldfish_repository = aGitRepository()
            ->withProject($this->gpig_project)
            ->withName('device/generic/goldfish')
            ->build();

        stub($this->repository_factory)
            ->getByProjectNameAndPath(
                $this->gpig_project_name,
                'device/generic/goldfish.git'
            )->returns($this->goldfish_repository);

        stub($this->repository_factory)
            ->getRepositoryById($this->repository_id)
            ->returns($this->goldfish_repository);

        stub($this->project_manager)
            ->getProject($this->gpig_project_id)
            ->returns($this->gpig_project);
    }
}

class Git_URL_FriendlyTest extends Git_URLTest {

    protected $url_goldfish_repository = '/plugins/git/gpig/device/generic/goldfish?q=parameters';

    public function itIsFriendly() {
        $url = $this->getGitURL();

        $this->assertTrue($url->isFriendly());
        $this->assertFalse($url->isStandard());
    }

    public function itRetrievesTheRepository() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getRepository(), $this->goldfish_repository);
    }

    public function itRetrievesTheProject() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getProject(), $this->gpig_project);
    }

    public function itRetrievesTheParameters() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getParameters(), 'q=parameters');
    }

    /** @return Git_URL */
    private function getGitURL() {
        return new Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $this->url_goldfish_repository
        );
    }
}

class Git_URL_StandardTest extends Git_URLTest {

    protected $url_goldfish_repository = '/plugins/git/index.php/111/view/43/?q=parameters';

    public function itIsStandard() {
        $url = $this->getGitURL();

        $this->assertFalse($url->isFriendly());
        $this->assertTrue($url->isStandard());
    }

    public function itRetrievesTheRepository() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getRepository(), $this->goldfish_repository);
    }

    public function itRetrievesTheProject() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getProject(), $this->gpig_project);
    }

    public function itRetrievesTheParameters() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getParameters(), 'q=parameters');
    }

    public function itRetrievesTheRepositoryByName() {
        expect($this->repository_factory)->getRepositoryByPath($this->gpig_project_id, $this->gpig_project_name.'/coin.git')->once();

        $url = $this->getGitURL('/plugins/git/index.php/111/view/coin/?q=parameters');
        $url->getRepository();
    }

    public function itReturnsNoRepositoryAsSoonAsTheNameDoesNotMatchAnExistingRepository() {
        stub($this->repository_factory)->getRepositoryByPath()->returns(null);

        $url = $this->getGitURL('/plugins/git/index.php/111/view/coin/?q=parameters');
        $this->assertNull($url->getRepository());
        $this->assertNull($url->getProject());
    }

    /** @return Git_URL */
    private function getGitURL($uri = null) {
        return new Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $uri ? $uri : $this->url_goldfish_repository
        );
    }
}

class Git_URL_InvalidURITest extends Git_URLTest {

    protected $url_goldfish_repository = '/plugins/git/gpig/invalid_url?blah';

    public function itIsNotFriendlyNeitherStandard() {
        $url = $this->getGitURL();

        $this->assertFalse($url->isFriendly());
        $this->assertFalse($url->isStandard());
    }

    public function itReturnsNoRepository() {
        $url = $this->getGitURL();

        $this->assertNull($url->getRepository());
    }

    public function itReturnsNoProject() {
        $url = $this->getGitURL();

        $this->assertNull($url->getProject());
    }

    public function itReturnsNoParameters() {
        $url = $this->getGitURL();

        $this->assertEqual($url->getParameters(), '');
    }

    /** @return Git_URL */
    private function getGitURL() {
        return new Git_URL(
            $this->project_manager,
            $this->repository_factory,
            $this->url_goldfish_repository
        );
    }
}