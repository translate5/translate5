<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use editor_Models_LanguageResources_Languages as LanguageResources_Languages;
use editor_Models_Languages as Languages;
use editor_Models_Task as Task;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeEvent;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeType;
use MittagQI\Translate5\LanguageResource\Operation\AssociateTaskOperation;
use MittagQI\Translate5\Penalties\DataProvider\TaskPenaltyDataProvider;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use Zend_Cache_Exception;
use ZfExtended_Factory as Factory;

class LanguageResourcesDefaults implements ITaskDefaults
{
    public function __construct(
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly AssociateTaskOperation $associateTaskOperation,
    ) {
    }

    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws \ZfExtended_Exception
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ReflectionException
     */
    public function applyDefaults(Task $task, bool $importWizardUsed = false): void
    {
        $customerAssoc = Factory::get(CustomerAssoc::class);

        $customerAssocData = $customerAssoc->loadByCustomerIdsUseAsDefault([$task->getCustomerId()]);

        if (empty($customerAssocData)) {
            return;
        }

        $taskGuid = $task->getTaskGuid();

        // Get task source and target major languages
        // $language = Factory::get(Languages::class);
        // $taskMajorSourceLangId = $language->findMajorLanguageById($task->getSourceLang());
        // $taskMajorTargetLangId = $language->findMajorLanguageById($task->getTargetLang());

        $taskPenaltyDataProvider = TaskPenaltyDataProvider::create();

        $data = $this->findMatchingAssocData(
            $task->getSourceLang(), // $taskMajorSourceLangId
            $task->getTargetLang(), // $taskMajorTargetLangId
            $customerAssocData
        );

        foreach ($data as $assocRow) {
            $languageResourceId = (int) $assocRow['languageResourceId'];

            $subLangMismatch = $taskPenaltyDataProvider->getPenalties(
                $taskGuid,
                $languageResourceId
            )['sublangMismatch'];

            $segmentsUpdatable = ! empty($assocRow['writeAsDefault']) && $subLangMismatch === false;

            $this->associateTaskOperation->associate($languageResourceId, $taskGuid, $segmentsUpdatable);

            EventDispatcher::create()->dispatch(
                new LanguageResourceTaskAssociationChangeEvent(
                    $this->languageResourceRepository->get((int) $assocRow['languageResourceId']),
                    $taskGuid,
                    LanguageResourceTaskAssociationChangeType::Add,
                )
            );
        }

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
            return yield from [];
        }

        $languages = Factory::get(LanguageResources_Languages::class);
        $language = Factory::get(Languages::class);

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

    public function canApplyDefaults(Task $task): bool
    {
        return true;
    }
}
