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

namespace MittagQI\Translate5\JobAssignment\LspJob\Validation;

use editor_Models_Task as Task;
use MittagQI\Translate5\JobAssignment\Exception\ConfirmedCompetitiveJobAlreadyExistsException;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\CoordinatorOfParentLspHasNotConfirmedLspJobYetException;
use MittagQI\Translate5\JobAssignment\LspJob\Exception\NotFoundLspJobException;
use MittagQI\Translate5\JobAssignment\UserJob\Exception\AttemptToAssignSubLspJobBeforeParentJobCreatedException;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;

class CompetitiveJobCreationValidator
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly LspJobRepository $lspJobRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            LspJobRepository::create(),
        );
    }

    /**
     * @throws ConfirmedCompetitiveJobAlreadyExistsException
     */
    public function assertCanCreate(
        Task $task,
        LanguageServiceProvider $lsp,
        string $workflow,
        string $workflowStepName
    ): void {
        if (! $task->isCompetitive()) {
            return;
        }

        if (! $lsp->isDirectLsp()) {
            try {
                // check if parent LSP Job exists. Sub LSP can have only jobs related to its parent LSP
                $parentJob = $this->lspJobRepository->getByLspIdTaskGuidAndWorkflow(
                    (int) $lsp->getParentId(),
                    $task->getTaskGuid(),
                    $workflow,
                    $workflowStepName,
                );
            } catch (NotFoundLspJobException) {
                throw new AttemptToAssignSubLspJobBeforeParentJobCreatedException();
            }

            $dataJob = $this->userJobRepository->getDataJobByLspJob((int) $parentJob->getId());

            if (! $dataJob->isConfirmed()) {
                throw new CoordinatorOfParentLspHasNotConfirmedLspJobYetException();
            }

            return;
        }

        if ($this->userJobRepository->taskHasConfirmedJob($task->getTaskGuid(), $workflow, $workflowStepName)) {
            throw new ConfirmedCompetitiveJobAlreadyExistsException();
        }
    }
}
