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

namespace MittagQI\Translate5\LanguageResource\TaskTm;

use editor_Services_Manager;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeEvent;
use MittagQI\Translate5\LanguageResource\Operation\AssociateTaskOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use Zend_EventManager_Event as ZendEvent;
use Zend_EventManager_SharedEventManager as ZendEventManager;

class EventListener
{
    public function __construct(
        private readonly ZendEventManager $eventManager,
        private readonly editor_Services_Manager $serviceManager,
        private readonly TaskTmRepository $taskTmRepository,
        private readonly AssociateTaskOperation $associateTaskOperation,
        private readonly TaskRepository $taskRepository,
    ) {
    }

    public static function create(ZendEventManager $eventManager): self
    {
        return new self(
            $eventManager,
            new editor_Services_Manager(),
            new TaskTmRepository(),
            AssociateTaskOperation::create(),
            TaskRepository::create(),
        );
    }

    public function atachAll()
    {
        $this->eventManager->attach(
            EventDispatcher::class,
            LanguageResourceTaskAssociationChangeEvent::class,
            $this->createTaskTmOnLanguageResourceTaskAssociationChange()
        );
    }

    /**
     * @phpstan-return callable(ZendEvent)
     */
    private function createTaskTmOnLanguageResourceTaskAssociationChange(): callable
    {
        return function (ZendEvent $zendEvent) {
            /** @var LanguageResourceTaskAssociationChangeEvent $event */
            $event = $zendEvent->getParam('event');
            $resource = $event->languageResource->getResource();

            $taskTmCreateOperation = $this->serviceManager->getCreateTaskTmOperation($resource->getServiceType());

            // Nothing to do if resource does not support task TM
            if (null === $taskTmCreateOperation) {
                return;
            }

            if (! $this->taskSupportsTaskTm($event->taskGuid)) {
                return;
            }

            if (! $this->hasWritableTmOfType(
                $event->taskGuid,
                $resource->getServiceType()
            )) {
                return;
            }

            $taskTm = $this->taskTmRepository->findOfTypeCreatedForTask(
                $event->taskGuid,
                $resource->getServiceType()
            );

            // Nothing to do if we're dealing with the task TM itself
            if ($taskTm && $event->languageResource->getId() === $taskTm->getId()) {
                return;
            }

            if (! $taskTm) {
                $taskTm = $taskTmCreateOperation->createTaskTm($event->taskGuid, $resource);
                $this->associateTaskOperation->associate((int) $taskTm->getId(), $event->taskGuid, true);
            }
        };
    }

    private function hasWritableTmOfType(string $taskGuid, string $serviceType): bool
    {
        // check if there is a writable tm of the given type
        return $this->taskTmRepository->hasWritableOfType($taskGuid, $serviceType);
    }

    private function taskSupportsTaskTm(string $taskGuid): bool
    {
        try {
            $task = $this->taskRepository->getByGuid($taskGuid);
        } catch (InexistentTaskException) {
            return false;
        }

        return $task->getTaskType()->supportsTaskTm();
    }
}
