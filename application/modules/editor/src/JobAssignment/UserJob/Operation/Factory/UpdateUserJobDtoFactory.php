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

namespace MittagQI\Translate5\JobAssignment\UserJob\Operation\Factory;

use editor_Workflow_Manager;
use MittagQI\Translate5\JobAssignment\Exception\InvalidTypeProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\InvalidStateProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\WorkflowStepNotProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Operation\DTO\UpdateUserJobDto;
use MittagQI\Translate5\JobAssignment\UserJob\Validation\SegmentRangeValidator;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\Exception\InexistentTaskException;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use REST_Controller_Request_Http as Request;

class UpdateUserJobDtoFactory extends AbstractUserJobDtoFactory
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly UserRepository $userRepository,
        editor_Workflow_Manager $workflowManager,
        SegmentRangeValidator $segmentRangeValidator,
    ) {
        parent::__construct($workflowManager, $segmentRangeValidator);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            TaskRepository::create(),
            UserJobRepository::create(),
            new UserRepository(),
            new editor_Workflow_Manager(),
            SegmentRangeValidator::create(),
        );
    }

    /**
     * @throws InexistentTaskException
     * @throws InexistentUserException
     * @throws InvalidStateProvidedException
     * @throws InvalidTypeProvidedException
     */
    public function fromRequest(Request $request): UpdateUserJobDto
    {
        $data = $request->getParam('data');
        $data = json_decode($data, true, flags: JSON_THROW_ON_ERROR);

        $job = $this->userJobRepository->get((int) $request->getParam('id'));
        $task = $this->taskRepository->getByGuid($job->getTaskGuid());

        try {
            $workflowDto = $this->getWorkflowDto($data, $task);
        } catch (WorkflowStepNotProvidedException) {
            $workflowDto = null;
        }

        $state = $data['state'] ?? null;

        $userGuid = empty($data['userGuid'])
            ? null
            : $this->userRepository->getByGuid($data['userGuid'])->getUserGuid();

        $deadlineDate = $data['deadlineDate'] ?? null;
        $segmentRanges = null;

        if (null !== $workflowDto) {
            $segmentRanges = $this->getSegmentRanges(
                $data['segmentrange'] ?? null,
                $job->getTaskGuid(),
                $job->getUserGuid(),
                $workflowDto->workflowStepName
            );
        }

        return new UpdateUserJobDto(
            $userGuid,
            $state,
            $workflowDto,
            $segmentRanges,
            $deadlineDate,
            isset($data['trackchangesShow']) ? (bool) $data['trackchangesShow'] : null,
            isset($data['trackchangesShowAll']) ? (bool) $data['trackchangesShowAll'] : null,
            isset($data['trackchangesAcceptReject']) ? (bool) $data['trackchangesAcceptReject'] : null,
        );
    }
}
