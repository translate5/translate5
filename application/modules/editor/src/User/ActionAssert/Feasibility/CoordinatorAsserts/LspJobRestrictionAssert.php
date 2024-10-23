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

namespace MittagQI\Translate5\User\ActionAssert\Feasibility\CoordinatorAsserts;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\ActionFeasibilityAssertInterface;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LspJob\ActionAssert\Feasibility\LspJobActionFeasibilityAssert;
use MittagQI\Translate5\LspJob\Model\LspJobAssociation;
use MittagQI\Translate5\Repository\LspJobRepository;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\CoordinatorHasBlockingLspJobException;

/**
 * @implements FeasibilityAssertInterface<JobCoordinator>
 */
class LspJobRestrictionAssert implements FeasibilityAssertInterface
{
    /**
     * @param ActionFeasibilityAssertInterface<LspJobAssociation> $lsJobActionFeasibilityAssert
     */
    public function __construct(
        private readonly UserJobRepository $userRepository,
        private readonly LspJobRepository $lspJobRepository,
        private readonly ActionFeasibilityAssertInterface $lsJobActionFeasibilityAssert,
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
            LspJobActionFeasibilityAssert::create(),
        );
    }

    public function supports(Action $action): bool
    {
        return $action === Action::Delete;
    }

    /**
     * {@inheritDoc}
     */
    public function assertAllowed(object $object): void
    {
        foreach ($this->userRepository->getJobsByUserGuid($object->user->getUserGuid()) as $job) {
            if (! $job->isLspJob()) {
                continue;
            }

            $lspJob = $this->lspJobRepository->get((int) $job->getLspJobId());

            try {
                $this->lsJobActionFeasibilityAssert->assertAllowed(Action::Delete, $lspJob);
            } catch (FeasibilityExceptionInterface) {
                throw new CoordinatorHasBlockingLspJobException($object, $lspJob);
            }
        }
    }
}
