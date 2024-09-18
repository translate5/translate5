<?php

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Task_AbstractWorker;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use ZfExtended_Factory;

/***
 * Remove all batch results from associated resources for given task.
 * This worker will runn after match analysis and pivot pre-translation workers are finished
 */
class BatchCleanupWorker extends editor_Models_Task_AbstractWorker
{
    protected function validateParameters(array $parameters): bool
    {
        $neededEntries = ['taskGuid'];
        $foundEntries = array_keys($parameters);
        $keyDiff = array_diff($neededEntries, $foundEntries);

        //if there is not keyDiff all needed were found
        return empty($keyDiff);
    }

    protected function work(): bool
    {
        $resources = [];

        $taskAssociation = ZfExtended_Factory::get(TaskAssociation::class);
        $result = $taskAssociation->loadByTaskGuids($this->taskGuid);

        if (! empty($result)) {
            $resources = array_column($result, 'languageResourceId');
        }

        $taskPivotAssociation = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $result = $taskPivotAssociation->loadTaskAssociated($this->taskGuid);

        if (! empty($result)) {
            $resources = array_merge($resources, array_column($result, 'languageResourceId'));
        }

        $resources = array_unique($resources);

        $batchResult = ZfExtended_Factory::get(BatchResult::class);
        $batchResult->deleteForLanguageresource(
            $resources,
            $this->taskGuid
        );

        return true;
    }
}
