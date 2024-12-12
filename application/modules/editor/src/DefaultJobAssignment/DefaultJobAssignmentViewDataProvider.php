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

namespace MittagQI\Translate5\DefaultJobAssignment;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\ActionAssert\Permission\DefaultUserJobActionPermissionAssert;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\DefaultUserJobViewDataProvider;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use MittagQI\Translate5\User\Model\User;

/**
 * @template DefaultJob of array{
 * id: int,
 * customerId: int,
 * userGuid: string,
 * sourceLang: int,
 * targetLang: int,
 * workflowStepName: string,
 * workflow: string,
 * deadlineDate: float,
 * trackchangesShow: bool,
 * trackchangesShowAll: bool,
 * trackchangesAcceptReject: bool,
 * type: int,
 * lspId: int|null,
 * isLspJob: bool,
 * }
 */
class DefaultJobAssignmentViewDataProvider
{
    public function __construct(
        private readonly DefaultUserJobViewDataProvider $defaultUserJobViewDataProvider,
        private readonly DefaultLspJobRepository $defaultLspJobRepository,
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly ActionPermissionAssertInterface $lspJobActionPermissionAssert,
    ) {
    }

    public static function create(): self
    {
        return new self(
            DefaultUserJobViewDataProvider::create(),
            DefaultLspJobRepository::create(),
            DefaultUserJobRepository::create(),
            DefaultUserJobActionPermissionAssert::create(),
        );
    }

    /**
     * @return DefaultJob[]
     */
    public function getListFor(int $customerId, string $workflow, User $viewer): array
    {
        $jobs = [];
        $context = new PermissionAssertContext($viewer);

        $lspJobs = $this->defaultLspJobRepository->getDefaultLspJobsOfForCustomerAndWorkflow($customerId, $workflow);

        foreach ($lspJobs as $lspJob) {
            if ($this->lspJobActionPermissionAssert->isGranted(DefaultJobAction::Read, $lspJob, $context)) {
                $dataJob = $this->defaultUserJobRepository->get((int) $lspJob->getDataJobId());

                $jobs[] = $this->defaultUserJobViewDataProvider->buildJobView($dataJob);
            }
        }

        $userJobs = $this->defaultUserJobViewDataProvider->getListFor($customerId, $workflow, $viewer);

        return array_merge($jobs, $userJobs);
    }
}
