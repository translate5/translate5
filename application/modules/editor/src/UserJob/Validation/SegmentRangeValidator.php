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

namespace MittagQI\Translate5\UserJob\Validation;

use editor_Models_TaskUserAssoc_Segmentrange as SegmentRange;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeFormatException;
use MittagQI\Translate5\UserJob\Exception\InvalidSegmentRangeSemanticException;

class SegmentRangeValidator
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
        );
    }

    /**
     * @throws InvalidSegmentRangeFormatException
     * @throws InvalidSegmentRangeSemanticException
     */
    public function validate(?string $segmentRanges, string $taskGuid, string $userGuid, string $workflowStepName): void
    {
        // valid: ""
        // valid: "   "
        // valid: "1-3,5,6-7"
        // not valid: "3-1,5,6-7"
        // not valid: "1-3,5,6-7" for userX when userY has the same role with "2-4"
        // not valid: "1-3,5,6+7"
        // not valid: "1-3,5,,6-7"
        $segmentRanges = SegmentRange::prepare($segmentRanges);

        if ($segmentRanges == '') {
            return;
        }

        $this->validateFormat($segmentRanges);

        //get all unsigned segments, but ignore the current assoc.
        $assignedSegments = $this->userJobRepository->getAssignedSegmentsExceptForUser(
            $taskGuid,
            $userGuid,
            $workflowStepName
        );

        $this->validateSemantics(SegmentRange::getAllSegmentGroups($segmentRanges), $assignedSegments);
    }

    /**
     * @throws InvalidSegmentRangeFormatException
     */
    private function validateFormat(?string $segmentRanges): void
    {
        if (! preg_match('/[0-9,-;]+/', $segmentRanges)) {
            throw new InvalidSegmentRangeFormatException();
        }

        $allSegmentGroups = SegmentRange::getAllSegmentGroups($segmentRanges);

        foreach ($allSegmentGroups as $segmentGroup) {
            $segmentGroupLimits = explode("-", $segmentGroup);
            if (! preg_match('/[0-9]+/', implode('', $segmentGroupLimits))) {
                throw new InvalidSegmentRangeFormatException();
            }
        }
    }

    /**
     * Values must not be in wrong order or overlapping (= neither in itself nor
     * with other users of the same role).
     *
     * @param string[] $segmentRanges
     * @param int[] $assignedSegments
     * @throws InvalidSegmentRangeSemanticException
     */
    private function validateSemantics(array $segmentRanges, array $assignedSegments): void
    {
        $segmentNumbers = [];

        foreach ($segmentRanges as $segmentGroup) {
            $segmentGroupLimits = explode("-", $segmentGroup);
            $segmentGroupStart = (int) reset($segmentGroupLimits);
            $segmentGroupEnd = (int) end($segmentGroupLimits);

            if ($segmentGroupStart > $segmentGroupEnd) {
                throw new InvalidSegmentRangeSemanticException();
            }

            for ($nr = $segmentGroupStart; $nr <= $segmentGroupEnd; $nr++) {
                if (in_array($nr, $assignedSegments)) {
                    throw new InvalidSegmentRangeSemanticException();
                }

                if (in_array($nr, $segmentNumbers)) {
                    throw new InvalidSegmentRangeSemanticException();
                }

                $segmentNumbers[] = $nr;
            }
        }
    }
}
