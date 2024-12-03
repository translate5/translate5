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

namespace MittagQI\Translate5\JobAssignment;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\JobAssignment\LspJob\ActionAssert\Permission\LspJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\UserJobViewDataProvider;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\User\Model\User;

/**
 * @template Job of array{
 * id: int,
 * taskGuid: string,
 * userGuid: string,
 * sourceLang: int,
 * targetLang: int,
 * state: string,
 * role: string,
 * workflowStepName: string,
 * workflow: string,
 * segmentrange: string|null,
 * segmentEditableCount: int,
 * segmentFinishCount: int,
 * usedState: string|null,
 * deadlineDate: string,
 * assignmentDate: string,
 * finishedDate: string|null,
 * trackchangesShow: bool,
 * trackchangesShowAll: bool,
 * trackchangesAcceptReject: bool,
 * type: int,
 * login: string,
 * firstName: string,
 * surName: string,
 * longUserName: string,
 * lspId: int|null,
 * isLspJob: bool,
 * isLspUserJob: bool,
 * staticAuthHash?: string,
 * }
 */
class JobAssignmentViewDataProvider
{
    public function __construct(
        private readonly UserJobViewDataProvider $userJobViewDataProvider,
        private readonly LspJobRepository $lspJobRepository,
        private readonly UserJobRepository $userJobRepository,
        private readonly ActionPermissionAssertInterface $lspJobActionPermissionAssert,
    ) {
    }

    public static function create(): self
    {
        return new self(
            UserJobViewDataProvider::create(),
            LspJobRepository::create(),
            UserJobRepository::create(),
            LspJobActionPermissionAssert::create(),
        );
    }

    /**
     * @return Job[]
     */
    public function getListFor(string $taskGuid, User $viewer): array
    {
        $lspJobs = [];
        $context = new PermissionAssertContext($viewer);

        foreach ($this->lspJobRepository->getTaskLspJobs($taskGuid) as $lspJob) {
            if ($this->lspJobActionPermissionAssert->isGranted(Action::Read, $lspJob, $context)) {
                $dataJob = $this->userJobRepository->getDataJobByLspJob((int) $lspJob->getId());

                $lspJobs[] = $this->userJobViewDataProvider->buildJobView($dataJob, $viewer);
            }
        }

        $userJobs = $this->userJobViewDataProvider->getListFor($taskGuid, $viewer);

        return array_merge($lspJobs, $userJobs);
    }
}
