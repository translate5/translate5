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
use editor_Services_Manager;
use MittagQI\Translate5\LanguageResource\Adapter\UpdatableAdapterInterface;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\CsvReimportSegmentRepository;
use MittagQI\Translate5\LanguageResource\ReimportSegments\Repository\ReimportSegmentRepositoryInterface;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Segment\FilteredIterator;

class ReimportSegmentsSnapshot
{
    public function __construct(
        private readonly editor_Services_Manager $serviceManager,
        private readonly ReimportSegmentRepositoryInterface $segmentsRepository,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly ReimportSegmentsProvider $reimportSegmentsProvider,
        private readonly ReApplyProtectionRules $reApplyProtectionRules,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new editor_Services_Manager(),
            new CsvReimportSegmentRepository(),
            new LanguageResourceRepository(),
            new ReimportSegmentsProvider(new Segment()),
            ReApplyProtectionRules::create(),
        );
    }

    public function createSnapshot(
        Task $task,
        string $runId,
        int $languageResourceId,
        ?string $timestamp,
        bool $onlyEdited,
        bool $useSegmentTimestamp
    ): void {
        $languageResource = $this->languageResourceRepository->get($languageResourceId);
        $connector = $this->getConnector($languageResource, $task);

        $filters = [
            ReimportSegmentsOptions::FILTER_TIMESTAMP => $timestamp,
            ReimportSegmentsOptions::FILTER_ONLY_EDITED => $onlyEdited,
        ];

        /** @var FilteredIterator $segments */
        $segments = $this->reimportSegmentsProvider->getSegments($task->getTaskGuid(), $filters);

        if (! $segments->valid()) {
            return;
        }

        $updateOptions = [
            UpdatableAdapterInterface::USE_SEGMENT_TIMESTAMP => $useSegmentTimestamp,
        ];

        foreach ($segments as $segment) {
            $updateDTO = $connector->getUpdateDTO($segment, $updateOptions);
            $updateDTO = $this->reApplyProtectionRules->reApplyRules(
                $updateDTO,
                (int) $task->getSourceLang(),
                (int) $task->getTargetLang(),
            );

            $this->segmentsRepository->save($runId, $updateDTO);
        }
    }

    private function getConnector(
        LanguageResource $languageResource,
        Task $task
    ): UpdatableAdapterInterface|\editor_Services_Connector {
        return $this->serviceManager->getConnector(
            $languageResource,
            config: $task->getConfig(),
            customerId: (int) $task->getCustomerId(),
        );
    }
}
