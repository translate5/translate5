<?php

namespace MittagQI\Translate5\Test\Unit\Job;

use editor_Workflow_Default as Workflow;
use MittagQI\Translate5\JobAssignment\JobSorterService;
use PHPUnit\Framework\TestCase;

class JobSorterTest extends TestCase
{
    /**
     * @var JobSorterService
     */
    private $jobSorter;

    protected function setUp(): void
    {
        $this->jobSorter = JobSorterService::create();
    }

    public function testSortJobsByWorkflowPosition(): void
    {
        // Create a mock for Workflow
        $workflow = $this->createMock(Workflow::class);

        // Configure the workflow mock to return a specific step chain
        $workflow->expects($this->once())
            ->method('getStepChain')
            ->willReturn(['translate', 'review', 'final']);

        // Test data - jobs in random order
        $jobs = [
            [
                'id' => 1,
                'workflowStepName' => 'review',
            ],
            [
                'id' => 2,
                'workflowStepName' => 'translate',
            ],
            [
                'id' => 3,
                'workflowStepName' => 'final',
            ],
            [
                'id' => 4,
                'workflowStepName' => 'unknown',
            ], // Not in workflow
        ];

        // Expected result - jobs sorted by workflow position
        $expected = [
            [
                'id' => 2,
                'workflowStepName' => 'translate',
            ],
            [
                'id' => 1,
                'workflowStepName' => 'review',
            ],
            [
                'id' => 3,
                'workflowStepName' => 'final',
            ],
            [
                'id' => 4,
                'workflowStepName' => 'unknown',
            ],
        ];

        $result = $this->jobSorter->sortJobsByWorkflowPosition($jobs, $workflow);

        $this->assertEquals($expected, $result);
    }

    public function testSortJobsWithMissingWorkflowStepName(): void
    {
        // Create a mock for Workflow
        $workflow = $this->createMock(Workflow::class);

        // Configure the workflow mock to return a specific step chain
        $workflow->expects($this->once())
            ->method('getStepChain')
            ->willReturn(['translate', 'review', 'final']);

        // Test data - some jobs missing workflowStepName
        $jobs = [
            [
                'id' => 1,
                'workflowStepName' => 'review',
            ],
            [
                'id' => 2,
            ], // Missing workflowStepName
            [
                'id' => 3,
                'workflowStepName' => 'translate',
            ],
        ];

        // Expected result - jobs with workflow steps first, then others
        $expected = [
            [
                'id' => 3,
                'workflowStepName' => 'translate',
            ],
            [
                'id' => 1,
                'workflowStepName' => 'review',
            ],
            [
                'id' => 2,
            ], // Maintains position at the end
        ];

        $result = $this->jobSorter->sortJobsByWorkflowPosition($jobs, $workflow);

        $this->assertEquals($expected, $result);
    }
}
