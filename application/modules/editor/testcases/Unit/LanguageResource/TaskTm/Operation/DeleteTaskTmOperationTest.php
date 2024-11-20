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
use MittagQI\Translate5\LanguageResource\Operation\DeleteLanguageResourceOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Operation\DeleteTaskTmOperation;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmTaskAssociationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteTaskTmOperationTest extends TestCase
{
    private MockObject|TaskTmRepository $taskTmRepository;

    private MockObject|TaskTmTaskAssociationRepository $taskTmTaskAssociationRepository;

    private MockObject|DeleteLanguageResourceOperation $deleteLanguageResourceOperation;

    public function setUp(): void
    {
        $this->taskTmRepository = $this->createMock(TaskTmRepository::class);
        $this->taskTmTaskAssociationRepository = $this->createMock(TaskTmTaskAssociationRepository::class);
        $this->deleteLanguageResourceOperation = $this->createMock(DeleteLanguageResourceOperation::class);
    }

    public function testRemoveTaskTmsForTaskNothingToDelete(): void
    {
        $this->taskTmTaskAssociationRepository->expects(self::never())
            ->method('deleteByTaskGuidAndTm');

        $this->taskTmRepository->expects(self::once())
            ->method('getAllCreatedForTask')
            ->with('taskGuid')
            ->willReturn([]);

        $this->deleteLanguageResourceOperation->expects(self::never())
            ->method('delete');

        $operation = new DeleteTaskTmOperation(
            $this->taskTmRepository,
            $this->taskTmTaskAssociationRepository,
            $this->deleteLanguageResourceOperation
        );
        $operation->removeTaskTmsForTask('taskGuid');
    }

    public function testRemoveTaskTmsForTask(): void
    {
        $taskTm1 = $this->createMock(LanguageResource::class);
        $taskTm1
            ->method('__call')
            ->willReturnMap([
                ['getId', [], 1],
            ]);
        $taskTm2 = $this->createMock(LanguageResource::class);
        $taskTm2
            ->method('__call')
            ->willReturnMap([
                ['getId', [], 2],
            ]);

        $this->taskTmRepository->expects(self::once())
            ->method('getAllCreatedForTask')
            ->with('taskGuid')
            ->willReturn([$taskTm1, $taskTm2]);

        $this->taskTmTaskAssociationRepository->expects(self::exactly(2))
            ->method('deleteByTaskGuidAndTm');

        $this->deleteLanguageResourceOperation->expects(self::atLeast(1))
            ->method('delete');

        $operation = new DeleteTaskTmOperation(
            $this->taskTmRepository,
            $this->taskTmTaskAssociationRepository,
            $this->deleteLanguageResourceOperation
        );
        $operation->removeTaskTmsForTask('taskGuid');
    }
}
