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

namespace MittagQI\Translate5\JobAssignment\CoordinatorGroupJob\Operation\DTO;

use DateTime;
use editor_Workflow_Default as Workflow;
use Exception;
use MittagQI\Translate5\JobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\JobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidAssignmentDateStringProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;

class NewCoordinatorGroupJobDto
{
    public function __construct(
        public readonly string $taskGuid,
        public readonly string $userGuid,
        public readonly string $state,
        public readonly WorkflowDto $workflow,
        public readonly ?string $segmentRange,
        public readonly ?string $assignmentDate,
        public readonly ?string $deadlineDate,
        public readonly TrackChangesRightsDto $trackChangesRights,
    ) {
        if (! in_array($state, Workflow::getAllStates())) {
            throw new InvalidStateProvidedException();
        }

        if (null !== $deadlineDate) {
            try {
                new DateTime($deadlineDate);
            } catch (Exception) {
                throw new InvalidDeadlineDateStringProvidedException();
            }
        }

        if (null !== $assignmentDate) {
            try {
                new DateTime($assignmentDate);
            } catch (Exception) {
                throw new InvalidAssignmentDateStringProvidedException();
            }
        }
    }

    public static function fromUserJobDto(NewUserJobDto $userJobDto): self
    {
        return new self(
            $userJobDto->taskGuid,
            $userJobDto->userGuid,
            $userJobDto->state,
            $userJobDto->workflow,
            $userJobDto->segmentRange,
            $userJobDto->assignmentDate,
            $userJobDto->deadlineDate,
            $userJobDto->trackChangesRights ?: new TrackChangesRightsDto(false, false, false),
        );
    }
}
