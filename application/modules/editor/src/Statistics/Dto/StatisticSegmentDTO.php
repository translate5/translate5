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

use editor_Models_Segment_MatchRateType;

readonly class StatisticSegmentDTO
{
    public string $taskGuid;

    public string $userGuid;

    public ?string $matchRateType;

    public function __construct(
        string $taskGuid,
        string $userGuid,
        public string $wfName,
        public string $wfStepName,
        public int $segmentId,
        public int $levenshteinOriginal = 0,
        public int $levenshteinPrevious = 0,
        public ?int $matchRate = null,
        ?string $matchRateType = null,
        public ?int $langResId = 0,
        public int $isEditable = 0,
        public ?int $qualityScore = null,
        public int $latestEntry = 0,
        public int $segmentlengthPrevious = 0,
    ) {
        //conversion here prevents usage of constructor property promotion
        $this->taskGuid = trim($taskGuid, '{}');
        $this->userGuid = trim($userGuid, '{}');
        if ($matchRateType === null) {
            $this->matchRateType = null;
        } else {
            $this->matchRateType = editor_Models_Segment_MatchRateType::getLangResourceType($matchRateType);
        }
    }

    /**
     * @param array{
     *   taskGuid: string,
     *   userGuid: string,
     *   wfName: string,
     *   wfStepName: string,
     *   segmentId: int,
     *   levenshteinOriginal?: int,
     *   levenshteinPrevious?: int,
     *   matchRate?: int|null,
     *   matchRateType?: string|null,
     *   langResId?: int|null,
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

    /**
     * returns as data array for statistic insert - basically without duration!
     * @return array{
     *   taskGuid:string,
     *   userGuid:string,
     *   workflowName:string,
     *   workflowStepName:string,
     *   segmentId:int,
     *   levenshteinOriginal:int,
     *   levenshteinPrevious:int,
     *   matchRate:int|null,
     *   langResType:string|null,
     *   langResId:int|null,
     *   editable:int,
     *   latestEntry:int,
     *   qualityScore:int|null,
     *   segmentlengthPrevious:int
     * }
     */
    public function toStatisticArray(): array
    {
        return [
            'taskGuid' => $this->taskGuid,
            'userGuid' => $this->userGuid,
            'workflowName' => $this->wfName,
            'workflowStepName' => $this->wfStepName,
            'segmentId' => $this->segmentId,
            'levenshteinOriginal' => $this->levenshteinOriginal,
            'levenshteinPrevious' => $this->levenshteinPrevious,
            'matchRate' => $this->matchRate,
            'langResType' => $this->matchRateType,
            'langResId' => $this->langResId,
            'editable' => $this->isEditable,
            'latestEntry' => $this->latestEntry,
            'qualityScore' => $this->qualityScore,
            'segmentlengthPrevious' => $this->segmentlengthPrevious,
        ];
    }
}
