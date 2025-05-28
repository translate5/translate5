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

use DateTimeImmutable;
use DateTimeInterface;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\ConstantApi;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;

class ReorganizeService
{
    // Need to move this region to a dedicated class while refactoring connector
    private const REORGANIZE_ATTEMPTS = 'reorganize_attempts';

    private const REORGANIZE_STARTED_AT = 'reorganize_started_at';

    // Applicable only for version < 0.5.x
    private const MAX_REORGANIZE_TIME_MINUTES = 30;

    private const REORGANIZE_WAIT_TIME_SECONDS = 60;

    private const VERSION_0_5 = '0.5';

    public function __construct(
        private readonly \Zend_Config $config,
        private readonly \ZfExtended_Logger $logger,
        private readonly VersionService $versionService,
        private readonly PersistenceService $persistenceService,
        private readonly Api\VersionedApiFactory $versionedApiFactory,
        private readonly ConstantApi $constantApi,
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('config'),
            \Zend_Registry::get('logger')->cloneMe('editor.t5memory.reorganize'),
            VersionService::create(),
            PersistenceService::create(),
            Api\VersionedApiFactory::create(),
            ConstantApi::create(),
        );
    }

    public function needsReorganizing(
        ResponseInterface $response,
        LanguageResource $languageResource,
        string $tmName,
        Task $task = null,
        bool $isInternalFuzzy = false,
    ): bool {
        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->reorganizeErrorCodes
        );

        $errorSupposesReorganizing = in_array($response->getCode(), $errorCodes);
        // Check if error codes contains any of the values
        $needsReorganizing = $errorSupposesReorganizing &&
            ! $this->isReorganizingAtTheMoment($languageResource, $tmName, $isInternalFuzzy);

        if ($needsReorganizing && $this->isMaxReorganizeAttemptsReached($languageResource)) {
            $this->logger->warn(
                'E1314',
                'The queried TM returned error which is configured for automatic TM reorganization. ' .
                'But maximum amount of attempts to reorganize it reached.',
                [
                    'apiError' => $response->getBody(),
                ]
            );
            $needsReorganizing = false;
        }

        if ($needsReorganizing) {
            $this->addReorganizeWarning($response, $task);
        }

        return $needsReorganizing;
    }

    public function reorganizeTm(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): bool {
        if (! $isInternalFuzzy) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $languageResource->refresh();
            $this->increaseReorganizeAttempts($languageResource);
            $this->setReorganizeStatusInProgress($languageResource);
            $languageResource->save();
        }

        $reorganized = true;

        try {
            $this->reorganize($languageResource, $tmName);
        } catch (\Throwable $e) {
            $this->logger->exception($e);

            $reorganized = false;
        }

        if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $languageResource)) {
            $reorganized = $this->waitReorganizeFinished($languageResource, $tmName, $isInternalFuzzy);
        }

        if (! $isInternalFuzzy) {
            $languageResource->setStatus(
                $reorganized ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::REORGANIZE_FAILED
            );

            $languageResource->save();
        }

        return $reorganized;
    }

    public function isReorganizingAtTheMoment(
        LanguageResource $languageResource,
        ?string $tmName = null,
        bool $isInternalFuzzy = false,
    ): bool {
        if (! $isInternalFuzzy) {
            // TODO remove this when t5memory 0.4.x is not supported anymore
            $this->resetReorganizingIfNeeded($languageResource);
        }

        if ($this->versionService->isLRVersionGreaterThan(self::VERSION_0_5, $languageResource)) {
            $status = $this->constantApi->getStatus(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName)
            );

            return $status->status === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
        }

        return $languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
    }

    public function waitReorganizeFinished(
        LanguageResource $languageResource,
        ?string $tmName,
        bool $isInternalFuzzy = false,
    ): bool {
        $elapsedTime = 0;
        $sleepTime = 5;

        while ($elapsedTime < $this->getReorganizeWaitingTimeSeconds()) {
            if (! $this->isReorganizingAtTheMoment($languageResource, $tmName, $isInternalFuzzy)) {
                return true;
            }

            sleep($sleepTime);
            $elapsedTime += $sleepTime;
        }

        return false;
    }

    private function getReorganizeWaitingTimeSeconds(): int
    {
        return $this->canWaitLongTaskFinish()
            ? self::REORGANIZE_WAIT_TIME_SECONDS * 60
            : self::REORGANIZE_WAIT_TIME_SECONDS;
    }

    /**
     * Shows if service can wait for a long-running task (e.g. reorganize, import, export) to finish before do
     * fuzzy requests or other operations.
     */
    private function canWaitLongTaskFinish(): bool
    {
        // This should be moved to config, but this requires refactoring of the connectors
        // and introducing a connector factory instead of manager as it is implemented at the moment
        return defined('ZFEXTENDED_IS_WORKER_THREAD');
    }

    private function isMaxReorganizeAttemptsReached(?LanguageResource $languageResource): bool
    {
        if (null === $languageResource) {
            return false;
        }

        $currentAttempts = $languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) ?? 0;
        $maxAttempts = $this->config->runtimeOptions->LanguageResources->t5memory->maxReorganizeAttempts;

        return $currentAttempts >= $maxAttempts;
    }

    private function increaseReorganizeAttempts(LanguageResource $languageResource): void
    {
        $languageResource->addSpecificData(
            self::REORGANIZE_ATTEMPTS,
            ($languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) ?? 0) + 1
        );
    }

    public function resetReorganizeAttempts(LanguageResource $languageResource, bool $isInternalFuzzy = false): void
    {
        if ($isInternalFuzzy) {
            return;
        }

        if ($languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS) === null) {
            return;
        }

        // In some cases language resource is detached from DB
        $languageResource->refresh();
        $languageResource->removeSpecificData(self::REORGANIZE_ATTEMPTS);
        $languageResource->save();
    }

    /**
     * Applicable only for t5memory 0.4.x
     */
    private function resetReorganizingIfNeeded(LanguageResource $languageResource): void
    {
        $reorganizeStartedAt = $languageResource->getSpecificData(self::REORGANIZE_STARTED_AT);

        if (null === $reorganizeStartedAt) {
            return;
        }

        $maxTimeOfReorganizing = (new DateTimeImmutable($reorganizeStartedAt))->modify(
            sprintf('+%d minutes', self::MAX_REORGANIZE_TIME_MINUTES)
        );

        if ($maxTimeOfReorganizing < new DateTimeImmutable()) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $languageResource->refresh();
            $languageResource->removeSpecificData(self::REORGANIZE_STARTED_AT);
            $languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
            $languageResource->save();
        }
    }

    /**
     * Applicable only for t5memory 0.4.x
     */
    private function setReorganizeStatusInProgress(LanguageResource $languageResource): void
    {
        $languageResource->setStatus(LanguageResourceStatus::REORGANIZE_IN_PROGRESS);
        $languageResource->addSpecificData(self::REORGANIZE_STARTED_AT, date(DateTimeInterface::RFC3339));
    }

    private function addReorganizeWarning(
        ResponseInterface $response,
        Task $task = null,
    ): void {
        $params = [
            'apiError' => $response->getBody(),
        ];

        if (null !== $task) {
            $params['task'] = $task;
        }

        $this->logger->warn(
            'E1314',
            'The queried TM returned error which is configured for automatic TM reorganization',
            $params
        );
    }

    /**
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    private function reorganize(LanguageResource $languageResource, string $tmName): void
    {
        $version = $this->versionService->getT5MemoryVersion($languageResource);

        if (Api\V6\VersionedApi::isVersionSupported($version)) {
            $this->versionedApiFactory
                ->get(Api\V6\VersionedApi::class)
                ->reorganizeTm(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName),
                );

            return;
        }

        if (Api\V5\VersionedApi::isVersionSupported($version)) {
            $this->versionedApiFactory
                ->get(Api\V5\VersionedApi::class)
                ->reorganizeTm(
                    $languageResource->getResource()->getUrl(),
                    $this->persistenceService->addTmPrefix($tmName)
                );

            return;
        }

        throw new \LogicException('Unsupported T5Memory version: ' . $version);
    }
}
