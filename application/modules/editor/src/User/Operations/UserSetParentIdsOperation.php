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

namespace MittagQI\Translate5\User\Operations;

use MittagQI\Translate5\LSP\JobCoordinatorRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForJobCoordinatorException;
use MittagQI\Translate5\User\Exception\InvalidParentUserProvidedForLspUserException;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\Translate5\User\Model\User;
use MittagQI\Translate5\User\Validation\ParentUserValidator;
use Zend_Acl_Exception;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_ValidateException;

/**
 * Ment to be used to initialize parentIds for a user.
 * So only on User creation or in special cases where the parentIds need to be reinitialized.
 */
final class UserSetParentIdsOperation implements UserSetParentIdsOperationInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly JobCoordinatorRepository $coordinatorRepository,
        private readonly ParentUserValidator $parentUserValidator,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            JobCoordinatorRepository::create(),
            ParentUserValidator::create(),
        );
    }

    /**
     * @throws ProvidedParentIdCannotBeEvaluatedToUserException
     * @throws InvalidParentUserProvidedForJobCoordinatorException
     * @throws InvalidParentUserProvidedForLspUserException
     * @throws Zend_Acl_Exception
     * @throws ZfExtended_ValidateException
     */
    public function setParentIds(ZfExtended_Models_User $user, ?string $parentId): void
    {
        if (empty($parentId)) {
            $user->setParentIds(',,');

            return;
        }

        try {
            $parentUser = $this->userRepository->resolveUser($parentId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new ProvidedParentIdCannotBeEvaluatedToUserException($parentId);
        }

        $this->parentUserValidator->assertUserCanBeSetAsParentTo($parentUser, $user);

        $parentIds = $this->getParentIds($parentUser);

        $user->setParentIds(',' . implode(',', $parentIds) . ',');

        $user->validate();
    }

    /**
     * @return int[]
     */
    private function getParentIds(User $parentUser): array
    {
        $parentCoordinator = $this->coordinatorRepository->findByUser($parentUser);

        if (null !== $parentCoordinator) {
            return [(int) $parentUser->getId()];
        }

        $parentIds = [];

        if (! empty($parentUser->getParentIds())) {
            $parentIds = array_map(
                'intval',
                explode(',', trim($parentUser->getParentIds(), ' ,'))
            );
        }

        $parentIds[] = (int) $parentUser->getId();

        return $parentIds;
    }
}
