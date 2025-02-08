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

namespace MittagQI\Translate5\Repository\Contract;

use MittagQI\Translate5\CoordinatorGroup\CoordinatorGroupUser;
use MittagQI\Translate5\CoordinatorGroup\Exception\CoordinatorGroupUserNotFoundException;
use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\User\Model\User;

interface CoordinatorGroupUserRepositoryInterface
{
    public function save(CoordinatorGroupUser $groupUser): void;

    public function delete(CoordinatorGroupUser $groupUser): void;

    public function findByUser(User $user): ?CoordinatorGroupUser;

    /**
     * @throws CoordinatorGroupUserNotFoundException
     */
    public function getByUser(User $user): CoordinatorGroupUser;

    /**
     * @throws CoordinatorGroupUserNotFoundException
     */
    public function getByUserId(int $userId): CoordinatorGroupUser;

    /**
     * @throws CoordinatorGroupUserNotFoundException
     */
    public function getByUserGuid(string $userGuid): CoordinatorGroupUser;

    public function findByUserGuid(string $userGuid): ?CoordinatorGroupUser;

    /**
     * @return array<int, int>
     */
    public function getUserIdToCoordinatorGroupIdMap(): array;

    /**
     * @return iterable<User>
     */
    public function getUsers(int $groupId): iterable;

    /**
     * @return array<string>
     */
    public function getUserGuids(int $groupId): array;

    /**
     * @return iterable<CoordinatorGroupUser>
     */
    public function getCoordinatorGroupUsers(CoordinatorGroup $group): iterable;
}
