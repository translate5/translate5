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

namespace MittagQI\Translate5\Test\Unit\Workflow;

use editor_Models_TaskUserAssoc as UserJob;
use editor_Workflow_Default;
use MittagQI\Translate5\Repository\CoordinatorGroupJobRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Workflow\WorkflowStepCalculator;
use PHPUnit\Framework\TestCase;

class WorkflowStepCalculatorTest extends TestCase
{
    /**
     * @dataProvider casesProvider
     */
    public function testGetValidTaskWorkflowStep(
        string $currentTaskStep,
        ?string $expected,
        bool $taskHasNotFinishedJob,
        array $jobs
    ): void {
        $taskRepository = $this->createMock(TaskRepository::class);
        $coordinatorGroupJobRepository = $this->createMock(CoordinatorGroupJobRepository::class);

        $userJobRepository = $this->createMock(UserJobRepository::class);
        $userJobRepository->method('taskHasNotFinishedJob')
            ->willReturn($taskHasNotFinishedJob);
        $userJobRepository->method('getTaskJobs')
            ->willReturn($jobs);

        $task = $this->createMock(\editor_Models_Task::class);
        $task->method('__call')->willReturn($currentTaskStep);

        $taskRepository->method('getByGuid')
            ->with('some-task-guid')
            ->willReturn($task);

        $workflow = $this->createMock(editor_Workflow_Default::class);
        $workflow->method('getName')->willReturn('default');
        $workflow->method('getValidStates')
            ->willReturn([
                'no workflow' => [
                    'translation' => [
                        'waiting',
                        'unconfirmed',
                    ],
                    'reviewing' => [
                        'waiting',
                        'unconfirmed',
                    ],
                    'translatorCheck' => [
                        'waiting',
                        'unconfirmed',
                    ],
                ],
                'translation' => [
                    'translation' => [
                        'open',
                        'edit',
                        'view',
                        'unconfirmed',
                    ],
                    'reviewing' => [
                        'waiting',
                        'unconfirmed',
                    ],
                    'translatorCheck' => [
                        'waiting',
                        'unconfirmed',
                    ],
                ],
                'reviewing' => [
                    'translation' => [
                        'finished',
                    ],
                    'reviewing' => [
                        'open',
                        'edit',
                        'view',
                        'unconfirmed',
                    ],
                    'translatorCheck' => [
                        'waiting',
                        'unconfirmed',
                    ],
                ],
                'translatorCheck' => [
                    'translation' => [
                        'finished',
                    ],
                    'reviewing' => [
                        'finished',
                    ],
                    'translatorCheck' => [
                        'open',
                        'edit',
                        'view',
                        'unconfirmed',
                    ],
                ],
                'workflowEnded' => [
                    'translation' => [
                        'finished',
                    ],
                    'reviewing' => [
                        'finished',
                    ],
                    'translatorCheck' => [
                        'finished',
                    ],
                ],
            ]);

        $calculator = new WorkflowStepCalculator(
            $taskRepository,
            $userJobRepository,
            $coordinatorGroupJobRepository,
        );

        $result = $calculator->getValidTaskWorkflowStep($workflow, 'some-task-guid');

        self::assertSame($expected, $result);
    }

    public function casesProvider(): array
    {
        return [
            'no workflow, all waiting' => [
                'currentTaskStep' => editor_Workflow_Default::STEP_NO_WORKFLOW,
                'expected' => editor_Workflow_Default::STEP_NO_WORKFLOW,
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'waiting'),
                    $this->createUserJob('reviewing', 'waiting'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'any step, no jobs' => [
                'currentTaskStep' => 'translation',
                'expected' => editor_Workflow_Default::STEP_NO_WORKFLOW,
                'taskHasNotFinishedJob' => false,
                'jobs' => [],
            ],
            'any step, all waiting' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translation',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'waiting'),
                    $this->createUserJob('reviewing', 'waiting'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'translation, translation open, next waiting' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translation',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'open'),
                    $this->createUserJob('reviewing', 'waiting'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'translation, translation finished, next waiting' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translation',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'waiting'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'translation, translation finished, next unconfirmed' => [
                'currentTaskStep' => 'translation',
                'expected' => 'reviewing',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'unconfirmed'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'translation, translation finished, next open' => [
                'currentTaskStep' => 'translation',
                'expected' => 'reviewing',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'open'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'translation, translation open, next finished' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translation',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'open'),
                    $this->createUserJob('reviewing', 'finished'),
                    $this->createUserJob('translatorCheck', 'waiting'),
                ],
            ],
            'translation, translation open, last finished' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translation',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'open'),
                    $this->createUserJob('reviewing', 'waiting'),
                    $this->createUserJob('translatorCheck', 'finished'),
                ],
            ],
            'translation, translation finished, reviewing open, last finished' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translation',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'open'),
                    $this->createUserJob('translatorCheck', 'finished'),
                ],
            ],
            'translation, translation finished, reviewing finished, translatorCheck open' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translatorCheck',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'finished'),
                    $this->createUserJob('translatorCheck', 'open'),
                ],
            ],
            'reviewing, translation finished, reviewing finished, translatorCheck open' => [
                'currentTaskStep' => 'translation',
                'expected' => 'translatorCheck',
                'taskHasNotFinishedJob' => true,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'finished'),
                    $this->createUserJob('translatorCheck', 'open'),
                ],
            ],
            'all finish' => [
                'currentTaskStep' => 'translation',
                'expected' => editor_Workflow_Default::STEP_WORKFLOW_ENDED,
                'taskHasNotFinishedJob' => false,
                'jobs' => [
                    $this->createUserJob('translation', 'finished'),
                    $this->createUserJob('reviewing', 'finished'),
                    $this->createUserJob('translatorCheck', 'finished'),
                ],
            ],
        ];
    }

    private function createUserJob(string $workflowStepName, string $state): UserJob
    {
        $userJob = $this->createMock(UserJob::class);
        $userJob->method('__call')->willReturnMap(
            [
                ['getWorkflowStepName', [], $workflowStepName],
                ['getState', [], $state],
            ]
        );

        return $userJob;
    }
}
