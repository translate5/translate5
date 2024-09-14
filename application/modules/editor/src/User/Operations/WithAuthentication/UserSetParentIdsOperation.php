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

namespace MittagQI\Translate5\User\Operations\WithAuthentication;

use MittagQI\Translate5\User\ActionAssert\Feasibility\Exception\FeasibilityExceptionInterface;
use MittagQI\Translate5\User\Contract\UserSetParentIdsOperationInterface;
use MittagQI\Translate5\User\Exception\ProvidedParentIdCannotBeEvaluatedToUserException;
use MittagQI\ZfExtended\Acl\SystemResource;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User as User;

/**
 * Ment to be used to initialize parentIds for a user.
 * So only on User creation or in special cases where the parentIds need to be reinitialized.
 */
final class UserSetParentIdsOperation implements UserSetParentIdsOperationInterface
{
    public function __construct(
        private readonly ZfExtended_Acl $acl,
        private readonly \MittagQI\Translate5\User\Operations\UserSetParentIdsOperation $operation,
        private readonly ZfExtended_AuthenticationInterface $authentication,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            ZfExtended_Acl::getInstance(),
            \MittagQI\Translate5\User\Operations\UserSetParentIdsOperation::create(),
            ZfExtended_Authentication::getInstance(),
        );
    }

    /**
     * @throws FeasibilityExceptionInterface
     * @throws ProvidedParentIdCannotBeEvaluatedToUserException
     * @throws Zend_Acl_Exception
     */
    public function setParentIds(User $user, ?string $parentId): void
    {
        $this->operation->setParentIds(
            $user,
            $this->resolveParentUserId(
                $parentId,
                $this->authentication->getUser()
            )
        );
    }

    private function resolveParentUserId(?string $parentId, User $authUser): string
    {
        if (! $this->canSeeAllUsers($authUser) || empty($parentId)) {
            return (string) $authUser->getId();
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
