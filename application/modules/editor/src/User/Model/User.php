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

namespace MittagQI\Translate5\User\Model;

use MittagQI\Translate5\Acl\Roles;
use MittagQI\Translate5\User\Operations\DTO\CreateUserDto;

class User extends \ZfExtended_Models_User
{
    public function isCoordinator(): bool
    {
        return in_array(Roles::JOB_COORDINATOR, $this->getRoles(), true);
    }

    public function isPm(): bool
    {
        return in_array(Roles::PM, $this->getRoles(), true);
    }

    public function isClientPm(): bool
    {
        return in_array(Roles::CLIENTPM, $this->getRoles(), true);
    }

    public function isPmLight(): bool
    {
        return in_array(Roles::PMLIGHT, $this->getRoles(), true);
    }

    public function isAdmin(): bool
    {
        return in_array(Roles::ADMIN, $this->getRoles(), true)
            || in_array(Roles::SYSTEMADMIN, $this->getRoles(), true);
    }

    public function setInitialFields(CreateUserDto $dto): void
    {
        $this->setUserGuid($dto->guid);
        $this->setLogin($dto->login);
        $this->setEmail($dto->email);
        $this->setFirstName($dto->firstName);
        $this->setSurName($dto->surName);
        $this->setGender($dto->gender);
        if (null !== $dto->locale) {
            $this->setLocale($dto->locale);
        }
    }
}
