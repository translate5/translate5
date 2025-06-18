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
use Http\Client\Exception\HttpException;
use MittagQI\Translate5\T5Memory\Api\Contract\CreateMemoryResponseInterface;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use MittagQI\Translate5\T5Memory\Exception\UnableToCreateMemoryException;

class CreateMemoryService
{
    public function __construct(
        private readonly VersionService $versionService,
        private readonly PersistenceService $persistenceService,
        private readonly Api\VersionedApiFactory $versionedApiFactory,
        private readonly MemoryNameGenerator $memoryNameGenerator,
        private readonly RetryService $waitingService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            VersionService::create(),
            PersistenceService::create(),
            Api\VersionedApiFactory::create(),
            new MemoryNameGenerator(),
            RetryService::create()
        );
    }

    public function createMemory(
        LanguageResource $languageResource,
        string $tmName,
        string $fileName,
        StripFramingTags $stripFramingTags,
    ): string {
        $response = $this->createTm($languageResource, $tmName, $fileName, $stripFramingTags);

        if ($response->successful()) {
            return $response->getTmName();
        }

        throw new UnableToCreateMemoryException(
            'Unable to create memory: ' . $response->getErrorMessage()
        );
    }

    /**
     * @throws UnableToCreateMemoryException
     */
    private function createTm(
        LanguageResource $languageResource,
        string $tmName,
        string $fileName,
        StripFramingTags $stripFramingTags,
    ): CreateMemoryResponseInterface {
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        if (Api\V6\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->createTm(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    $languageResource->getSourceLangCode(),
                    $fileName,
                    $stripFramingTags,
                );
        }

        if (Api\V5\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V5\VersionedApi::class)
                ->createTm(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    $languageResource->getSourceLangCode(),
                    file_get_contents($fileName),
                );
        }

        throw new \LogicException('Unsupported T5Memory version: ' . $version);
    }

    /**
     * @throws UnableToCreateMemoryException
     */
    public function createEmptyMemoryWithRetry(
        LanguageResource $languageResource,
        string $name,
    ): string {
        $createEmptyMemory = function () use ($languageResource, &$name) {
            try {
                $t = $this->createEmptyMemory($languageResource, $name);

                return [
                    WaitCallState::Done,
                    $t,
                ];
            } catch (HttpException $e) {
                $body = $e->getResponse()->getBody()->getContents();

                if (strpos($body, 'ERROR_MEM_NAME_EXISTS')) {
                    $name = $this->memoryNameGenerator->generateNextMemoryName($languageResource, $name, true);
                }

                return [
                    WaitCallState::Retry,
                    null,
                ];
            }
        };

        $memoryName = $this->waitingService->callAwaiting($createEmptyMemory);

        if (null === $memoryName) {
            throw new UnableToCreateMemoryException('Retry failed to create memory');
        }

        return $memoryName;
    }

    /**
     * @throws HttpException
     * @throws UnableToCreateMemoryException
     */
    public function createEmptyMemory(
        LanguageResource $languageResource,
        string $tmName,
    ): string {
        $response = $this->createEmptyTm($languageResource, $tmName);

        if ($response->successful()) {
            return $response->getTmName();
        }

        throw new UnableToCreateMemoryException(
            'Unable to create memory: ' . $response->getErrorMessage()
        );
    }

    /**
     * @throws HttpException
     */
    private function createEmptyTm(
        LanguageResource $languageResource,
        string $tmName,
    ): CreateMemoryResponseInterface {
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        if (Api\V6\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->createEmptyTm(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    $languageResource->getSourceLangCode(),
                );
        }

        if (Api\V5\VersionedApi::isVersionSupported($version)) {
            return $this->versionedApiFactory
                ->get(Api\V5\VersionedApi::class)
                ->createEmptyTm(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                    $languageResource->getSourceLangCode(),
                );
        }

        throw new \LogicException('Unsupported T5Memory version: ' . $version);
    }
}
