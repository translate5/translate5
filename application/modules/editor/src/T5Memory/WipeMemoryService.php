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

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;

class WipeMemoryService
{
    public function __construct(
        private readonly PersistenceService $persistenceService,
        private readonly CreateMemoryService $createMemoryService,
        private readonly T5MemoryApi $t5MemoryApi,
        private readonly MemoryNameGenerator $memoryNameGenerator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            PersistenceService::create(),
            CreateMemoryService::create(),
            T5MemoryApi::create(),
            new MemoryNameGenerator(),
        );
    }

    /**
     * @throws Exception\UnableToCreateMemoryException
     */
    public function wipeMemory(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): string {
        $newName = $this->memoryNameGenerator->generateNextMemoryName($languageResource);
        $newName = $this->createMemoryService->createEmptyMemoryWithRetry($languageResource, $newName);

        $this->persistenceService->removeMemoryFromLanguageResource($languageResource, $tmName, $isInternalFuzzy);
        $this->persistenceService->addMemoryToLanguageResource($languageResource, $newName, $isInternalFuzzy);

        $this->t5MemoryApi->deleteTm(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName),
        );

        return $newName;
    }
}
