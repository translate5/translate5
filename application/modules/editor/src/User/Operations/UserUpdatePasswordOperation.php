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
use MittagQI\Translate5\User\Contract\UserDeleteOperationInterface;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Models_User as User;

final class UserUpdatePasswordOperation
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ZfExtended_AuthenticationInterface $authentication,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new UserRepository(),
            ZfExtended_Authentication::getInstance(),
        );
    }

    /**
     * @throws \Zend_Exception
     */
    public function updatePassword(User $user, ?string $password): void
    {
        $password = empty($password) ? null : $this->authentication->createSecurePassword($password);

        $user->setPasswd($password);

        $this->userRepository->save($user);
    }
}
