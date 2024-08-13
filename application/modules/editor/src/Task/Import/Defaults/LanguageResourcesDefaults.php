<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_LanguageResources_CustomerAssoc;
use editor_Models_LanguageResources_Languages;
use editor_Models_Languages;
use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use Zend_Cache_Exception;
use ZfExtended_Factory;

class LanguageResourcesDefaults implements ITaskDefaults
{
    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);

        $data = $customerAssoc->loadByCustomerIdsUseAsDefault([$task->getCustomerId()]);

        if (empty($data)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        $this->applyAssocData(
            $this->findMatchingAssocData(
                (int) $task->getSourceLang(),
                (int) $task->getTargetLang(),
                $data
            ),
            function ($assocRow) use ($taskGuid) {
                $taskAssoc = ZfExtended_Factory::get(TaskAssociation::class);
                $taskAssoc->setLanguageResourceId($assocRow['languageResourceId']);
                $taskAssoc->setTaskGuid($taskGuid);
                if (! empty($assocRow['writeAsDefault'])) {
                    $taskAssoc->setSegmentsUpdateable(true);
                }
                $taskAssoc->save();
            }
        );
        $task->updateIsTerminologieFlag($task->getTaskGuid());
    }

    /**
     * Find matching language resources by task languages and call the callback for saving
     * @throws Zend_Cache_Exception
     */
    public function findMatchingAssocData(
        int $sourceLang,
        int $targetLang,
        array $defaultData,
    ): iterable {
        if (0 === $sourceLang || 0 === $targetLang) {
            yield from [];

            return;
        }

        $languages = ZfExtended_Factory::get(editor_Models_LanguageResources_Languages::class);
        $language = ZfExtended_Factory::get(editor_Models_Languages::class);

        $sourceLanguages = $language->getFuzzyLanguages($sourceLang, 'id', true);
        $targetLanguages = $language->getFuzzyLanguages($targetLang, 'id', true);

        foreach ($defaultData as $data) {
            $languageResourceId = $data['languageResourceId'];
            $sourceLangMatch = $languages->isInCollection($sourceLanguages, 'sourceLang', $languageResourceId);
            $targetLangMatch = $languages->isInCollection($targetLanguages, 'targetLang', $languageResourceId);

            if ($sourceLangMatch && $targetLangMatch) {
                yield $data;
            }
        }
    }

    protected function applyAssocData(iterable $dataIterator, callable $saveCallback): void
    {
        foreach ($dataIterator as $data) {
            $saveCallback($data);
        }
    }
}
