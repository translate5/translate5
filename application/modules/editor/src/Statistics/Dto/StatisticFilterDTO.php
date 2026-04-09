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

readonly class StatisticFilterDTO
{
    public function __construct(
        public ?int $matchRateMin = null,
        public ?int $matchRateMax = null,
        public ?int $qualityScoreMin = null,
        public ?int $qualityScoreMax = null,
        public ?array $langResource = null,
        public ?array $langResourceType = null,
        public ?array $userName = null,
        public ?array $workflow = null,
        /**
         * @var array|null the advanced job filter for workflow steps → filters on statistic table
         */
        public ?array $workflowStep = null,
        public ?array $workflowUserRole = null,
    ) {
    }

    public static function isSupported(string $name): bool
    {
        return property_exists(self::class, $name);
    }

    /**
     * @param array{
     *   matchRateMin?: int|string|null,
     *   matchRateMax?: int|string|null,
     *   qualityScoreMin?: int|string|null,
     *   qualityScoreMax?: int|string|null,
     *   langResource?: array<int|string, mixed>|null,
     *   langResourceType?: array<int|string, mixed>|null,
     *   userName?: array<int|string, mixed>|null,
     *   workflow?: array<int|string, mixed>|null,
     *   workflowStep?: array<int|string, mixed>|null,
     *   workflowUserRole?: array<int|string, mixed>|null
     * } $assocFilterList
     */
    public static function fromAssocArray(array $assocFilterList): self
    {
        return new self(...self::paramTypeCast($assocFilterList));
    }

    private static function paramTypeCast(array $assocFilterList): array
    {
        foreach (['matchRateMin', 'matchRateMax', 'qualityScoreMin', 'qualityScoreMax'] as $field) {
            if (array_key_exists($field, $assocFilterList) && $assocFilterList[$field] !== null) {
                $assocFilterList[$field] = (int) $assocFilterList[$field];
            }
        }

        return $assocFilterList;
    }

    /**
     * returns the workflow steps given with WORKFLOW#STEP as array<WORKFLOW, array<STEPS>>
     */
    public function getGroupWorkflowStepsByWorkflow(): array
    {
        $stepsByWorkflow = [];
        foreach ($this->workflowStep as $step) {
            $parts = explode('#', $step);
            if (count($parts) === 1) {
                $workflow = 'default';
                $step = $parts[0];
            } else {
                $workflow = $parts[0];
                $step = $parts[1];
            }

            if (! array_key_exists($workflow, $stepsByWorkflow)) {
                $stepsByWorkflow[$workflow] = [];
            }
            $stepsByWorkflow[$workflow][] = $step;
        }

        return $stepsByWorkflow;
    }
}
