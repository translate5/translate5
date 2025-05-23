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

namespace MittagQI\Translate5\LanguageResource\ReimportSegments\Action;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use editor_Services_Connector;
use editor_Services_Manager;
use MittagQI\Translate5\ContentProtection\T5memory\TmConversionService;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsLoggerProvider;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsResult;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\JsonlReimportSegmentsRepository;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\T5Memory\FlushMemoryService;
use Throwable;

class ReimportSnapshot
{
    private const MAX_TRIES = 10;

    public function __construct(
        private readonly ReimportSegmentRepositoryInterface $reimportSegmentRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly editor_Services_Manager $serviceManager,
        private readonly ReimportSegmentsLoggerProvider $loggerProvider,
        private readonly SegmentRepository $segmentRepository,
        private readonly TmConversionService $tmConversionService,
        private readonly FlushMemoryService $flushMemoryService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new JsonlReimportSegmentsRepository(),
            new LanguageResourceRepository(),
            new editor_Services_Manager(),
            new ReimportSegmentsLoggerProvider(),
            SegmentRepository::create(),
            TmConversionService::create(),
            FlushMemoryService::create(),
        );
    }

    public function reimport(
        Task $task,
        string $runId,
        int $languageResourceId,
        array $failedSegmentsIds = []
    ): ReimportSegmentsResult {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);
        $result = $this->reimportWithRetry($task, $languageResource, $runId, $failedSegmentsIds);
        $this->addFinalizationLog($result, $task, $languageResource);

        if (empty($result->failedSegmentIds)) {
            // Keep the segments in the repository for the next run and debug
            $this->reimportSegmentRepository->cleanByTask($runId, $task->getTaskGuid());
        }

        return $result;
    }

    private function reimportWithRetry(
        Task $task,
        LanguageResource $languageResource,
        string $runId,
        array $failedSegmentsIds = [],
    ): ReimportSegmentsResult {
        $totalEmptySegmentsAmount = 0;
        $totalSuccessfulSegmentsAmount = 0;
        $tries = 0;

        while ($tries < self::MAX_TRIES) {
            $segments = $this->reimportSegmentRepository->getByTask($runId, $task->getTaskGuid());
            $result = $this->updateSegments($languageResource, $task, $segments, $failedSegmentsIds);

            $totalEmptySegmentsAmount += $result->emptySegmentsAmount;
            $totalSuccessfulSegmentsAmount += $result->successfulSegmentsAmount;
            $failedSegmentsIds = $result->failedSegmentIds;

            if (empty($failedSegmentsIds)) {
                break;
            }

            $this->addFailedSegmentsLog($failedSegmentsIds, (int) $task->getId(), (int) $languageResource->getId());

            $tries++;
        }

        return new ReimportSegmentsResult(
            $totalEmptySegmentsAmount,
            $totalSuccessfulSegmentsAmount,
            $failedSegmentsIds,
        );
    }

    /**
     * @param iterable<UpdateSegmentDTO> $updateDTOs
     */
    private function updateSegments(
        LanguageResource $languageResource,
        Task $task,
        iterable $updateDTOs,
        array $updateOnlyIds = [],
    ): ReimportSegmentsResult {
        $connector = $this->getConnector($languageResource, $task);
        $options = [
            UpdatableAdapterInterface::SAVE_TO_DISK => false,
        ];

        $emptySegmentsAmount = 0;
        $successfulSegmentsAmount = 0;
        $failedSegmentsIds = [];
        $lastSegment = null;

        foreach ($updateDTOs as $updateDTO) {
            if (! empty($updateOnlyIds) && ! in_array($updateDTO->segmentId, $updateOnlyIds, true)) {
                continue;
            }

            $segment = $this->segmentRepository->get($updateDTO->segmentId);

            if ($updateDTO->source === '' || $updateDTO->target === '') {
                $emptySegmentsAmount++;

                continue;
            }

            try {
                [$source, $target] = $this->tmConversionService->convertPair(
                    $updateDTO->source,
                    $updateDTO->target,
                    $task->getSourceLang(),
                    $task->getTargetLang(),
                );

                $updateDTO = new UpdateSegmentDTO(
                    $updateDTO->taskGuid,
                    $updateDTO->segmentId,
                    $source,
                    $target,
                    $updateDTO->fileName,
                    $updateDTO->timestamp,
                    $updateDTO->userName,
                    $updateDTO->context,
                );

                $connector->updateWithDTO($updateDTO, $options, $segment);
            } catch (Throwable) {
                $failedSegmentsIds[] = $updateDTO->segmentId;

                continue;
            }

            $successfulSegmentsAmount++;

            if (null === $lastSegment) {
                // Check the first segment if update was successful
                $connector->checkUpdatedSegment($segment);
            }

            $lastSegment = $segment;
        }

        if ($lastSegment) {
            // And check the last segment if update was successful
            // We consider that if first and last were successfully updated -
            // high probability all in between were successful too
            $connector->checkUpdatedSegment($lastSegment);
        }

        if (0 !== $successfulSegmentsAmount) {
            $this->flushMemoryService->flushCurrentWritable($languageResource);
        }

        return new ReimportSegmentsResult($emptySegmentsAmount, $successfulSegmentsAmount, $failedSegmentsIds);
    }

    private function addFinalizationLog(
        ?ReimportSegmentsResult $result,
        Task $task,
        LanguageResource $languageResource,
    ): void {
        $params = [
            'taskId' => $task->getId(),
            'tmId' => $languageResource->getId(),
            'task' => $task,
            'languageResource' => $languageResource,
        ];

        if (null === $result) {
            $message = 'No segments for reimport';

            $this->loggerProvider->getLogger()->info('E1713', $message, $params);

            return;
        }

        $params = array_merge($params, [
            'emptySegments' => $result->emptySegmentsAmount,
            'successfulSegments' => $result->successfulSegmentsAmount,
            'failedSegments' => $result->failedSegmentIds,
        ]);

        $message = 'Task {taskId} re-imported into the desired TM {tmId}';

        if (empty($result->failedSegmentIds)) {
            $this->loggerProvider->getLogger()->info('E1713', $message, $params);

            return;
        }

        $message .= '. Please note there are {failedSegmentsAmount} segments that failed to be reimported. '
            . 'This operation is retried in the background. If error stays, please check the log for details.';
        $params['failedSegmentsAmount'] = count($result->failedSegmentIds);
        $this->loggerProvider->getLogger()->error('E1713', $message, $params);
    }

    private function addFailedSegmentsLog(array $failedSegmentsIds, int $taskId, int $languageResourceId): void
    {
        $message = 'Task reimport finished with failed segments, trying to reimport them';
        $params = [
            'taskId' => $taskId,
            'tmId' => $languageResourceId,
            'failedSegments' => $failedSegmentsIds,
        ];

        $this->loggerProvider->getLogger()->warn('E1714', $message, $params);
    }

    private function getConnector(
        LanguageResource $languageResource,
        Task $task
    ): UpdatableAdapterInterface|editor_Services_Connector {
        return $this->serviceManager->getConnector(
            $languageResource,
            config: $task->getConfig(),
            customerId: (int) $task->getCustomerId(),
        );
    }
}
