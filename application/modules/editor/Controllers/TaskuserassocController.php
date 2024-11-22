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
use MittagQI\Translate5\JobAssignment\Exception\ConfirmedCompetitiveJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\JobAssignmentViewDataProvider;
use MittagQI\Translate5\JobAssignment\Operation\WithAuthentication\DeleteJobAssignmentOperation;
use MittagQI\Translate5\LSP\Exception\CoordinatorDontBelongToLspException;
use MittagQI\Translate5\LspJob\ActionAssert\Feasibility\Exception\ThereIsUnDeletableBoundJobException;
use MittagQI\Translate5\LspJob\DataProvider\CoordinatorProvider;
use MittagQI\Translate5\LspJob\DataProvider\UserProvider;
use MittagQI\Translate5\LspJob\Operation\DTO\NewLspJobDto;
use MittagQI\Translate5\LspJob\Operation\WithAuthentication\CreateLspJobAssignmentOperation;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\ActionAssert\Permission\TaskActionPermissionAssert;
use MittagQI\Translate5\Task\ActionAssert\TaskAction;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobInUseException;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobWhichTaskIsLockedByUserException;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\UserHasAlreadyOpenedTheTaskForEditingException;
use MittagQI\Translate5\UserJob\ActionAssert\Permission\Exception\ActionNotAllowedException;
use MittagQI\Translate5\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use MittagQI\Translate5\UserJob\ActionAssert\UserJobAction;
use MittagQI\Translate5\UserJob\Exception\AssignedUserCanBeChangedOnlyForLspJobException;
use MittagQI\Translate5\UserJob\Exception\AttemptToAssignLspUserToAJobBeforeLspJobCreatedException;
use MittagQI\Translate5\UserJob\Exception\AttemptToAssignSubLspJobBeforeParentJobCreatedException;
use MittagQI\Translate5\UserJob\Exception\CoordinatorHasNotConfirmedLspJobYetException;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeFormatException;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeSemanticException;
use MittagQI\Translate5\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\UserJob\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\UserJob\Exception\NotLspCustomerTaskException;
use MittagQI\Translate5\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\UserJob\Exception\TrackChangesRightsAreNotSubsetOfLspJobException;
use MittagQI\Translate5\UserJob\Operation\Factory\NewUserJobDtoFactory;
use MittagQI\Translate5\UserJob\Operation\Factory\UpdateUserJobDtoFactory;
use MittagQI\Translate5\UserJob\Operation\WithAuthentication\CreateUserJobAssignmentOperation;
use MittagQI\Translate5\UserJob\Operation\WithAuthentication\UpdateUserJobAssignmentOperation;
use MittagQI\Translate5\UserJob\TypeEnum;
use MittagQI\Translate5\UserJob\UserJobViewDataProvider;
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

    private LspJobRepository $lspJobRepository;

    private UserJobViewDataProvider $userJobViewDataProvider;

    private UserJobActionPermissionAssert $permissionAssert;

    private CoordinatorProvider $coordinatorProvider;

    private UserProvider $userProvider;

    private TaskActionPermissionAssert $taskActionPermissionAssert;

    public function init()
    {
        parent::init();
        $this->userRepository = new UserRepository();
        $this->taskRepository = TaskRepository::create();
        $this->userJobRepository = UserJobRepository::create();
        $this->lspJobRepository = LspJobRepository::create();
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
                'E1232',
                'Job list: this route deprecated, use /editor/task/:taskId/job instead',
            );

            $rows = $this->userJobViewDataProvider->buildViewForList($this->entity->loadAll(), $authUser);

            // @phpstan-ignore-next-line
            $this->view->rows = $rows;
            $this->view->total = count($rows);

            return;
        }

        $task = $this->taskRepository->get((int) $this->getRequest()->getParam('taskId'));

        $rows = $this->jobAssignmentViewDataProvider->getListFor($task, $authUser);

        // @phpstan-ignore-next-line
        $this->view->rows = $rows;
        $this->view->total = count($rows);
    }

    public function projectAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1232',
                'Job list: this route deprecated, use editor/project/:projectId/jobs instead',
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
            $task = $this->taskRepository->get((int) $this->getRequest()->getParam('taskId'));

            $this->taskActionPermissionAssert->assertGranted(
                TaskAction::Read,
                $task,
                new PermissionAssertContext($authUser)
            );

            // @phpstan-ignore-next-line
            $this->view->rows = $this->userProvider->getPossibleUsersForNewJobInTask($task, $authUser);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    public function jobcoordinatorsfornewjobAction()
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $task = $this->taskRepository->get((int) $this->getRequest()->getParam('taskId'));

            $this->taskActionPermissionAssert->assertGranted(
                TaskAction::Read,
                $task,
                new PermissionAssertContext($authUser)
            );

            // @phpstan-ignore-next-line
            $this->view->rows = $this->coordinatorProvider->getPossibleCoordinatorsForNewJobInTask($task, $authUser);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    public function coordinatorsforupdateAction()
    {
        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));

            $this->assertJobBelongsToTask($job);

            $this->permissionAssert->assertGranted(
                UserJobAction::Update,
                $job,
                new PermissionAssertContext($authUser)
            );

            if (! $job->isLspJob()) {
                throw new ZfExtended_BadMethodCallException('Only LSP jobs can have coordinators');
            }

            $lspJob = $this->lspJobRepository->get((int) $job->getLspJobId());

            // @phpstan-ignore-next-line
            $this->view->rows = $this->coordinatorProvider->getPossibleCoordinatorsForLspJobUpdate($lspJob, $authUser);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    public function putAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1232',
                'Job list: this route deprecated, use /editor/task/:taskId/job instead'
            );
        }

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));

            $this->assertJobBelongsToTask($job);

            $this->processClientReferenceVersion($job);

            $dto = UpdateUserJobDtoFactory::create()->fromRequest($this->getRequest());

            UpdateUserJobAssignmentOperation::create()->update($job, $dto);

            $this->view->rows = (object) $this->userJobViewDataProvider->buildJobView($job, $authUser);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    public function postAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1232',
                'Job list: this route deprecated, use /editor/task/:taskId/job instead',
            );
        }

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $dto = NewUserJobDtoFactory::create()->fromRequest($this->getRequest());

            if (TypeEnum::Lsp === $dto->type) {
                $lspJob = CreateLspJobAssignmentOperation::create()->assignJob(NewLspJobDto::fromUserJobDto($dto));
                $userJob = UserJobRepository::create()->getDataJobByLspJob((int) $lspJob->getId());
            } else {
                $userJob = CreateUserJobAssignmentOperation::create()->assignJob($dto);
            }

            $this->view->rows = (object) $this->userJobViewDataProvider->buildJobView($userJob, $authUser);
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
    }

    public function deleteAction()
    {
        /** @deprecated App logic should not tolerate requests without task in scope */
        if (str_contains($this->getRequest()->getRequestUri(), 'taskuserassoc')) {
            Zend_Registry::get('logger')->warn(
                'E1232',
                'Job list: this route deprecated, use /editor/task/:taskId/job instead',
            );
        }

        try {
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));

            $this->assertJobBelongsToTask($job);

            $this->processClientReferenceVersion($job);

            $deleteJobOperation = DeleteJobAssignmentOperation::create();

            if ($this->getRequest()->has('force')) {
                $deleteJobOperation->forceDelete((int) $job->getId());
            } else {
                $deleteJobOperation->delete((int) $job->getId());
            }
        } catch (Throwable $e) {
            throw $this->transformException($e);
        }
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
            OnlyCoordinatorCanBeAssignedToLspJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'Nur der Koordinator kann einem LSP-Auftrag zugewiesen werden',
                    ],
                ],
            ),
            AssignedUserCanBeChangedOnlyForLspJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'Ein LSP-Benutzer kann nur LSP-bezogenen Auftrag zugewiesen werden.',
                    ],
                ],
            ),
            NotLspCustomerTaskException::class => EntityConflictException::createResponse(
                'E1012',
                [
                    'id' => [
                        'Aufgabe gehört nicht zu einem der LSP-KundenAufgabe gehört nicht zu einem der LSP-Kunden',
                    ],
                ],
            ),
            CoordinatorDontBelongToLspException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'Der Koordinator gehört nicht zum LSP',
                    ],
                ],
            ),
            AttemptToAssignLspUserToAJobBeforeLspJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'LSP-Benutzer kann einem Auftrag nicht zugewiesen werden, bevor der LSP-Auftrag erstellt wurde',
                    ],
                ],
            ),
            AttemptToAssignSubLspJobBeforeParentJobCreatedException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'userGuid' => [
                        'Der elterliche LSP verfügt nicht über eine angemessene Aufgabenverteilung',
                    ],
                ],
            ),
            TrackChangesRightsAreNotSubsetOfLspJobException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'permission' => [
                        'Die Rechte des LSP-Benutzers sollten eine Teilmenge der Rechte des LSP- Auftrags sein.',
                    ],
                ],
            ),
            ThereIsUnDeletableBoundJobException::class => EntityConflictException::createResponse(
                'E1162',
                [
                    'id' => [
                        'LSP-Auftrag hat verwandte Aufträge, die nicht gelöscht werden können.',
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
            CoordinatorHasNotConfirmedLspJobYetException::class => UnprocessableEntity::createResponse(
                'E1012',
                [
                    'id' => [
                        'Der Koordinator hat den LSP-Auftrag noch nicht bestätigt',
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
