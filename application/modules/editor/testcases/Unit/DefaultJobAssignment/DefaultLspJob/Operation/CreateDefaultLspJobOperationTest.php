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

use Exception;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Exception\NotLspCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\CreateDefaultLspJobOperation;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DTO\NewDefaultLspJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\LSP\Exception\CustomerDoesNotBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\LSP\Validation\LspCustomerAssociationValidator;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateDefaultLspJobOperationTest extends TestCase
{
    private MockObject|DefaultLspJobRepository $defaultLspJobRepository;

    private MockObject|DefaultUserJobRepository $defaultUserJobRepository;

    private MockObject|JobCoordinatorRepository $coordinatorRepository;

    private MockObject|LspCustomerAssociationValidator $lspCustomerAssociationValidator;

    private CreateDefaultLspJobOperation $operation;

    public function setUp(): void
    {
        $this->defaultLspJobRepository = $this->createMock(DefaultLspJobRepository::class);
        $this->defaultUserJobRepository = $this->createMock(DefaultUserJobRepository::class);
        $this->coordinatorRepository = $this->createMock(JobCoordinatorRepository::class);
        $this->lspCustomerAssociationValidator = $this->createMock(LspCustomerAssociationValidator::class);

        $this->operation = new CreateDefaultLspJobOperation(
            $this->defaultLspJobRepository,
            $this->defaultUserJobRepository,
            $this->coordinatorRepository,
            $this->lspCustomerAssociationValidator,
        );
    }

    public function testThrowsExceptionIfUserIsNotCoordinator(): void
    {
        $this->coordinatorRepository->method('findByUserGuid')->willReturn(null);

        $this->expectException(OnlyCoordinatorCanBeAssignedToLspJobException::class);

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->operation->assignJob($dto);
    }

    public function testThrowsExceptionIfCustomerDoesNotBelongToLsp(): void
    {
        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $this->lspCustomerAssociationValidator
            ->method('assertCustomersAreSubsetForLSP')
            ->willThrowException(new CustomerDoesNotBelongToLspException($dto->customerId, (int) $lsp->getId()));

        $this->expectException(NotLspCustomerException::class);

        $this->operation->assignJob($dto);
    }

    public function testDeletesUserJobOnLspJobSaveError(): void
    {
        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $exception = new Exception();

        $this->defaultLspJobRepository->method('save')->willThrowException($exception);

        $this->defaultUserJobRepository->expects(self::once())->method('delete');

        $this->expectExceptionObject($exception);

        $this->operation->assignJob($dto);
    }

    public function testAssign(): void
    {
        $user = $this->createMock(User::class);
        $lsp = $this->createMock(LanguageServiceProvider::class);
        $lsp->method('__call')->willReturnMap([
            ['getId', [], '1'],
        ]);
        $jc = new JobCoordinator('{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}', $user, $lsp);

        $dto = new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $this->coordinatorRepository->method('findByUserGuid')->willReturn($jc);

        $job = $this->operation->assignJob($dto);

        self::assertSame($dto->customerId, $job->getCustomerId());
        self::assertSame($dto->workflow->workflow, $job->getWorkflow());
        self::assertSame($dto->workflow->workflowStepName, $job->getWorkflowStepName());
        self::assertSame($dto->targetLanguageId, $job->getTargetLang());
        self::assertSame($dto->sourceLanguageId, $job->getSourceLang());
        self::assertSame((int) $lsp->getId(), (int) $job->getLspId());
    }
}
