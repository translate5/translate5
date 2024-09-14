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

use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\User\ActionAssert\Action;
use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssert;
use MittagQI\Translate5\User\ActionAssert\Feasibility\UserActionFeasibilityAssertInterface;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\ZfExtended\Acl\SystemResource;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Models_User as User;
use ZfExtended_ValidateException;

final class UserUpdateParentIdsOperation
{
    public function __construct(
        private readonly UserActionFeasibilityAssertInterface $userActionFeasibilityChecker,
        private readonly ZfExtended_Acl $acl,
        private readonly UserRepository $userRepository,
        private readonly UserSetParentIdsOperation $setParentIds,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        $acl = ZfExtended_Acl::getInstance();

        return new self(
            UserActionFeasibilityAssert::create(),
            $acl,
            new UserRepository(),
            UserSetParentIdsOperation::create(),
        );
    }

    /**
     * @throws FeasibilityExceptionInterface
     * @throws ProvidedParentIdCannotBeEvaluatedToUserException
     * @throws Zend_Acl_Exception
     */
    public function updateParentIdsBy(User $user, string $parentId, User $authUser): void
    {
        $parentId = $this->resolveParentUserId($parentId, $authUser);

        //FIXME currently its not possible for seeAllUsers users to remove the parentIds flag by set it to null/""
        if (! $parentId) {
            return;
        }

        $this->updateParentIds($user, $parentId);
    }

    /**
     * @throws FeasibilityExceptionInterface
     * @throws ProvidedParentIdCannotBeEvaluatedToUserException
     * @throws Zend_Acl_Exception
     * @throws ZfExtended_ValidateException
     */
    public function updateParentIds(User $user, ?string $parentId): void
    {
        $this->userActionFeasibilityChecker->assertAllowed(Action::UPDATE, $user);

        $this->setParentIds->setParentIds($user, $parentId);

        $this->userRepository->save($user);
    }

    private function resolveParentUserId(?string $parentId, User $authUser): ?string
    {
        if (! $this->canSeeAllUsers($authUser) || empty($parentId)) {
            return null;
        }

        return $parentId;
    }

    private function canSeeAllUsers(User $authUser): bool
    {
        try {
            return $this->acl->isInAllowedRoles(
                $authUser->getRoles(),
                SystemResource::ID,
                SystemResource::SEE_ALL_USERS
            );
        } catch (Zend_Acl_Exception) {
            return false;
        }
    }
}
