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
use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use Zend_Config;
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
        private readonly T5MemoryApi $t5MemoryApi,
    ) {
    }

    public static function create(): self
    {
        return new self(
            \Zend_Registry::get('config'),
            \Zend_Registry::get('logger')->cloneMe('editor.t5memory.reorganize'),
            PersistenceService::create(),
            T5MemoryApi::create(),
        );
    }

    public function needsReorganizing(
        ResponseInterface $response,
        LanguageResource $languageResource,
        string $tmName,
        Task $task = null,
    ): bool {
        $errorCodes = explode(
            ',',
            $this->config->runtimeOptions->LanguageResources->t5memory->reorganizeErrorCodes
        );

        $errorSupposesReorganizing = in_array($response->getCode(), $errorCodes);
        // Check if error codes contains any of the values
        $needsReorganizing = $errorSupposesReorganizing &&
            ! $this->isReorganizingAtTheMoment($languageResource, $tmName);

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
            $this->addReorganizeWarning($response, $languageResource, $task);
        }

        return $needsReorganizing;
    }

    public function reorganizeTm(
        LanguageResource $languageResource,
        string $tmName,
        ReorganizeOptions $reorganizeOptions,
        bool $isInternalFuzzy = false,
    ): bool {
        if (! $isInternalFuzzy) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $languageResource->refresh();
            $this->increaseReorganizeAttempts($languageResource);
            $languageResource->save();
        }

        try {
            $this->t5MemoryApi->reorganizeTm(
                $languageResource->getResource()->getUrl(),
                $this->persistenceService->addTmPrefix($tmName),
                $reorganizeOptions,
            );
        } catch (\Throwable $e) {
            $this->logger->exception($e);

            return false;
        }

        $reorganized = $this->waitReorganizeFinished($languageResource, $tmName);

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
    ): bool {
        $status = $this->t5MemoryApi->getStatus(
            $languageResource->getResource()->getUrl(),
            $this->persistenceService->addTmPrefix($tmName)
        );

        return $status->status === LanguageResourceStatus::REORGANIZE_IN_PROGRESS;
    }

    public function waitReorganizeFinished(
        LanguageResource $languageResource,
        ?string $tmName,
    ): bool {
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

    private function addReorganizeWarning(
        ResponseInterface $response,
        LanguageResource $languageResource,
        Task $task = null,
    ): void {
        $params = [
            'languageResource' => $languageResource,
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
}
