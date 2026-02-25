<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class EmptyMemoryCheck
{
    public function __construct(
        private readonly PersistenceService $persistenceService,
        private readonly T5MemoryApi $t5MemoryApi,
    ) {
    }

    public static function create(): self
    {
        return new self(
            PersistenceService::create(),
            T5MemoryApi::create(),
        );
    }

    public function hasNotEmptyMemory(LanguageResource $languageResource): bool
    {
        $data = $languageResource->getSpecificData('memories', true);

        if (empty($data) || ! is_array($data)) {
            return false;
        }

        $memories = array_column($data, 'filename');

        foreach ($memories as $memory) {
            if (! $this->isMemoryEmpty($languageResource, $memory)) {
                return true;
            }
        }

        return false;
    }

    public function isMemoryEmpty(LanguageResource $languageResource, string $tmName): bool
    {
        $response = $this->t5MemoryApi->downloadTmx(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName),
            1
        );

        foreach ($response as $stream) {
            $stream->rewind();
            $content = $stream->getContents();

            if (str_contains($content, '<tu ')) {
                return false;
            }
        }

        return true;
    }
}
