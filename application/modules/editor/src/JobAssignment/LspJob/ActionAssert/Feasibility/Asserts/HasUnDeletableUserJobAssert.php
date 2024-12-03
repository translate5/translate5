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

namespace MittagQI\Translate5\JobAssignment\LspJob\ActionAssert\Feasibility\Asserts;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\JobAssignment\LspJob\ActionAssert\Feasibility\Exception\ThereIsUnDeletableBoundJobException;
use MittagQI\Translate5\JobAssignment\LspJob\Model\LspJob;
use MittagQI\Translate5\JobAssignment\UserJob\ActionAssert\Feasibility\UserJobActionFeasibilityAssert;
use MittagQI\Translate5\Repository\UserJobRepository;

/**
 * @implements FeasibilityAssertInterface<LspJob>
 */
class HasUnDeletableUserJobAssert implements FeasibilityAssertInterface
{
    public function __construct(
        private readonly UserJobRepository $userJobRepository,
        private readonly UserJobActionFeasibilityAssert $userJobActionFeasibilityAssert,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            UserJobRepository::create(),
            UserJobActionFeasibilityAssert::create(),
        );
    }

    public function supports(Action $action): bool
    {
        return $action === Action::Delete;
    }

    public function assertAllowed(object $object): void
    {
        try {
            foreach ($this->userJobRepository->getUserJobsByLspJob((int) $object->getId()) as $job) {
                if ($job->isLspJob()) {
                    continue;
                }

                $this->userJobActionFeasibilityAssert->assertAllowed(Action::Delete, $job);
            }
        } catch (FeasibilityExceptionInterface) {
            throw new ThereIsUnDeletableBoundJobException();
        }
    }
}
