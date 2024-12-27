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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultLspJob\Operation;

use editor_Models_UserAssocDefault as DefaultUserJob;
use editor_Workflow_Default;
use editor_Workflow_Manager;
use Exception;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\UpdateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DTO\UpdateDefaultJobDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\LSP\Exception\CoordinatorDontBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend_Db_Adapter_Abstract;

class UpdateDefaultLspJobOperationTest extends TestCase
{
    private MockObject|Zend_Db_Adapter_Abstract $db;

    private MockObject|DefaultLspJobRepository $defaultLspJobRepository;

    private MockObject|DefaultUserJobRepository $defaultUserJobRepository;

    private readonly MockObject|JobCoordinatorRepository $coordinatorRepository;

    private readonly MockObject|editor_Workflow_Manager $workflowManager;

    private UpdateDefaultLspJobOperation $operation;

    public function setUp(): void
    {
        $this->db = $this->createMock(Zend_Db_Adapter_Abstract::class);
        $this->defaultLspJobRepository = $this->createMock(DefaultLspJobRepository::class);
        $this->defaultUserJobRepository = $this->createMock(DefaultUserJobRepository::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->workflowManager = $this->createMock(editor_Workflow_Manager::class);

        $this->operation = new UpdateDefaultLspJobOperation(
            $this->db,
            $this->defaultLspJobRepository,
            $this->defaultUserJobRepository,
            $this->coordinatorRepository,
            $this->workflowManager,
        );
    }

    public function testThrowsExceptionOnAttemptToSetNotCoordinator(): void
    {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $this->coordinatorRepository->method('findByUserGuid')->willReturn(null);

        $this->expectException(OnlyCoordinatorCanBeAssignedToLspJobException::class);

        $dto = new UpdateDefaultJobDto(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $defaultLspJob = new DefaultLspJob();

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testThrowsExceptionOnAttemptToSetCoordinatorOfDifferentLsp(): void
    {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $this->expectException(CoordinatorDontBelongToLspException::class);

        $dto = new UpdateDefaultJobDto(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
        );

        $defaultLspJob = new DefaultLspJob();
        $defaultLspJob->setLspId(2);

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testThrowsExceptionOnAttemptToSetWorkflowStepNotFromTaskWorkflow(): void
    {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $workflow = $this->createMock(editor_Workflow_Default::class);
        $workflow->method('getUsableSteps')->willReturn(['translation']);

        $this->workflowManager->method('getCached')->willReturn($workflow);

        $this->expectException(InvalidWorkflowStepProvidedException::class);

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            'un-existent',
            null,
            null,
            null,
            null,
        );

        $defaultLspJob = new DefaultLspJob();

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testRollsBackOnLspJobSaveException(): void
    {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $exception = new Exception();

        $this->expectExceptionObject($exception);

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            null,
            1.0,
            null,
            null,
            null,
        );

        $defaultLspJob = new DefaultLspJob();

        $this->defaultLspJobRepository->method('save')->willThrowException($exception);

        $this->db->expects(self::once())->method('beginTransaction');
        $this->db->expects(self::once())->method('rollBack');

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testRollsBackOnUserJobSaveException(): void
    {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $exception = new Exception();

        $this->expectExceptionObject($exception);

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            null,
            1.0,
            null,
            null,
            null,
        );

        $defaultLspJob = new DefaultLspJob();

        $this->defaultUserJobRepository->method('save')->willThrowException($exception);

        $this->db->expects(self::once())->method('beginTransaction');
        $this->db->expects(self::once())->method('rollBack');

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testCommitsOnOk(): void
    {
        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $dto = new UpdateDefaultJobDto(
            null,
            null,
            null,
            null,
            1.0,
            null,
            null,
            null,
        );

        $defaultLspJob = new DefaultLspJob();

        $this->db->expects(self::once())->method('beginTransaction');
        $this->db->expects(self::once())->method('commit');

        $this->operation->updateJob($defaultLspJob, $dto);
    }

    public function testUpdate(): void
    {
        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $defaultUserJob = new DefaultUserJob();
        $defaultUserJob->setId(1);

        $this->defaultUserJobRepository->method('get')->willReturn($defaultUserJob);

        $dto = new UpdateDefaultJobDto(
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            'complex',
            1.0,
            true,
            true,
            true,
        );

        $workflow = $this->createMock(editor_Workflow_Default::class);
        $workflow->method('getUsableSteps')->willReturn([$dto->workflowStepName]);

        $this->workflowManager->method('getCached')->willReturn($workflow);

        $defaultLspJob = new DefaultLspJob();
        $defaultLspJob->setLspId(1);

        $this->operation->updateJob($defaultLspJob, $dto);

        self::assertSame($dto->sourceLanguageId, $defaultLspJob->getSourceLang());
        self::assertSame($dto->targetLanguageId, $defaultLspJob->getTargetLang());
        self::assertSame($dto->workflowStepName, $defaultLspJob->getWorkflowStepName());
    }
}
