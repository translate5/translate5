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

use editor_Models_Task as Task;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\DataProvider\CoordinatorProvider;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DTO\NewCoordinatorGroupJobDto;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\WithAuthentication\CreateCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\JobAssignmentViewDataProvider;
use MittagQI\Translate5\JobAssignment\JobExceptionTransformer;
use MittagQI\Translate5\JobAssignment\JobSorterService;
use MittagQI\Translate5\JobAssignment\Operation\WithAuthentication\DeleteJobAssignmentOperation;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\UserJobAction;
use MittagQI\Translate5\JobAssignment\UserJob\DataProvider\UserProvider;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\Factory\NewUserJobDtoFactory;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\Factory\UpdateUserJobDtoFactory;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\WithAuthentication\CreateUserJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\WithAuthentication\UpdateUserJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\JobAssignment\UserJob\UserJobViewDataProvider;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;

/**
 * Controller for the User Task Associations
 * Since PMs see all Task and Users, the indexAction has not to be constrained to show a subset of associations for
 * security reasons
 */
class Editor_TaskuserassocController extends ZfExtended_RestController
{
    protected $entityClass = 'editor_Models_TaskUserAssoc';

    /**
     * @var editor_Models_TaskUserAssoc
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    private UserRepository $userRepository;

    private TaskRepository $taskRepository;

    private UserJobRepository $userJobRepository;

    private CoordinatorGroupJobRepository $coordinatorGroupJobRepository;

    private UserJobViewDataProvider $userJobViewDataProvider;

    private UserJobActionPermissionAssert $permissionAssert;

    private CoordinatorProvider $coordinatorProvider;

    private UserProvider $userProvider;

    private TaskActionPermissionAssert $taskActionPermissionAssert;

    private JobAssignmentViewDataProvider $jobAssignmentViewDataProvider;

    private JobExceptionTransformer $jobExceptionTransformer;

    private JobSorterService $jobSorterService;

    public function init()
    {
        parent::init();
        $this->userRepository = new UserRepository();
        $this->taskRepository = TaskRepository::create();
        $this->userJobRepository = UserJobRepository::create();
        $this->coordinatorGroupJobRepository = CoordinatorGroupJobRepository::create();
        $this->permissionAssert = UserJobActionPermissionAssert::create();
        $this->taskActionPermissionAssert = TaskActionPermissionAssert::create();

        $this->userJobViewDataProvider = UserJobViewDataProvider::create();
        $this->jobAssignmentViewDataProvider = JobAssignmentViewDataProvider::create();
        $this->coordinatorProvider = CoordinatorProvider::create();
        $this->userProvider = UserProvider::create();

        $this->jobExceptionTransformer = JobExceptionTransformer::create();
        $this->jobSorterService = JobSorterService::create();
    }

    public function indexAction(): void
    {
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        /** @deprecated App logic should not tolerate requests without task in scope */
        if (! $this->getRequest()->getParam('taskId')) {
            //            Zend_Registry::get('logger')->warn(
            //                'E1680',
            //                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead',
            //            );

            $rows = $this->userJobViewDataProvider->buildViewForList($this->entity->loadAll(), $authUser);

            // @phpstan-ignore-next-line
            $this->view->rows = $rows;
            $this->view->total = count($rows);

            return;
        }

        $task = $this->resolveTask();

        $rows = $this->jobAssignmentViewDataProvider->getListFor($task->getTaskGuid(), $authUser);

