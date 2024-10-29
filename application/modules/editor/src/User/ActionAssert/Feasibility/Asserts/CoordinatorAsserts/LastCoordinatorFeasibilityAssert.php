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

namespace MittagQI\Translate5\User\ActionAssert\Feasibility\Asserts\CoordinatorAsserts;

use MittagQI\Translate5\ActionAssert\Action;
use MittagQI\Translate5\ActionAssert\Feasibility\Asserts\FeasibilityAssertInterface;
use MittagQI\Translate5\LSP\JobCoordinator;
use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\LastCoordinatorException;

/**
 * @implements FeasibilityAssertInterface<JobCoordinator>
 */
final class LastCoordinatorFeasibilityAssert implements FeasibilityAssertInterface
{
    public function __construct(
        private readonly JobCoordinatorRepository $jcRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            JobCoordinatorRepository::create(),
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
        // Nobody can delete the last coordinator of an LSP
        if ($this->jcRepository->getCoordinatorsCount($object->lsp) <= 1) {
            throw new LastCoordinatorException($object);
        }
    }
}
