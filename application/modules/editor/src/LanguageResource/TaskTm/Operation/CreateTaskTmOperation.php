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

namespace MittagQI\Translate5\LanguageResource\TaskTm\Operation;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Resource as Resource;
use editor_Models_Task as Task;
use editor_Services_Manager as ServiceManager;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\Operation\CreateLanguagePairOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmTaskAssociationRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\TaskTmTaskAssociation;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CreateTaskTmOperation
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly CreateLanguagePairOperation $createLanguagePairOperation,
        private readonly CustomerAssocService $customerAssocService,
        private readonly ServiceManager $serviceManager,
        private readonly TaskTmTaskAssociationRepository $taskTmTaskAssociationRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            new LanguageResourceRepository(),
            CreateLanguagePairOperation::create(),
            CustomerAssocService::create(),
            new ServiceManager(),
            new TaskTmTaskAssociationRepository(),
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function createTaskTm(string $taskGuid, Resource $resource): LanguageResource
    {
        $task = $this->taskRepository->getByGuid($taskGuid);

        $languageResource = $this->createLanguageResource($resource, $task);
        $this->createTaskTmAssociation($languageResource, $task);
        $this->customerAssocService->associate((int) $languageResource->getId(), (int) $task->getCustomerId());
        $this->createLanguagePairOperation->createLanguagePair(
            (int) $languageResource->getId(),
            (int) $task->getSourceLang(),
            (int) $task->getTargetLang()
        );

        $this->serviceManager->getConnector($languageResource)->addTm();

        $this->serviceManager->getTmConversionService($resource->getServiceType())?->setRulesHash(
            $languageResource,
            (int) $task->getSourceLang(),
            (int) $task->getTargetLang()
        );

        return $languageResource;
    }

    private function createTaskTmAssociation(LanguageResource $languageResource, Task $task): void
    {
        $association = ZfExtended_Factory::get(TaskTmTaskAssociation::class);
        $association->setLanguageResourceId((int) $languageResource->getId());
        $association->setTaskId((int) $task->getId());
        $association->setTaskGuid($task->getTaskGuid());
        $association->setServiceType($languageResource->getServiceType());
        $this->taskTmTaskAssociationRepository->save($association);
    }

    private function createLanguageResource(Resource $resource, Task $task): LanguageResource
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->initByResource($resource);
        $languageResource->setName("Task TM id '{$task->getId()}'");
        $this->languageResourceRepository->save($languageResource);

        return $languageResource;
    }
}
