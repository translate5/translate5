<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;

class PersistenceService
{
    public function getWritableMemory(LanguageResource $languageResource): string
    {
        foreach ($languageResource->getSpecificData('memories', parseAsArray: true) as $memory) {
            if (! $memory['readonly']) {
                return $memory['filename'];
            }
        }

        throw new \editor_Services_Connector_Exception('E1564', [
            'name' => $languageResource->getName(),
        ]);
    }
}