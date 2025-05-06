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
use MittagQI\Translate5\T5Memory\Api\ConstantApi;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;

class FlushMemoryService
{
    public function __construct(
        private readonly VersionService $versionService,
        private readonly ConstantApi $constantApi,
        private readonly PersistenceService $persistenceService,
        private readonly Api\VersionedApiFactory $versionedApiFactory,
    ) {
    }

    public static function create(): self
    {
        return new self(
            VersionService::create(),
            ConstantApi::create(),
            PersistenceService::create(),
            Api\VersionedApiFactory::create()
        );
    }

    public function flushCurrentWritable(LanguageResource $languageResource)
    {
        $tmName = $this->persistenceService->getWritableMemory($languageResource);

        $this->flush($languageResource, $tmName);
    }

    /**
     * @throws \editor_Services_Exceptions_NoService
     */
    public function flush(
        LanguageResource $languageResource,
        string $tmName,
    ): ResponseInterface {
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        if (Api\V6\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->flush(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                );
        }

        if (Api\V5\VersionedApi::isVersionSupported($version)) {
            return $this->constantApi->saveTms(
                $languageResource->getResource()->getUrl(),
            );
        }

        throw new \LogicException('Unsupported T5Memory version: ' . $version);
    }
}