        // @phpstan-ignore-next-line
        $this->view->rows = $this->jobSorterService->sortJobsByWorkflowPosition(
            $rows,
            $task->getTaskActiveWorkflow()
        );
        $this->view->total = count($rows);
    }

    public function projectAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            //            Zend_Registry::get('logger')->warn(
            //                'E1680',
            //                'Route /editor/taskuserassoc/project deprecated, use editor/project/:projectId/jobs/:workflow instead',
            //            );
        }

        $projectId = $this->getParam('projectId');
        $workflow = $this->getParam('workflow');

        if (empty($projectId) || empty($workflow)) {
            return;
        }

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $jobs = $this->userJobRepository->getProjectJobs((int) $projectId, $workflow);

        $rows = $this->userJobViewDataProvider->buildViewForList($jobs, $authUser);

        // @phpstan-ignore-next-line
        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function usersfornewjobAction()
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $task = $this->resolveTask();

            $this->taskActionPermissionAssert->assertGranted(
                TaskAction::Read,
                $task,
                new PermissionAssertContext($authUser)
            );

            // @phpstan-ignore-next-line
            $this->view->rows = $this->userProvider->getPossibleUsersForNewJobInTask($task, $authUser);
        } catch (Throwable $e) {
            throw $this->jobExceptionTransformer->transformException($e);
        }
    }

    public function jobcoordinatorsfornewjobAction()
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $task = $this->resolveTask();

            $this->taskActionPermissionAssert->assertGranted(
                TaskAction::Read,
                $task,
                new PermissionAssertContext($authUser)
            );

            // @phpstan-ignore-next-line
            $this->view->rows = $this->coordinatorProvider->getPossibleCoordinatorsForNewJobInTask($task, $authUser);
        } catch (Throwable $e) {
            throw $this->jobExceptionTransformer->transformException($e);
        }
    }

    public function coordinatorsforupdateAction()
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('jobId'));

            $this->assertJobBelongsToTask($job);

            $this->permissionAssert->assertGranted(
                UserJobAction::Update,
                $job,
                new PermissionAssertContext($authUser)
            );

            if (! $job->isCoordinatorGroupJob()) {
                throw new ZfExtended_BadMethodCallException('Only Coordinator group jobs can have coordinators');
            }

            $groupJob = $this->coordinatorGroupJobRepository->get((int) $job->getCoordinatorGroupJobId());

            // @phpstan-ignore-next-line
            $this->view->rows = $this->coordinatorProvider->getPossibleCoordinatorsForCoordinatorGroupJobUpdate($groupJob);
        } catch (Throwable $e) {
            throw $this->jobExceptionTransformer->transformException($e);
        }
    }

    public function putAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            //            Zend_Registry::get('logger')->warn(
            //                'E1680',
            //                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead'
            //            );
        }

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));

            $this->assertJobBelongsToTask($job);

            $this->processClientReferenceVersion($job);

            $dto = UpdateUserJobDtoFactory::create()->fromRequest($this->getRequest());

            UpdateUserJobOperation::create()->update($job, $dto);

            $this->view->rows = (object) $this->userJobViewDataProvider->buildJobView($job, $authUser);

            // @phpstan-ignore-next-line
            $this->entity = $job; // for the afterPutAction
        } catch (Throwable $e) {
            throw $this->jobExceptionTransformer->transformException($e);
        }
    }

    public function postAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            //            Zend_Registry::get('logger')->warn(
            //                'E1680',
            //                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead',
            //            );
        }

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $dto = NewUserJobDtoFactory::create()->fromRequest($this->getRequest());

            if (TypeEnum::Coordinator === $dto->type) {
                $groupJob = CreateCoordinatorGroupJobOperation::create()->assignJob(NewCoordinatorGroupJobDto::fromUserJobDto($dto));
                $userJob = UserJobRepository::create()->getDataJobByCoordinatorGroupJob((int) $groupJob->getId());
            } else {
                $userJob = CreateUserJobOperation::create()->assignJob($dto);
            }

            $this->view->rows = (object) $this->userJobViewDataProvider->buildJobView($userJob, $authUser);

            // @phpstan-ignore-next-line
            $this->entity = $userJob; // for the afterPostAction
        } catch (Throwable $e) {
            throw $this->jobExceptionTransformer->transformException($e);
        }
    }

    public function deleteAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            //            Zend_Registry::get('logger')->warn(
            //                'E1680',
            //                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead',
            //            );
        }

        $workflowManager = new editor_Workflow_Manager();

        try {
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));

            $this->assertJobBelongsToTask($job);

            // TODO: This workflow instantiating here is a workaroung to trigger editor_Workflow_Default_Hooks.
            //      constructor there has events listeners that should be extracted.
            $workflowManager->get($job->getWorkflow());

            $this->processClientReferenceVersion($job);

            $deleteJobOperation = DeleteJobAssignmentOperation::create();

            if ($this->getRequest()->has('force')) {
                $deleteJobOperation->forceDelete((int) $job->getId());
            } else {
                $deleteJobOperation->delete((int) $job->getId());
            }
        } catch (Throwable $e) {
            throw $this->jobExceptionTransformer->transformException($e);
        }
    }

    private function resolveTask(): Task
    {
        $task = $this->taskRepository->find((int) $this->getRequest()->getParam('taskId'));

        if (null !== $task) {
            return $task;
        }

        return $this->taskRepository->getByGuid((string) $this->getRequest()->getParam('taskId'));
    }

    /**
     * @throws InexistentTaskException
     * @throws ZfExtended_NotFoundException
     */
    public function assertJobBelongsToTask(editor_Models_TaskUserAssoc $job): void
    {
        if ($this->hasParam('taskId')) {
            $task = $this->taskRepository->getByGuid($job->getTaskGuid());

            if ((int) $task->getId() !== (int) $this->getRequest()->getParam('taskId')) {
                throw new ZfExtended_NotFoundException('Job not found');
            }
        }
    }
}
