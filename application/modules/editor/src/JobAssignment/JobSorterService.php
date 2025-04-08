<?php

namespace MittagQI\Translate5\JobAssignment;

use editor_Workflow_Default;

class JobSorterService
{
    public static function create(): self
    {
        return new self();
    }

    /**
     * Sort jobs by workflow position in the workflow chain
     */
    public function sortJobsByWorkflowPosition(array $jobs, editor_Workflow_Default $workflow): array
    {
        $chain = $workflow->getStepChain();

        // Create a map of step names to their positions in the chain
        $stepOrder = [];
        foreach ($chain as $index => $step) {
            $stepOrder[$step] = $index;
        }

        // Sort jobs based on their workflow step position in the chain
        usort($jobs, function ($jobA, $jobB) use ($stepOrder) {
            $stepA = $jobA['workflowStepName'] ?? '';
            $stepB = $jobB['workflowStepName'] ?? '';

            // If both steps exist in the order map, compare their positions
            if (isset($stepOrder[$stepA]) && isset($stepOrder[$stepB])) {
                return $stepOrder[$stepA] - $stepOrder[$stepB];
            }

            // If only one exists, prioritize the one in the map
            if (isset($stepOrder[$stepA])) {
                return -1;
            }

            if (isset($stepOrder[$stepB])) {
                return 1;
            }

            // If neither exists in the map, maintain original order
            return 0;
        });

        return $jobs;
    }
}
