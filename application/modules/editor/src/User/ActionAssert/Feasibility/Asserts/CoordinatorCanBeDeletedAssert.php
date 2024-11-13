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

namespace MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts\CoordinatorAsserts\LastCoordinatorFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts\CoordinatorAsserts\LspJobRestrictionAssert;
use MittagQI\Translate5\User\Model\User;

/**
 * @implements FeasibilityAssertInterface<User>
 */
final class CoordinatorCanBeDeletedAssert implements FeasibilityAssertInterface
{
    /**
     * @param FeasibilityAssertInterface<JobCoordinator>[] $asserts
     */
    public function __construct(
        private readonly JobCoordinatorRepository $jcRepository,
        private readonly array $asserts,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            JobCoordinatorRepository::create(),
            [
                LastCoordinatorFeasibilityAssert::create(),
                LspJobRestrictionAssert::create(),
            ],
        );
    }

    public function supports(Action $action): bool
    {
        return $action === Action::Delete;
    }

    public function assertAllowed(object $object): void
    {
        // Possible coordinator that we try to delete
        $coordinator = $this->jcRepository->findByUser($object);

        if (null === $coordinator) {
            return;
        }

        foreach ($this->asserts as $assert) {
            $assert->assertAllowed($coordinator);
        }
    }
}
