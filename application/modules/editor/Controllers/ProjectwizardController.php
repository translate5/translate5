<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\UserJob\UserJobViewDataProvider;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;

class editor_ProjectwizardController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_Task::class;

    private UserRepository $userRepository;

    private TaskRepository $taskRepository;

    private UserJobRepository $userJobRepository;

    private UserJobViewDataProvider $userJobViewDataProvider;

    private TaskActionPermissionAssert $taskActionPermissionAssert;

    public function init()
    {
        parent::init();
        $this->userRepository = new UserRepository();
        $this->taskRepository = TaskRepository::create();
        $this->userJobRepository = UserJobRepository::create();
        $this->taskActionPermissionAssert = TaskActionPermissionAssert::create();
        $this->userJobViewDataProvider = UserJobViewDataProvider::create();
    }

    public function joblistAction(): void
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1680',
                'Route /editor/taskuserassoc/project deprecated, use editor/project/wizard/:projectId/jobs/:workflow instead',
            );
        }

        $projectId = $this->getParam('projectId');
        $workflow = $this->getParam('workflow');

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $jobs = $this->userJobRepository->getProjectJobs((int) $projectId, $workflow);

        $rows = $this->userJobViewDataProvider->buildViewForList($jobs, $authUser);

        // @phpstan-ignore-next-line
        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function tasklistAction(): void
    {
        $projectId = (int) $this->getParam('projectId');
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $tasks = $this->taskRepository->getProjectTaskList($projectId);
        $list = [];

        $context = new PermissionAssertContext($authUser);

        foreach ($tasks as $task) {
            if ($this->taskActionPermissionAssert->isGranted(TaskAction::Read, $task, $context)) {
                $list[] = [
                    'taskId' => (int) $task->getId(),
                    'taskGuid' => $task->getTaskGuid(),
                    'targetLanguage' => (int) $task->getTargetLang(),
                ];
            }
        }

        // @phpstan-ignore-next-line
        $this->view->rows = $list;
    }
}
