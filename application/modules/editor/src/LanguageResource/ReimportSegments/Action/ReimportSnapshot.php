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
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagService;
use MittagQI\Translate5\ContentProtection\T5memory\ConvertT5MemoryTagServiceInterface;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\Integration\UpdateSegmentService;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsLoggerProvider;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsResult;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\JsonlReimportSegmentsRepository;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\SegmentRepository;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;
use MittagQI\Translate5\T5Memory\FlushMemoryService;
use Throwable;

class ReimportSnapshot
{
    private const MAX_TRIES = 10;

    public function __construct(
        private readonly ReimportSegmentRepositoryInterface $reimportSegmentRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly ReimportSegmentsLoggerProvider $loggerProvider,
        private readonly SegmentRepository $segmentRepository,
        private readonly ConvertT5MemoryTagServiceInterface $tmConversionService,
        private readonly FlushMemoryService $flushMemoryService,
        private readonly UpdateSegmentService $updateSegmentService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new JsonlReimportSegmentsRepository(),
            new LanguageResourceRepository(),
            new ReimportSegmentsLoggerProvider(),
            SegmentRepository::create(),
            ConvertT5MemoryTagService::create(),
            FlushMemoryService::create(),
            \Zend_Registry::get('integration.segment.update'),
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
     * @param iterable<ReimportSegmentDTO> $reimportDTOS
     */
    private function updateSegments(
        LanguageResource $languageResource,
        Task $task,
        iterable $reimportDTOS,
        array $updateOnlyIds = [],
    ): ReimportSegmentsResult {
        $options = UpdateOptions::fromArray(
            [
                UpdateOptions::SAVE_TO_DISK => false,
            ]
        );

        $emptySegmentsAmount = 0;
        $successfulSegmentsAmount = 0;
        $failedSegmentsIds = [];

        foreach ($reimportDTOS as $reimportDTO) {
            if (! empty($updateOnlyIds) && ! in_array($reimportDTO->segmentId, $updateOnlyIds, true)) {
                continue;
            }

            $segment = $this->segmentRepository->get($reimportDTO->segmentId);

            if ($reimportDTO->source === '' || $reimportDTO->target === '') {
                $emptySegmentsAmount++;

                continue;
            }

            try {
                [$source, $target] = $this->tmConversionService->convertPair(
                    $reimportDTO->source,
                    $reimportDTO->target,
                    $task->getSourceLang(),
                    $task->getTargetLang(),
                );

                $updateDTO = new UpdateSegmentDTO(
                    $source,
                    $target,
                    $reimportDTO->fileName,
                    $reimportDTO->timestamp,
                    $reimportDTO->userName,
                    $reimportDTO->context,
                );

                $this->updateSegmentService->updateWithDTO(
                    $languageResource,
                    $segment,
                    $updateDTO,
                    $task->getConfig(),
                    $options,
                );
            } catch (Throwable) {
                $failedSegmentsIds[] = $reimportDTO->segmentId;

                continue;
            }

            $successfulSegmentsAmount++;
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
}
