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
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;

class StatusService
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

    public function getStatus(
        LanguageResource $languageResource,
        ?string $tmName = null,
    ): string {
        if ($languageResource->isConversionStarted()) {
            return LanguageResourceStatus::CONVERTING;
        }

        if ($languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS) {
            return LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
        }

        if ($languageResource->getStatus() === LanguageResourceStatus::IMPORT) {
            return LanguageResourceStatus::IMPORT;
        }

        if ($tmName) {
            $status = $this->t5MemoryApi->getStatus(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
            );

            return $status->successful() ? $status->status : LanguageResourceStatus::ERROR;
        }

        $memories = $languageResource->getSpecificData('memories', parseAsArray: true);
        foreach ($memories as ['filename' => $name]) {
            $status = $this->t5MemoryApi->getStatus(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($name)
            );

            if (! $status->successful()) {
                return LanguageResourceStatus::ERROR;
            }

            if ($status->status !== LanguageResourceStatus::AVAILABLE) {
                return $status->status;
            }
        }

        return LanguageResourceStatus::AVAILABLE;
    }
}
