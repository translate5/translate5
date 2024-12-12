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

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob;

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\DefaultUserJobActionPermissionAssert;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
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
class DefaultUserJobViewDataProvider
{
    public function __construct(
        private readonly DefaultUserJobRepository $defaultUserJobRepository,
        private readonly DefaultLspJobRepository $defaultLspJobRepository,
        private readonly ActionPermissionAssertInterface $defaultUserJobPermissionAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            DefaultUserJobRepository::create(),
            DefaultLspJobRepository::create(),
            DefaultUserJobActionPermissionAssert::create(),
        );
    }

    /**
     * @return DefaultJob[]
     */
    public function buildViewForList(iterable $jobs, User $viewer): array
    {
        $result = [];
        $context = new PermissionAssertContext($viewer);

        foreach ($jobs as $job) {
            $job = $this->getJob($job);

            if (! $this->defaultUserJobPermissionAssert->isGranted(DefaultJobAction::Read, $job, $context)) {
                continue;
            }

            $result[] = $this->buildJobView($job);
        }

        return $result;
    }

    /**
     * @return DefaultJob[]
     */
    public function getListFor(int $customerId, string $workflow, User $viewer): array
    {
        $jobs = $this->defaultUserJobRepository->getDefaultUserJobsOfForCustomerAndWorkflow($customerId, $workflow);

        return $this->buildViewForList($jobs, $viewer);
    }

    private function getJob(array|DefaultUserJob $job): DefaultUserJob
    {
        if ($job instanceof DefaultUserJob) {
            return $job;
        }

        $tua = new DefaultUserJob();
        $tua->init($job);

        return $tua;
    }

    /**
     * @return DefaultJob
     */
    public function buildJobView(DefaultUserJob $job): array
    {
        $lspJob = $this->defaultLspJobRepository->findDefaultLspJobByDataJobId((int) $job->getId());
        $type = null !== $lspJob ? TypeEnum::Lsp : TypeEnum::Editor;

        return [
            'id' => (int) $job->getId(),
            'customerId' => (int) $job->getCustomerId(),
            'userGuid' => $job->getUserGuid(),
            'sourceLang' => (int) $job->getSourceLang(),
            'targetLang' => (int) $job->getTargetLang(),
            'workflow' => $job->getWorkflow(),
            'workflowStepName' => $job->getWorkflowStepName(),
            'deadlineDate' => (float) $job->getDeadlineDate(),
            'trackchangesShow' => (bool) $job->getTrackchangesShow(),
            'trackchangesShowAll' => (bool) $job->getTrackchangesShowAll(),
            'trackchangesAcceptReject' => (bool) $job->getTrackchangesAcceptReject(),
            'type' => $type->value,
            'lspId' => $lspJob ? (int) $lspJob->getLspId() : null,
            'isLspJob' => null !== $lspJob,
        ];
    }
}
