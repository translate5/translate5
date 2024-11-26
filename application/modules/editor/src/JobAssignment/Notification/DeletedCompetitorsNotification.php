<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\JobAssignment\Notification;

use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc as UserJob;
use editor_Workflow_Manager as WorkflowManager;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\Workflow\Notification\DTO\DeletedJobDto;
use MittagQI\Translate5\Workflow\Notification\NotificationMailFactory;

class DeletedCompetitorsNotification
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $userActionAssert,
        private readonly UserRepository $userRepository,
        private readonly WorkflowManager $workflowManager,
        private readonly NotificationMailFactory $notificationMailFactory,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserActionPermissionAssert::create(),
            new UserRepository(),
            new WorkflowManager(),
            NotificationMailFactory::create(),
        );
    }

    public function sendNotification(
        Task $task,
        DeletedJobDto $deletedJob,
        User $responsibleUser,
        bool $anonymizeUser
    ): void {
        $taskConfig = $task->getConfig();

        if ($taskConfig->runtimeOptions->workflow?->disableNotifications) {
            return;
        }

        $params = [];
        $deletedUser = $this->userRepository->findByGuid($deletedJob->userGuid);

        if (null === $deletedUser) {
            return;
        }

        $context = new PermissionAssertContext($deletedUser);

        if (! $anonymizeUser && $this->userActionAssert->isGranted(UserAction::Read, $responsibleUser, $context)) {
            $params = [
                //we do not pass the whole userObject to keep data private
                'responsibleUser' => [
                    'surName' => $responsibleUser->getSurName(),
                    'firstName' => $responsibleUser->getFirstName(),
                    'login' => $responsibleUser->getLogin(),
                    'email' => $responsibleUser->getEmail(),
                ],
            ];
        }

        $workflow = $this->workflowManager->get($task->getWorkflow());

        $labels = $workflow->getLabels(false);
        $steps = $workflow->getSteps();

        $params['task'] = $task;
        $params['role'] = $labels[array_search($deletedJob->workflowStepName, $steps)];

        $mailer = $this->notificationMailFactory->createDeletedCompetitorsNotificationMail(
            $deletedJob->role,
            $task->getPmGuid(),
            $params,
        );

        $mailer->sendToUser($deletedUser);
    }
}