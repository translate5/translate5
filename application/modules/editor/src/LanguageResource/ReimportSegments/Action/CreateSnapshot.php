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

use editor_Models_Segment as Segment;
use editor_Models_Task as Task;
use MittagQI\Translate5\Integration\SegmentUpdateDtoFactory;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentDTO;
use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsOptions;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\JsonlReimportSegmentsRepository;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\LanguageResource\ReimportSegments\SegmentsProvider;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Segment\FilteredIterator;
use MittagQI\Translate5\T5Memory\DTO\UpdateOptions;

class CreateSnapshot
{
    public function __construct(
        private readonly ReimportSegmentRepositoryInterface $segmentsRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly SegmentsProvider $reimportSegmentsProvider,
        private readonly SegmentUpdateDtoFactory $segmentUpdateDtoFactory,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new JsonlReimportSegmentsRepository(),
            new LanguageResourceRepository(),
            new SegmentsProvider(new Segment()),
            \Zend_Registry::get('integration.segment.update.dto_factory'),
        );
    }

    public function createSnapshot(
        Task $task,
        string $runId,
        int $languageResourceId,
        ?string $timestamp,
        bool $onlyEdited,
        bool $useSegmentTimestamp,
        array $segmentIds = []
    ): void {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);

        $filters = [
            ReimportSegmentsOptions::FILTER_TIMESTAMP => $timestamp,
            ReimportSegmentsOptions::FILTER_ONLY_EDITED => $onlyEdited,
        ];

        /** @var FilteredIterator $segments */
        $segments = $this->reimportSegmentsProvider->getSegments($task->getTaskGuid(), $filters);

        if (! $segments->valid()) {
            return;
        }

        $updateOptions = UpdateOptions::fromArray([
            UpdateOptions::USE_SEGMENT_TIMESTAMP => $useSegmentTimestamp,
        ]);

        foreach ($segments as $segment) {
            if (! empty($segmentIds) && ! in_array((int) $segment->getId(), $segmentIds, true)) {
                continue;
            }

            $updateDTO = $this->segmentUpdateDtoFactory->getUpdateDTO(
                $languageResource,
                $segment,
                $task->getConfig(),
                $updateOptions,
            );

            $reimportDTO = new ReimportSegmentDTO(
                taskGuid: $segment->getTaskGuid(),
                segmentId: (int) $segment->getId(),
                source: $updateDTO->source,
                target: $updateDTO->target,
                fileName: $updateDTO->fileName,
                timestamp: $updateDTO->timestamp,
                userName: $updateDTO->userName,
                context: $updateDTO->context,
            );

            $this->segmentsRepository->save($runId, $reimportDTO);
        }
    }
}
