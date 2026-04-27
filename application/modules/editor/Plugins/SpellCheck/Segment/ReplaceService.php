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

namespace MittagQI\Translate5\Plugins\SpellCheck\Segment;

use MittagQI\Translate5\Repository\SegmentQualityRepository;
use MittagQI\Translate5\Segment\SearchAndReplace\DTO\SearchQueryDto;
use MittagQI\Translate5\Segment\SearchAndReplace\ReplaceDtoFactory;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchAndReplaceService;
use MittagQI\Translate5\Segment\SearchAndReplace\SearchService;
use MittagQI\Translate5\Task\Current\NoAccessException;

class ReplaceService
{
    public function __construct(
        private readonly SegmentQualityRepository $qualityRepository,
        private readonly SegmentRepository $segmentRepository,
        private readonly ReplaceDtoFactory $replaceDtoFactory,
        private readonly SearchAndReplaceService $searchAndReplaceService,
        private readonly SearchService $searchService,
    ) {
    }

    public static function create(): self
    {
        return new self(
            SegmentQualityRepository::create(),
            SegmentRepository::create(),
            ReplaceDtoFactory::create(),
            SearchAndReplaceService::create(),
            SearchService::create(),
        );
    }

    public function replaceInAllSegmentsWithTheSameQuality(int $qualityId, array $params): array
    {
        $quality = $this->qualityRepository->get($qualityId);

        $taskGuid = $quality->getTaskGuid();
        if ($taskGuid !== $params['taskGuid']) {
            throw new NoAccessException();
        }

        $sameQualities = $this->qualityRepository->getSameQualities($quality);

        if ($params['omitCurrent']) {
            $sameQualities = array_filter($sameQualities, static function ($q) use ($qualityId) {
                return (int) $q['id'] !== $qualityId;
            });
        }

        $segments = $this->segmentRepository->getSegmentsByIdsSkipHydrating(
            array_column($sameQualities, 'segmentId'),
            $taskGuid
        );

        $searchDto = SearchQueryDto::createForExactMatch(
            taskGuid: $taskGuid,
            searchInField: 'targetEdit',
            searchFor: $quality->getAdditionalData()->content
        );

        $replaceDto = $this->replaceDtoFactory->fromParams($params, $searchDto);
        $this->searchAndReplaceService->replace($replaceDto, $segments);

        return $this->searchService->search($searchDto);
    }
}
