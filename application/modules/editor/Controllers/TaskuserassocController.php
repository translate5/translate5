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

use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Segment\QualityService;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\Task\Exception\TaskHasCriticalQualityErrorsException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobInUseException;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\AttemptToRemoveJobWhichTaskIsLockedByUserException;
use MittagQI\Translate5\UserJob\ActionAssert\Feasibility\Exception\UserHasAlreadyOpenedTheTaskForEditingException;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeFormatException;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeSemanticException;
use MittagQI\Translate5\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\UserJob\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\UserJob\Exception\AssignedUserCanBeChangedOnlyForLspJobException;
use MittagQI\Translate5\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\UserJob\Operation\Factory\NewUserJobDtoFactory;
use MittagQI\Translate5\UserJob\Operation\Factory\UpdateUserJobDtoFactory;
use MittagQI\Translate5\UserJob\Operation\WithAuthentication\CreateUserJobAssignmentOperation;
use MittagQI\Translate5\UserJob\Operation\WithAuthentication\DeleteUserJobAssignmentOperation;
use MittagQI\Translate5\UserJob\Operation\WithAuthentication\UpdateUserJobAssignmentOperation;
use MittagQI\Translate5\UserJob\ViewDataProvider;
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

    /**
     *  @var editor_Logger_Workflow
     */
    protected $log;

    /**
     * contains if available the task to the current tua
     * @var editor_Models_Task
     */
    protected $task;

    private UserRepository $userRepository;

    private TaskRepository $taskRepository;

    private ViewDataProvider $viewDataProvider;

    private UserJobRepository $userJobRepository;

    public function init()
    {
        parent::init();
        $this->task = ZfExtended_Factory::get(editor_Models_Task::class);
        $this->log = ZfExtended_Factory::get(editor_Logger_Workflow::class, [$this->task]);
        $this->userRepository = new UserRepository();
        $this->taskRepository = new TaskRepository();
        $this->userJobRepository = UserJobRepository::create();

        $this->viewDataProvider = ViewDataProvider::create();

        ZfExtended_UnprocessableEntity::addCodes([
            'E1012' => 'Multi Purpose Code logging in the context of jobs (task user association)',
            'E1280' => "The format of the segmentrange that is assigned to the user is not valid.",
        ]);

        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1061' => 'The job can not be removed, since the user is using the task.',
            'E1062' => 'The job can not be removed, since the task is locked by the user.',
            'E1161' => "The job can not be modified, since the user has already opened the task for editing."
                . " You are to late.",
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
                'Job list: this route deprecated, use /editor/task/:taskId/job instead'
            );

            $rows = $this->viewDataProvider->buildViewForList($this->entity->loadAll(), $authUser);

            // @phpstan-ignore-next-line
            $this->view->rows = $rows;
            $this->view->total = count($rows);

            return;
        }

        $task = $this->taskRepository->get((int) $this->getRequest()->getParam('taskId'));

        $rows = $this->viewDataProvider->getListFor($task, $authUser);

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
                'Job list: this route deprecated, use editor/project/:projectId/jobs instead'
            );
        }

        $projectId = $this->getParam('projectId');
        $workflow = $this->getParam('workflow');

        if (empty($projectId) || empty($workflow)) {
            return;
        }

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
        $jobs = $this->userJobRepository->getProjectJobs((int) $projectId, $workflow);

        $rows = $this->viewDataProvider->buildViewForList($jobs, $authUser);

        // @phpstan-ignore-next-line
        $this->view->rows = $rows;
        $this->view->total = count($rows);
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

        $this->log->request();

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));
            $task = $this->taskRepository->getByGuid($job->getTaskGuid());

            if ((int) $task->getId() !== (int) $this->getRequest()->getParam('taskId')) {
                throw new ZfExtended_NotFoundException('Job not found');
            }

            $this->processClientReferenceVersion($job);

            $dto = UpdateUserJobDtoFactory::create()->fromRequest($this->getRequest());

            UpdateUserJobAssignmentOperation::create()->update($job, $dto);

            $this->view->rows = (object) $this->viewDataProvider->buildJobView($job, $authUser);
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
                'Job list: this route deprecated, use /editor/task/:taskId/job instead'
            );
        }

        try {
            $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserid());
            $dto = NewUserJobDtoFactory::create()->fromRequest($this->getRequest());
            $job = CreateUserJobAssignmentOperation::create()->assignJob($dto);

            $this->view->rows = (object) $this->viewDataProvider->buildJobView($job, $authUser);
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
                'Job list: this route deprecated, use /editor/task/:taskId/job instead'
            );
        }

        $this->log->request();

        try {
            $job = $this->userJobRepository->get((int) $this->getRequest()->getParam('id'));
            $task = $this->taskRepository->getByGuid($job->getTaskGuid());

            if ((int) $task->getId() !== (int) $this->getRequest()->getParam('taskId')) {
                throw new ZfExtended_NotFoundException('Job not found');
            }

            $this->processClientReferenceVersion($job);

            $deleteJobOperation = DeleteUserJobAssignmentOperation::create();

            if ($this->getRequest()->has('force')) {
                $deleteJobOperation->forceDelete($job);
            } else {
                $deleteJobOperation->delete($job);
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
                    'job' => $this,
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
            default => $e,
        };
    }
}
