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
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\Contract\FetchesStatusInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\ReorganizeInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\Exception\ReorganizeException;
use MittagQI\Translate5\T5Memory\Reorganize\ManualReorganizeMemoryWorker;
use MittagQI\Translate5\T5Memory\Reorganize\ManualReorganizeService;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Logger;

class ReorganizeService
{
    // Need to move this region to a dedicated class while refactoring connector
    private const REORGANIZE_ATTEMPTS = 'reorganize_attempts';

    private const REORGANIZE_WAIT_TIME_SECONDS = 60;

    public function __construct(
        private readonly Zend_Config $config,
        private readonly ZfExtended_Logger $logger,
        private readonly PersistenceService $persistenceService,
        private readonly ReorganizeInterface & FetchesStatusInterface $t5MemoryApi,
        private readonly ManualReorganizeService $manualReorganizeService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('config'),
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.reorganize'),
            PersistenceService::create(),
            T5MemoryApi::create(),
            ManualReorganizeService::create(),
        );
    }

    public function needsReorganizing(
        ResponseInterface $response,
        LanguageResource $languageResource,
        string $tmName,
    ): bool {
        if (str_contains($response->getErrorMessage() ?: '', 'Failed to load tm')) {
            return false;
        }

        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->reorganizeErrorCodes
        );

        $errorSupposesReorganizing = in_array($response->getCode(), $errorCodes);
        // Check if error codes contains any of the values
        $needsReorganizing = $errorSupposesReorganizing &&
            ! $this->isReorganizingAtTheMoment($languageResource, $tmName);

        if ($needsReorganizing && $this->isMaxReorganizeAttemptsReached($languageResource, $tmName)) {
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
            $this->addReorganizeWarning($response, $languageResource, $tmName);
        }

        return $needsReorganizing;
    }

    /**
     * @throws ReorganizeException
     */
    public function startReorganize(
        LanguageResource $languageResource,
        string $tmName,
        ReorganizeOptions $reorganizeOptions,
        bool $isInternalFuzzy = false,
    ): void {
        if (! $isInternalFuzzy) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $languageResource->refresh();
        }

        $this->increaseReorganizeAttempts($languageResource, $tmName);

        if (! $isInternalFuzzy) {
            $languageResource->save();
        }

        $this->logger->info(
            'E1314',
            'Starting TM reorganization',
            [
                'languageResource' => $languageResource,
                'tmName' => $tmName,
                'attempt' => $this->getCurrentAttemptsCount($languageResource, $tmName),
            ]
        );

        if ($this->config->runtimeOptions->LanguageResources->t5memory->reorganizeManually) {
            $languageResource->setStatus(LanguageResourceStatus::REORGANIZE_IN_PROGRESS);

            if (! $isInternalFuzzy) {
                $languageResource->save();
            }

            ManualReorganizeMemoryWorker::queueWorker(
                $languageResource,
                $tmName,
                $isInternalFuzzy
            );

            return;
        }

        try {
            $this->t5MemoryApi->reorganizeTm(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
                $reorganizeOptions,
            );
        } catch (\Throwable $e) {
            $this->logger->exception($e);

            throw new ReorganizeException($e->getMessage(), $e->getCode(), previous: $e);
        }
    }

    /**
     * @throws ReorganizeException
     */
    public function reorganizeTm(
        LanguageResource $languageResource,
        string $tmName,
        ReorganizeOptions $reorganizeOptions,
        bool $isInternalFuzzy = false,
    ): void {
        if ($this->config->runtimeOptions->LanguageResources->t5memory->reorganizeManually) {
            $this->manualReorganizeService->reorganizeTm(
                $languageResource,
                $tmName,
                $reorganizeOptions,
                $isInternalFuzzy
            );

            return;
        }

        $this->startReorganize(
            $languageResource,
            $tmName,
            $reorganizeOptions,
            $isInternalFuzzy
        );

        $reorganized = $this->waitReorganizeFinished($languageResource, $tmName);

        if (! $isInternalFuzzy) {
            $languageResource->setStatus(
                $reorganized ? LanguageResourceStatus::AVAILABLE : LanguageResourceStatus::REORGANIZE_FAILED
            );

            $languageResource->save();
        }
    }

    public function isReorganizingAtTheMoment(LanguageResource $languageResource, string $tmName): bool
    {
        if ($languageResource->getStatus() === LanguageResourceStatus::REORGANIZE_IN_PROGRESS) {
            return $this->getCurrentAttemptsCount($languageResource, $tmName) > 0;
        }

        $status = $this->t5MemoryApi->getStatus(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName)
        );

        return $status->status === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
    }

    public function waitReorganizeFinished(LanguageResource $languageResource, string $tmName): bool
    {
        $elapsedTime = 0;
        $sleepTime = 5;

        while ($elapsedTime < $this->getReorganizeWaitingTimeSeconds()) {
            if (! $this->isReorganizingAtTheMoment($languageResource, $tmName)) {
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

    private function isMaxReorganizeAttemptsReached(?LanguageResource $languageResource, string $tmName): bool
    {
        if (null === $languageResource) {
            return false;
        }

        $currentAttempts = $this->getCurrentAttemptsCount($languageResource, $tmName);
        $maxAttempts = $this->config->runtimeOptions->LanguageResources->t5memory->maxReorganizeAttempts;

        return $currentAttempts >= $maxAttempts;
    }

    private function getCurrentAttemptsCount(LanguageResource $languageResource, string $tmName): int
    {
        return $languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS, true)[$tmName] ?? 0;
    }

    private function increaseReorganizeAttempts(LanguageResource $languageResource, string $tmName): void
    {
        $attempts = $languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS, true);
        $attempts[$tmName] = ($attempts[$tmName] ?? 0) + 1;

        $languageResource->addSpecificData(self::REORGANIZE_ATTEMPTS, $attempts);
    }

    public function resetReorganizeAttempts(
        LanguageResource $languageResource,
        string $tmName,
        bool $isInternalFuzzy = false,
    ): void {
        if ($isInternalFuzzy) {
            return;
        }

        $attempts = $languageResource->getSpecificData(self::REORGANIZE_ATTEMPTS, true);

        if (! isset($attempts[$tmName])) {
            return;
        }

        unset($attempts[$tmName]);

        // In some cases language resource is detached from DB
        $languageResource->refresh();
        $languageResource->addSpecificData(self::REORGANIZE_ATTEMPTS, $attempts);
        $languageResource->save();
    }

    private function addReorganizeWarning(
        ResponseInterface $response,
        LanguageResource $languageResource,
        string $tmName,
    ): void {
        $params = [
            'languageResource' => $languageResource,
            'apiError' => $response->getBody(),
            'tmName' => $tmName,
        ];

        $this->logger->warn(
            'E1314',
            'The queried TM returned error which is configured for automatic TM reorganization',
            $params
        );
    }
}
