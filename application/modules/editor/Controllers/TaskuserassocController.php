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
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorDontBelongToLCoordinatorGroupException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\ActionAssert\Feasibility\Exception\ThereIsUnDeletableBoundJobException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\DataProvider\CoordinatorProvider;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Exception\CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DTO\NewCoordinatorGroupJobDto;
use MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\WithAuthentication\CreateCoordinatorGroupJobOperation;
use MittagQI\Translate5\JobAssignment\Exception\ConfirmedCompetitiveJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\JobAssignment\JobAssignmentViewDataProvider;
use MittagQI\Translate5\JobAssignment\Operation\WithAuthentication\DeleteJobAssignmentOperation;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobInUseException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobWhichTaskIsLockedByUserException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\Exception\UserHasAlreadyOpenedTheTaskForEditingException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\Exception\ActionNotAllowedException;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\UserJobAction;
use MittagQI\Translate5\JobAssignment\UserJob\DataProvider\UserProvider;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\CoordinatorHasNotConfirmedCoordinatorGroupJobYetException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidSegmentRangeFormatException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidSegmentRangeSemanticException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\NotCoordinatorGroupCustomerTaskException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException;
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
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use ZfExtended_Models_Entity_Conflict as EntityConflictException;
use ZfExtended_UnprocessableEntity as UnprocessableEntity;

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

        ZfExtended_UnprocessableEntity::addCodes([
            'E1012' => 'Multi Purpose Code logging in the context of jobs (task user association)',
            'E1280' => 'The format of the segmentrange that is assigned to the user is not valid.',
        ]);

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1061' => 'The job can not be removed, since the user is using the task.',
            'E1062' => 'The job can not be removed, since the task is locked by the user.',
            'E1161' => 'The job can not be modified, since the user has already opened the task for editing.'
                . ' You are to late.',
            'E1542' => QualityService::ERROR_MASSAGE_PLEASE_SOLVE_ERRORS,
        ]);
    }

    public function indexAction(): void
    {
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());

        /** @deprecated App logic should not tolerate requests without task in scope */
        if (! $this->getRequest()->getParam('taskId')) {
            Zend_Registry::get('logger')->warn(
                'E1680',
                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead',
            );

            $rows = $this->userJobViewDataProvider->buildViewForList($this->entity->loadAll(), $authUser);

            // @phpstan-ignore-next-line
            $this->view->rows = $rows;
            $this->view->total = count($rows);

            return;
        }

        $task = $this->resolveTask();

        $rows = $this->jobAssignmentViewDataProvider->getListFor($task->getTaskGuid(), $authUser);

        // @phpstan-ignore-next-line
        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function projectAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1680',
                'Route /editor/taskuserassoc/project deprecated, use editor/project/:projectId/jobs/:workflow instead',
            );
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
            $this->log->exception($e);

            throw $this->transformException($e);
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
            $this->log->exception($e);

            throw $this->transformException($e);
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
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function putAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1680',
                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead'
            );
        }

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));

            $this->assertJobBelongsToTask($job);

            $this->processClientReferenceVersion($job);

            $dto = UpdateUserJobDtoFactory::create()->fromRequest($this->getRequest());

            UpdateUserJobOperation::create()->update($job, $dto);

            $this->view->rows = (object) $this->userJobViewDataProvider->buildJobView($job, $authUser);
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function postAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1680',
                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead',
            );
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
        } catch (Throwable $e) {
            $this->log->exception($e);

            throw $this->transformException($e);
        }
    }

    public function deleteAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1680',
                'Route /editor/taskuserassoc deprecated, use /editor/task/:taskId/job instead',
            );
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
            $this->log->exception($e);

            throw $this->transformException($e);
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
     * @throws Throwable
     */
    private function transformException(Throwable $e): ZfExtended_ErrorCodeException|Throwable
    {
        $invalidValueProvidedMessage = 'Ungültiger Wert bereitgestellt';

        return match ($e::class) {
            InvalidTypeProvidedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'type' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InexistentUserException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InexistentTaskException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'taskGuid' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InvalidStateProvidedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'state' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            InvalidDeadlineDateStringProvidedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'deadlineDate' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
            ),
            UserHasAlreadyOpenedTheTaskForEditingException::class => EntityConflictException::createResponse(
                'E1161',
                [
                    'id' => 'Sie können den Job zur Zeit nicht bearbeiten,'
                        . ' der Benutzer hat die Aufgabe bereits zur Bearbeitung geöffnet.',
                ]
            ),
            InvalidSegmentRangeFormatException::class => UnprocessableEntity::createResponse(
                'E1280',
                [
                    'id' => 'Das Format für die editierbaren Segmente ist nicht valide. Bsp: 1-3,5,8-9',
                ]
            ),
            InvalidSegmentRangeSemanticException::class => UnprocessableEntity::createResponse(
                'E1280',
                [
                    'id' => 'Der Inhalt für die editierbaren Segmente ist nicht valide.'
                        . ' Die Zahlen müssen in der richtigen Reihenfolge angegeben sein und dürfen nicht überlappen,'
                        . ' weder innerhalb der Eingabe noch mit anderen Usern von derselben Rolle.',
                ]
            ),
            TaskHasCriticalQualityErrorsException::class => EntityConflictException::createResponse(
                'E1542',
                [QualityService::ERROR_MASSAGE_PLEASE_SOLVE_ERRORS],
                [
                    'task' => $e->task,
                    'categories' => implode('</br>', $e->categories),
                ]
            ),
            AttemptToRemoveJobInUseException::class => EntityConflictException::createResponse(
                'E1061',
                [
                    'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da der Benutzer diese aktuell benutzt.',
                ],
                [
                    'job' => $e->job,
                ]
            ),
            AttemptToRemoveJobWhichTaskIsLockedByUserException::class => EntityConflictException::createResponse(
                'E1062',
                [
                    'Die Zuweisung zwischen Aufgabe und Benutzer kann nicht gelöscht werden, da die Aufgabe durch den Benutzer gesperrt ist.',
                ],
                [
                    'job' => $e->job,
                ]
            ),
            OnlyCoordinatorCanBeAssignedToCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.create.only-coordinator-can-be-assigned-to-coordinator-group-job',
                    ],
                ],
            ),
            AssignedUserCanBeChangedOnlyForCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.update.assigned-user-can-be-changed-only-for-coordinator-group-job',
                    ],
                ],
            ),
            NotCoordinatorGroupCustomerTaskException::class => EntityConflictException::createResponse(
                'E1012',
                [
                    'id' => [
                        'not-coordinator-group-customer-task',
                    ],
                ],
            ),
            CoordinatorDontBelongToLCoordinatorGroupException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'coordinator-dont-belong-to-coordinator-group',
                    ],
                ],
            ),
            AttemptToAssignCoordinatorGroupUserJobBeforeCoordinatorGroupJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.create.group-user-job-can-not-be-created-before-coordinator-group-job',
                    ],
                ],
            ),
            AttemptToAssignSubCoordinatorGroupJobBeforeParentJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'job-assignment.create.parent-coordinator-group-does-not-have-appropriate-job',
                    ],
                ],
            ),
            TrackChangesRightsAreNotSubsetOfCoordinatorGroupJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'permission' => [
                        'job-assignment.track-changes-rights-are-not-subset-of-coordinator-group-job',
                    ],
                ],
            ),
            ThereIsUnDeletableBoundJobException::class => EntityConflictException::createResponse(
                'E1162',
                [
                    'id' => [
                        'job-assignment.delete.there-is-un-deletable-bound-job',
                    ],
                ],
            ),
            ActionNotAllowedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'deadlineDate' => [
                        $invalidValueProvidedMessage,
                    ],
                ],
                [
                    'exception' => ActionNotAllowedException::class,
                    'action' => $e->jobAction->value,
                    'job' => $e->userJob->getId(),
                ]
            ),
            ConfirmedCompetitiveJobAlreadyExistsException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'Es gibt bereits eine bestätigte konkurrierende Auftragsvergabe',
                    ],
                ],
            ),
            CoordinatorHasNotConfirmedCoordinatorGroupJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'coordinator-has-not-yet-confirmed-the-coordinator-group-job',
                    ],
                ],
            ),
            CoordinatorOfParentGroupHasNotConfirmedCoordinatorGroupJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'coordinator-of-parent-group-has-not-yet-confirmed-the-coordinator-group-job',
                    ],
                ],
            ),
            default => $e,
        };
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
