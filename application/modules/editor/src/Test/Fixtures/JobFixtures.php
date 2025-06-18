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

namespace MittagQI\Translate5\Test\Fixtures;

use editor_Models_TaskUserAssoc;
use editor_Workflow_Default;
use editor_Workflow_Manager;
use MittagQI\Translate5\JobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\JobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\CreateUserJobOperation;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\NewUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;

/**
 * @codeCoverageIgnore
 */
class JobFixtures
{
    private readonly TrackChangesRightsDto $trackingDto;

    public function __construct(
        private readonly CreateUserJobOperation $createUserJobOperation,
        private readonly editor_Workflow_Default $workflow,
    ) {
        $this->trackingDto = new TrackChangesRightsDto(true, true, true);
    }

    public static function create(string $wfName = 'default'): self
    {
        return new self(
            CreateUserJobOperation::create(),
            (new editor_Workflow_Manager())->getCached($wfName),
        );
    }

    public function createJob(
        string $taskGuid,
        string $userGuid,
        string $workflowStepName,
        string $jobState = 'open',
        ?string $deadlineDate = null
    ): editor_Models_TaskUserAssoc {
        $workflowDto = new WorkflowDto(
            $this->workflow->getRoleOfStep($workflowStepName),
            $this->workflow->getName(),
            $workflowStepName,
        );

        $createDto = new NewUserJobDto(
            $taskGuid,
            $userGuid,
            $jobState,
            $workflowDto,
            TypeEnum::Editor,
            null,
            null,
            $deadlineDate,
            $this->trackingDto,
        );

        return $this->createUserJobOperation->assignJob($createDto);
    }
}
