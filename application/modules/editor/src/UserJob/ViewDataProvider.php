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

declare(strict_types=1);

namespace MittagQI\Translate5\UserJob;

use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\UserJob\ActionAssert\Permission\UserJobActionPermissionAssert;
use ZfExtended_Acl;
use ZfExtended_Factory;

/**
 * @template Job of array{
 * id: string,
 * taskGuid: string,
 * userGuid: string,
 * sourceLang: int,
 * targetLang: int,
 * state: string,
 * role: string,
 * workflowStepName: string,
 * workflow: string,
 * segmentrange: string|null,
 * segmentEditableCount: int,
 * segmentFinishCount: int,
 * usedState: string|null,
 * deadlineDate: string,
 * assignmentDate: string,
 * finishedDate: string|null,
 * trackchangesShow: bool,
 * trackchangesShowAll: bool,
 * trackchangesAcceptReject: bool,
 * type: string,
 * login: string,
 * firstName: string,
 * surName: string,
 * longUserName: string
 * }
 */
class ViewDataProvider
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly ActionPermissionAssertInterface $userJobPermissionAssert,
        private readonly UserRepository $userRepository,
        private readonly TaskRepository $taskRepository,
        private readonly ZfExtended_Acl $acl,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            UserJobActionPermissionAssert::create(),
            new UserRepository(),
            new TaskRepository(),
            ZfExtended_Acl::getInstance(),
        );
    }

    /**
     * @return Job[]
     */
    public function buildViewForList(iterable $jobs, User $viewer): array
    {
        $users = [];
        $tasks = [];
        $result = [];
        $context = new PermissionAssertContext($viewer);

        foreach ($jobs as $job) {
            $job = $this->getJob($job);

            try {
                $this->userJobPermissionAssert->assertGranted(Action::Read, $job, $context);
            } catch (PermissionExceptionInterface) {
                continue;
            }

            if (! isset($users[$job->getUserGuid()])) {
                $users[$job->getUserGuid()] = $this->userRepository->getByGuid($job->getUserGuid());
            }

            if (! isset($tasks[$job->getTaskGuid()])) {
                $tasks[$job->getTaskGuid()] = $this->taskRepository->getByGuid($job->getTaskGuid());
            }

            $result[] = $this->buildJobViewWithAssignedUserAndTask(
                $job,
                $users[$job->getUserGuid()],
                $tasks[$job->getTaskGuid()],
                $viewer
            );
        }

        return $result;
    }

    public function getListFor(Task $task, User $viewer): array
    {
        $jobs = $this->userJobRepository->getTaskJobs($task, true);

        return $this->buildViewForList($jobs, $viewer);
    }

    private function getJob(array|UserJob $job): UserJob
    {
        if ($job instanceof UserJob) {
            return $job;
        }

        $tua = ZfExtended_Factory::get(UserJob::class);
        $tua->init($job);

        return $tua;
    }

    /**
     * @return Job
     */
    public function buildJobView(UserJob $job, User $viewer): array
    {
        return $this->buildJobViewWithAssignedUserAndTask(
            $job,
            $this->userRepository->getByGuid($job->getUserGuid()),
            $this->taskRepository->getByGuid($job->getTaskGuid()),
            $viewer
        );
    }

    /**
     * @return Job
     */
    private function buildJobViewWithAssignedUserAndTask(
        UserJob $job,
        User $assignedUser,
        Task $task,
        User $viewer,
    ): array {
        $row = [
            'id' => $job->getId(),
            'taskGuid' => $job->getTaskGuid(),
            'userGuid' => $job->getUserGuid(),
            'sourceLang' => (int) $task->getSourceLang(),
            'targetLang' => (int) $task->getTargetLang(),
            'state' => $job->getState(),
            'role' => $job->getRole(),
            'workflowStepName' => $job->getWorkflowStepName(),
            'workflow' => $job->getWorkflow(),
            'segmentrange' => $job->getSegmentrange(),
            'segmentEditableCount' => (int) $job->getSegmentEditableCount(),
            'segmentFinishCount' => (int) $job->getSegmentFinishCount(),
            'usedState' => $job->getUsedState(),
            'deadlineDate' => $job->getDeadlineDate(),
            'assignmentDate' => $job->getAssignmentDate(),
            'finishedDate' => $job->getFinishedDate(),
            'trackchangesShow' => (bool) $job->getTrackchangesShow(),
            'trackchangesShowAll' => (bool) $job->getTrackchangesShowAll(),
            'trackchangesAcceptReject' => (bool) $job->getTrackchangesAcceptReject(),
            'type' => $job->getType()->name,
            'login' => $assignedUser->getLogin(),
            'firstName' => $assignedUser->getFirstName(),
            'surName' => $assignedUser->getSurName(),
            'longUserName' => $assignedUser->getUsernameLong(),
        ];

        if ($this->acl->isInAllowedRoles($viewer->getRoles(), Rights::ID, Rights::READ_AUTH_HASH)) {
            $row['staticAuthHash'] = $job->getStaticAuthHash();
        }

        return $row;
    }
}