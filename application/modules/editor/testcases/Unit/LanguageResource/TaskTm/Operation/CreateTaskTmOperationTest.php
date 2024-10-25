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

namespace MittagQI\Translate5\Test\Unit\LanguageResource\TaskTm\Operation;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Resource as Resource;
use editor_Models_Task as Task;
use editor_Services_Manager as ServiceManager;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionServiceInterface;
use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\LanguageResource\CustomerAssoc\CustomerAssocService;
use MittagQI\Translate5\LanguageResource\Operation\CreateLanguagePairOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Operation\CreateTaskTmOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmTaskAssociationRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\TaskTmTaskAssociation;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateTaskTmOperationTest extends TestCase
{
    private MockObject|Resource $resource;

    private MockObject|TaskRepository $taskRepository;

    private MockObject|LanguageResourceRepository $languageResourceRepository;

    private MockObject|CreateLanguagePairOperation $createLanguagePairOperation;

    private MockObject|CustomerAssocService $customerAssocService;

    private MockObject|Task $task;

    private MockObject|ServiceManager $serviceManager;

    private MockObject|TaskTmTaskAssociationRepository $taskTmTaskAssociationRepository;

    protected function setUp(): void
    {
        $this->resource = $this->createMock(Resource::class);
        $this->taskRepository = $this->createMock(TaskRepository::class);
        $this->languageResourceRepository = $this->createMock(LanguageResourceRepository::class);
        $this->createLanguagePairOperation = $this->createMock(CreateLanguagePairOperation::class);
        $this->customerAssocService = $this->createMock(CustomerAssocService::class);
        $this->task = $this->createMock(Task::class);
        $this->serviceManager = $this->createMock(ServiceManager::class);
        $this->taskTmTaskAssociationRepository = $this->createMock(TaskTmTaskAssociationRepository::class);
    }

    public function testCreateTaskTm(): void
    {
        $taskGuid = bin2hex(random_bytes(16));
        $serviceType = bin2hex(random_bytes(8));
        $taskId = random_int(1, 100);
        $languageResourceId = random_int(1, 100);
        $sourceLanguageId = random_int(1, 100);
        $targetLanguageId = random_int(1, 100);
        $customerId = random_int(1, 100);

        $this->resource->method('getServiceType')->willReturn($serviceType);
        $this->task->method('__call')->willReturnMap([
            ['getId', [], $taskId],
            ['getTaskGuid', [], $taskGuid],
            ['getCustomerId', [], $customerId],
            ['getSourceLang', [], $sourceLanguageId],
            ['getTargetLang', [], $targetLanguageId],
        ]);
        $this->taskRepository->method('getByGuid')->with($taskGuid)->willReturn($this->task);

        $this->languageResourceRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static function (LanguageResource $languageResource) use ($taskId, $serviceType, $languageResourceId) {
                    self::assertSame("Task TM id '{$taskId}'", $languageResource->getName());
                    self::assertSame($serviceType, $languageResource->getServiceType());

                    $languageResource->setId($languageResourceId);

                    return true;
                }
            ));
        $this->taskTmTaskAssociationRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(
                static function (TaskTmTaskAssociation $association) use (
                    $taskId,
                    $languageResourceId,
                    $taskGuid,
                    $serviceType,
                ) {
                    self::assertSame($languageResourceId, $association->getLanguageResourceId());
                    self::assertSame($taskId, $association->getTaskId());
                    self::assertSame($taskGuid, $association->getTaskGuid());
                    self::assertSame($serviceType, $association->getServiceType());

                    return true;
                }
            ));
        $this->customerAssocService->expects(self::once())
            ->method('associate')
            ->with($languageResourceId, $customerId);
        $this->createLanguagePairOperation->expects(self::once())
            ->method('createLanguagePair')
            ->with($languageResourceId, $sourceLanguageId, $targetLanguageId);
        $connector = $this->createMock(FileBasedInterface::class);
        $this->serviceManager->method('getConnector')->willReturn($connector);

        $connector->expects(self::once())->method('addTm');

        $tmConversionServiceMock = $this->createMock(TmConversionServiceInterface::class);
        $tmConversionServiceMock->expects(self::once())->method('setRulesHash');
        $this->serviceManager->expects(self::once())
            ->method('getTmConversionService')
            ->with($serviceType)
            ->willReturn($tmConversionServiceMock);

        $TaskTmService = $this->createTaskTmService();
        $TaskTmService->createTaskTm($taskGuid, $this->resource);
    }

    private function createTaskTmService(): CreateTaskTmOperation
    {
        return new CreateTaskTmOperation(
            $this->taskRepository,
            $this->languageResourceRepository,
            $this->createLanguagePairOperation,
            $this->customerAssocService,
            $this->serviceManager,
            $this->taskTmTaskAssociationRepository
        );
    }
}
