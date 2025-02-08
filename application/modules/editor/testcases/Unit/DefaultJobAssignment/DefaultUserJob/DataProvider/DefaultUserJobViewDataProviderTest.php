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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultUserJob\DataProvider;

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultCoordinatorGroupJob\Model\DefaultCoordinatorGroupJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DataProvider\DefaultUserJobViewDataProvider;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use MittagQI\Translate5\Repository\DefaultCoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\User\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultUserJobViewDataProviderTest extends TestCase
{
    private MockObject|DefaultUserJobRepository $defaultUserJobRepository;

    private MockObject|DefaultCoordinatorGroupJobRepository $defaultCoordinatorGroupJobRepository;

    private MockObject|ActionPermissionAssertInterface $defaultUserJobPermissionAssert;

    private DefaultUserJobViewDataProvider $provider;

    public function setUp(): void
    {
        $this->defaultUserJobRepository = $this->createMock(DefaultUserJobRepository::class);
        $this->defaultCoordinatorGroupJobRepository = $this->createMock(DefaultCoordinatorGroupJobRepository::class);
        $this->defaultUserJobPermissionAssert = $this->createMock(ActionPermissionAssertInterface::class);

        $this->provider = new DefaultUserJobViewDataProvider(
            $this->defaultUserJobRepository,
            $this->defaultCoordinatorGroupJobRepository,
            $this->defaultUserJobPermissionAssert,
        );
    }

    public function testBuildViewForList(): void
    {
        $viewer = $this->createMock(User::class);

        $jobs = $this->getJobMocks();

        $groupJobJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $groupJobJob->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getGroupId', [], 13],
        ]);
        $this->defaultCoordinatorGroupJobRepository
            ->method('findDefaultCoordinatorGroupJobByDataJobId')
            ->willReturnCallback(
                fn (int $id) => (int) $jobs[0]->getId() === $id ? $groupJobJob : null
            );

        $this->defaultUserJobPermissionAssert->method('isGranted')->willReturnCallback(
            fn (DefaultJobAction $action, DefaultUserJob $job) => (int) $job->getId() !== 11
        );

        $list = $this->provider->buildViewForList($jobs, $viewer);

        self::assertCount(2, $list);
        $this->checkRow($list[0], $jobs[0], $groupJobJob);
        $this->checkRow($list[1], $jobs[2], null);
    }

    public function testGetListFor(): void
    {
        $viewer = $this->createMock(User::class);

        $jobs = $this->getJobMocks();

        $groupJobJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $groupJobJob->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getGroupId', [], 13],
        ]);
        $this->defaultCoordinatorGroupJobRepository
            ->method('findDefaultCoordinatorGroupJobByDataJobId')
            ->willReturnCallback(
                fn (int $id) => (int) $jobs[2]->getId() === $id ? $groupJobJob : null
            );

        $this->defaultUserJobRepository
            ->method('getDefaultUserJobsOfForCustomerAndWorkflow')
            ->willReturn($jobs);

        $this->defaultUserJobPermissionAssert->method('isGranted')->willReturnCallback(
            fn (DefaultJobAction $action, DefaultUserJob $job) => $job->getWorkflow() === 'default'
        );

        $list = $this->provider->getListFor(1, 'default', $viewer);

        self::assertCount(1, $list);
        $this->checkRow($list[0], $jobs[2], $groupJobJob);
    }

    public function testBuildJobView(): void
    {
        $jobs = $this->getJobMocks();

        $groupJobJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $groupJobJob->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getGroupId', [], 13],
        ]);
        $this->defaultCoordinatorGroupJobRepository
            ->method('findDefaultCoordinatorGroupJobByDataJobId')
            ->willReturn($groupJobJob);

        $row = $this->provider->buildJobView($jobs[1]);

        $this->checkRow($row, $jobs[1], $groupJobJob);
    }

    public function testBuildJobViewFromArray(): void
    {
        $jobs = $this->getJobMocks();

        $groupJobJob = $this->createMock(DefaultCoordinatorGroupJob::class);
        $groupJobJob->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getGroupId', [], 13],
        ]);
        $this->defaultCoordinatorGroupJobRepository
            ->method('findDefaultCoordinatorGroupJobByDataJobId')
            ->willReturn($groupJobJob);

        $this->defaultUserJobPermissionAssert->method('isGranted')->willReturn(true);

        $viewer = $this->createMock(User::class);

        $job = $jobs[1];

        $row = $this->provider->buildViewForList(
            [
                [
                    'id' => (int) $job->getId(),
                    'customerId' => (int) $job->getCustomerId(),
                    'userGuid' => $job->getUserGuid(),
                    'sourceLang' => (int) $job->getSourceLang(),
                    'targetLang' => (int) $job->getTargetLang(),
                    'workflow' => $job->getWorkflow(),
                    'workflowStepName' => $job->getWorkflowStepName(),
                    'deadlineDate' => (float) $job->getDeadlineDate(),
                    'trackchangesShow' => (bool) $job->getTrackchangesShow(),
                    'trackchangesShowAll' => (bool) $job->getTrackchangesShowAll(),
                    'trackchangesAcceptReject' => (bool) $job->getTrackchangesAcceptReject(),
                    'type' => TypeEnum::Coordinator->value,
                    'groupId' => $groupJobJob->getGroupId(),
                    'isCoordinatorGroupJob' => true,
                ],
            ],
            $viewer
        );

        $this->checkRow($row[0], $job, $groupJobJob);
    }

    private function checkRow(array $row, DefaultUserJob $job, ?DefaultCoordinatorGroupJob $groupJob): void
    {
        self::assertEquals(
            [
                'id' => (int) $job->getId(),
                'customerId' => (int) $job->getCustomerId(),
                'userGuid' => $job->getUserGuid(),
                'sourceLang' => (int) $job->getSourceLang(),
                'targetLang' => (int) $job->getTargetLang(),
                'workflow' => $job->getWorkflow(),
                'workflowStepName' => $job->getWorkflowStepName(),
                'deadlineDate' => (float) $job->getDeadlineDate(),
                'trackchangesShow' => (bool) $job->getTrackchangesShow(),
                'trackchangesShowAll' => (bool) $job->getTrackchangesShowAll(),
                'trackchangesAcceptReject' => (bool) $job->getTrackchangesAcceptReject(),
                'type' => $groupJob ? TypeEnum::Coordinator->value : TypeEnum::Editor->value,
                'groupId' => $groupJob?->getGroupId(),
                'isCoordinatorGroupJob' => $groupJob !== null,
            ],
            $row
        );
    }

    /**
     * @return DefaultUserJob[]
     */
    private function getJobMocks(): array
    {
        $job1 = $this->createMock(DefaultUserJob::class);
        $job1->method('__call')->willReturnMap([
            ['getId', [], 10],
            ['getCustomerId', [], 11],
            ['getUserGuid', [], '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}'],
            ['getSourceLang', [], 4],
            ['getTargetLang', [], 5],
            ['getWorkflow', [], 'complex'],
            ['getWorkflowStepName', [], 'translation1'],
            ['getDeadlineDate', [], 2.3],
            ['getTrackchangesShow', [], '1'],
            ['getTrackchangesShowAll', [], '0'],
            ['getTrackchangesAcceptReject', [], '1'],
        ]);

        $job2 = $this->createMock(DefaultUserJob::class);
        $job2->method('__call')->willReturnMap([
            ['getId', [], 11],
            ['getCustomerId', [], 12],
            ['getUserGuid', [], '{633a9811-a1f6-4fa8-81f7-2206d7a93ba5}'],
            ['getSourceLang', [], 4],
            ['getTargetLang', [], 5],
            ['getWorkflow', [], 'complex'],
            ['getWorkflowStepName', [], 'translation2'],
            ['getDeadlineDate', [], 2.5],
            ['getTrackchangesShow', [], '0'],
            ['getTrackchangesShowAll', [], '1'],
            ['getTrackchangesAcceptReject', [], '0'],
        ]);

        $job3 = $this->createMock(DefaultUserJob::class);
        $job3->method('__call')->willReturnMap([
            ['getId', [], 12],
            ['getCustomerId', [], 13],
            ['getUserGuid', [], '{633a9811-a1f6-4fa8-81f7-2206d7a93ba6}'],
            ['getSourceLang', [], 14],
            ['getTargetLang', [], 15],
            ['getWorkflow', [], 'default'],
            ['getWorkflowStepName', [], 'review'],
            ['getDeadlineDate', [], .5],
            ['getTrackchangesShow', [], '1'],
            ['getTrackchangesShowAll', [], '1'],
            ['getTrackchangesAcceptReject', [], '1'],
        ]);

        return [$job1, $job2, $job3];
    }
}
