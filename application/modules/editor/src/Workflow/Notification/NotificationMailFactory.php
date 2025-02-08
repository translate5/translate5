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

namespace MittagQI\Translate5\Workflow\Notification;

use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use ZfExtended_TemplateBasedMail as Mail;

class NotificationMailFactory
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
            UserJobRepository::create(),
        );
    }

    public function createDeletedCompetitorsNotificationMail(
        string $role,
        ?string $pmGuid,
        array $parameters,
    ): Mail {
        $mailer = new Mail();
        $mailer->setParameters($parameters);
        $mailer->setTemplate($this->getMailTemplate($role, 'notifyCompetitiveDeleted'));

        $pm = $this->userRepository->findByGuid($pmGuid);
        // Add reply-to with project-manager mail to all automated workflow-mails
        if (null !== $pm) {
            $mailer->setReplyTo($pm->getEmail(), $pm->getFirstName() . ' ' . $pm->getSurName());
        }

        return $mailer;
    }

    /**
     * Adds the users of the given cc/bcc step config to the email - if receiverStep is configured in config
     */
    public function addCopyReceivers(
        Mail $mailer,
        string $taskGuid,
        array $receiverStepMap,
        string $receiverStep,
        bool $bcc = false,
    ): void {
        $users = [];

        foreach ($receiverStepMap as $recStep => $steps) {
            $users[] = match ($recStep) {
                '*', $receiverStep => $this->getUsersByWorkflowStepNames($taskGuid, $steps),
                'byUserLogin' => $this->getUsersByLogins($steps),
                default => [],
            };
        }

        $users = array_merge(...$users);

        foreach ($users as $userData) {
            if ($bcc) {
                $mailer->addBcc($userData['email']);
            } else {
                $mailer->addCc($userData['email'], $userData['firstName'] . ' ' . $userData['surName']);
            }
        }
    }

    private function getUsersByWorkflowStepNames(string $taskGuid, array $workflowStepNames): array
    {
        $users = [];

        foreach ($workflowStepNames as $stepName) {
            $users[] = $this->userJobRepository->loadUsersOfTaskWithStep($taskGuid, $stepName, ['deadlineDate']);
        }

        return array_merge(...$users);
    }

    private function getUsersByLogins(array $logins)
    {
        $users = [];

        foreach ($logins as $login) {
            $userModel = $this->userRepository->findByLogin($login);

            $users[] = (array) $userModel?->getDataObject();
        }

        return array_filter($users);
    }

    private function getMailTemplate(string $role, string $template): string
    {
        return 'workflow/' . $role . '/' . $template . '.phtml';
    }
}
