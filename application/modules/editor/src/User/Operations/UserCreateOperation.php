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
use MittagQI\Translate5\User\DTO\CreateUserDto;
use MittagQI\Translate5\User\Exception\GuidAlreadyInUseException;
use MittagQI\Translate5\User\Exception\LoginAlreadyInUseException;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_User as User;
use ZfExtended_Utils;
use ZfExtended_ValidateException;

final class UserCreateOperation
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_ValidateException
     * @throws LoginAlreadyInUseException
     * @throws GuidAlreadyInUseException
     */
    public function createUser(CreateUserDto $dto): User
    {
        $user = $this->userRepository->getEmptyModel();
        $user->setUserGuid(ZfExtended_Utils::guid(true));
        $user->setLogin($dto->login);
        $user->setEmail($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setSurName($dto->surName);
        $user->setGender($dto->gender);
        $user->setCustomers(',,');

        $user->validate();

        try {
            $this->userRepository->save($user);
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $field = $e->getExtra('field') ?? '';

            if ($field === 'login') {
                throw new LoginAlreadyInUseException();
            }

            if ($field === 'userGuid') {
                throw new GuidAlreadyInUseException();
            }

            throw $e;
        }

        return $user;
    }
}
