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

namespace MittagQI\Translate5\Statistics\Dto;

/**
 * manipulatable DTO for data collection
 */
class StatisticSegmentAggregationDTO
{
    public function __construct(
        public string $userGuid,
        public string $editedInStep,
        public string $workflowStep,
        public int $id,
        public int $autoStateId,
        public int $levenshteinOriginal = 0,
        public int $levenshteinPrevious = 0,
        public ?int $matchRate = null,
        public ?string $matchRateType = null,
        public int $isEditable = 0,
        public ?int $qualityScore = null,
        public int $latestEntry = 0,
        public int $segmentlengthPrevious = 0,
    ) {
    }

    /**
     * @param array{
     *   userGuid: string,
     *   editedInStep: string,
     *   workflowStep: string,
     *   id: int,
     *   autoStateId: int,
     *   levenshteinOriginal?: int,
     *   levenshteinPrevious?: int,
     *   matchRate?: int|null,
     *   matchRateType?: string|null,
     *   isEditable?: int,
     *   qualityScore?: int|null,
     *   latestEntry?: int,
     *   segmentlengthPrevious?: int
     * } $assocFilterList
     */
    public static function fromAssocArray(array $assocFilterList): self
    {
        return new self(...$assocFilterList);
    }
}
