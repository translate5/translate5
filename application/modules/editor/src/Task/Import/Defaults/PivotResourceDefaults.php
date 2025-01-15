<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_LanguageResources_CustomerAssoc;
use editor_Models_Task as Task;
use editor_Models_TaskConfig;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use ZfExtended_Factory;

class PivotResourceDefaults extends LanguageResourcesDefaults
{
    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        $customerAssocData = $customerAssoc->loadByCustomerIdsPivotAsDefault([$task->getCustomerId()]);

        if (empty($customerAssocData)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        $data = $this->findMatchingAssocData(
            (int) $task->getSourceLang(),
            (int) $task->getRelaisLang(),
            $customerAssocData
        );

        foreach ($data as $assocRow) {
            $pivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
            // @phpstan-ignore-next-line
            $pivotAssoc->setLanguageResourceId($assocRow['languageResourceId']);
            // @phpstan-ignore-next-line
            $pivotAssoc->setTaskGuid($taskGuid);
            $pivotAssoc->save();
        }

        if ($importWizardUsed) {
            $this->handlePivotAutostart($task);
        }
    }

    /**
     * Disable the pivot autostart in case the import is done via the translate5 UI. For all api imports, the config
     * runtimeOptions.import.autoStartPivotTranslations will decide if the pivot pre-translation is auto-queued
     */
    private function handlePivotAutostart(Task $task): void
    {
        $config = ZfExtended_Factory::get(editor_Models_TaskConfig::class);
        $config->updateInsertConfig($task->getTaskGuid(), 'runtimeOptions.import.autoStartPivotTranslations', false);
    }

    public function canApplyDefaults(Task $task): bool
    {
        return true;
    }
}
