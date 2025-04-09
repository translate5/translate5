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

namespace MittagQI\Translate5\LanguageResource\ReimportSegments;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Segment as Segment;
use editor_Models_Task as Task;
use editor_Services_Connector;
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Adapter\Exception\SegmentUpdateException;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\JsonlReimportSegmentsRepository;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\Repository\LanguageResourceRepository;

class ReimportSegments
{
    public function __construct(
        private readonly ReimportSegmentRepositoryInterface $reimportSegmentRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly editor_Services_Manager $serviceManager,
        private readonly ReimportSegmentsLoggerProvider $loggerProvider,
        private readonly Segment $segment,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new JsonlReimportSegmentsRepository(),
            new LanguageResourceRepository(),
            new editor_Services_Manager(),
            new ReimportSegmentsLoggerProvider(),
            new Segment(),
        );
    }

    public function reimport(Task $task, string $runId, int $languageResourceId): void
    {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);
        $segments = $this->reimportSegmentRepository->getByTask($runId, $task->getTaskGuid());
        $result = $this->updateSegments($languageResource, $task, $segments);
        $this->processResponse($result, $task, $languageResource);
        $this->reimportSegmentRepository->cleanByTask($runId, $task->getTaskGuid());
    }

    private function updateSegments(
        LanguageResource $languageResource,
        Task $task,
        iterable $updateDTOs,
    ): ?ReimportSegmentsResult {
        $connector = $this->getConnector($languageResource, $task);

        $emptySegmentsAmount = 0;
        $successfulSegmentsAmount = 0;
        $failedSegmentsIds = [];
        $lastSegment = null;

        $options = [
            UpdatableAdapterInterface::SAVE_TO_DISK => false,
        ];

        foreach ($updateDTOs as $updateDTO) {
            $this->segment->load($updateDTO->segmentId);

            if ($updateDTO->source === '' || $updateDTO->target === '') {
                $emptySegmentsAmount++;

                continue;
            }

            try {
                $connector->updateWithDTO($updateDTO, $options, $this->segment);
            } catch (SegmentUpdateException) {
                $failedSegmentsIds[] = (int) $updateDTO->segmentId;

                continue;
            }

            $successfulSegmentsAmount++;

            if (null === $lastSegment) {
                // Check the first segment if update was successful
                $connector->checkUpdatedSegment($this->segment);
            }

            $lastSegment = $this->segment;
        }

        if ($lastSegment) {
            // And check the last segment if update was successful
            // We consider that if first and last was successfully updated -
            // high probability all in between were successful too
            $connector->checkUpdatedSegment($lastSegment);
        }

        if (0 === $emptySegmentsAmount && 0 === $successfulSegmentsAmount && 0 === count($failedSegmentsIds)) {
            return null;
        }

        /** @phpstan-ignore-next-line */
        $connector->flush();

        return new ReimportSegmentsResult($emptySegmentsAmount, $successfulSegmentsAmount, $failedSegmentsIds);
    }

    private function processResponse(
        ?ReimportSegmentsResult $result,
        Task $task,
        LanguageResource $languageResource,
    ): void {
        $message = 'No segments for reimport';
        $params = [
            'taskId' => $task->getId(),
            'tmId' => $languageResource->getId(),
        ];

        if ($result !== null) {
            $message = 'Task {taskId} re-imported successfully into the desired TM {tmId}';
            $params = array_merge($params, [
                'emptySegments' => $result->emptySegmentsAmount,
                'successfulSegments' => $result->successfulSegmentsAmount,
                'failedSegments' => $result->failedSegmentIds,
            ]);
        }

        $this->loggerProvider->getLogger()->info('E0000', $message, $params);
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
