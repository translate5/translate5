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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation;

use editor_Workflow_Manager;
use MittagQI\Translate5\DefaultJobAssignment\Contract\UpdateDefaultLspJobOperationInterface;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\DefaultJobAssignment\DTO\UpdateDefaultJobDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\OnlyCoordinatorCanBeAssignedToLspJobException;
use MittagQI\Translate5\LSP\Exception\CoordinatorDontBelongToLspException;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use Throwable;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class UpdateDefaultLspJobOperation implements UpdateDefaultLspJobOperationInterface
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly DefaultLspJobRepository $defaultLspJobRepository,
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly editor_Workflow_Manager $workflowManager,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            DefaultLspJobRepository::create(),
            DefaultUserJobRepository::create(),
            JobCoordinatorRepository::create(),
            new editor_Workflow_Manager(),
        );
    }

    public function updateJob(DefaultLspJob $job, UpdateDefaultJobDto $dto): void
    {
        $dataJob = $this->defaultUserJobRepository->get((int) $job->getDataJobId());

        if (null !== $dto->userGuid) {
            $coordinator = $this->coordinatorRepository->findByUserGuid($dto->userGuid);

            if ($coordinator === null) {
                throw new OnlyCoordinatorCanBeAssignedToLspJobException();
            }

            if ((int)$job->getLspId() !== (int)$coordinator->lsp->getId()) {
                throw new CoordinatorDontBelongToLspException();
            }

            $dataJob->setUserGuid($dto->userGuid);
        }

        if (null !== $dto->workflowStepName) {
            $workflow = $this->workflowManager->getCached($job->getWorkflow());

            if (! in_array($dto->workflowStepName, $workflow->getUsableSteps())) {
                throw new InvalidWorkflowStepProvidedException();
            }

            $job->setWorkflowStepName($dto->workflowStepName);
            $dataJob->setWorkflowStepName($dto->workflowStepName);
        }

        if (null !== $dto->sourceLanguageId) {
            $job->setSourceLang($dto->sourceLanguageId);
            $dataJob->setSourceLang($dto->sourceLanguageId);
        }

        if (null !== $dto->targetLanguageId) {
            $job->setTargetLang($dto->targetLanguageId);
            $dataJob->setTargetLang($dto->targetLanguageId);
        }

        if (null !== $dto->deadline) {
            $dataJob->setDeadlineDate($dto->deadline);
        }

        if (null !== $dto->canSeeTrackChangesOfPrevSteps) {
            $dataJob->setTrackchangesShow((int) $dto->canSeeTrackChangesOfPrevSteps);
        }

        if (null !== $dto->canSeeAllTrackChanges) {
            $dataJob->setTrackchangesShowAll((int) $dto->canSeeAllTrackChanges);
        }

        if (null !== $dto->canAcceptOrRejectTrackChanges) {
            $dataJob->setTrackchangesAcceptReject((int) $dto->canAcceptOrRejectTrackChanges);
        }

        $this->db->beginTransaction();

        try {
            $this->defaultLspJobRepository->save($job);
            $this->defaultUserJobRepository->save($dataJob);
        } catch (Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }

        $this->db->commit();
    }
}
