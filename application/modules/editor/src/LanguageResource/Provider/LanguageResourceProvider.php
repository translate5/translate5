<?php

namespace MittagQI\Translate5\LanguageResource\Provider;

use editor_Services_Manager;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use ZfExtended_Models_Filter;

class LanguageResourceProvider
{
    public function __construct(
        private readonly editor_Services_Manager $serviceManager,
        private readonly TmConversionService $tmConversionService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Services_Manager(),
            TmConversionService::create(),
        );
    }

    /**
     * Get all available tms for the language combination as in the provided task.
     * (Uses loadByAssociatedTaskAndLanguage() which is meant to be called only by rest call!)
     * @throws \ZfExtended_Exception
     * @throws \ReflectionException
     */
    public function getAssocTasksWithResources(string $taskGuid, ?ZfExtended_Models_Filter $filter = null): array
    {
        $taskAssoc = new TaskAssociation();

        if (null !== $filter) {
            $taskAssoc->filterAndSort($filter);
        }

        /**
         * TODO: Extract method @see TaskAssociation::loadByAssociatedTaskAndLanguage() into a separate class
         */
        $result = $taskAssoc->loadByAssociatedTaskAndLanguage($taskGuid);

        $available = [];

        foreach ($result as $languageResource) {
            $resource = $this->serviceManager->getResourceById(
                $languageResource['serviceType'],
                $languageResource['resourceId']
            );

            if (! empty($resource)) {
                $languageResource = array_merge($languageResource, $resource->getMetaData());
                $languageResource['serviceName'] = $this->serviceManager->getNameByType($languageResource['serviceType']);
                $languageResource['isTaskTm'] = ($languageResource['isTaskTm'] ?? 0) === '1';

                if (editor_Services_Manager::SERVICE_OPENTM2 === $languageResource['serviceType']) {
                    $lrId = (int) $languageResource['languageResourceId'];
                    $languageResource['tmConversionState'] = $this->tmConversionService->getConversionState($lrId)->value;
                }

                $available[] = $languageResource;
            }
        }

        return $available;
    }
}
