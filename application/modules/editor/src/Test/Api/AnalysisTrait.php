<?php

namespace MittagQI\Translate5\Test\Api;

trait AnalysisTrait
{

    /**
     * Filter out not needed data from the analysis results
     *
     * @param array $analysisData
     * @return array
     */
    private function filterUngroupedAnalysis(array $analysisData): array
    {
        // remove some unneeded columns
        foreach ($analysisData as $a) {
            unset(
                $a->id,
                $a->taskGuid,
                $a->analysisId,
                $a->segmentId,
                $a->languageResourceid,
                $a->errorCount
            );
        }

        return $analysisData;
    }

    /**
     * Filter out not needed data from the task analysis results and sort the returned data by resourceName
     * @param array $data
     * @return array
     */
    private function filterTaskAnalysis(array &$data): array
    {
        // remove the created timestamp since is not relevant for the test
        foreach ($data as $a) {
            unset($a->created,$a->id,$a->taskGuid,$a->segmentId,$a->errorCount);
        }
        usort($data, function ($a, $b) {
            return strcmp($a->resourceName, $b->resourceName);
        });

        return $data;
    }
}