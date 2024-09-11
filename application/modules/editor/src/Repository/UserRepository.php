<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Repository;

use MittagQI\ZfExtended\Acl\Roles;
use Zend_Db_Table_Row;
use ZfExtended_Factory;
use ZfExtended_Models_User;

class UserRepository
{
    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $id): ZfExtended_Models_User
    {
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->load($id);

        return $user;
    }

    /**
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function getByGuid(string $guid): ZfExtended_Models_User
    {
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid($guid);

        return $user;
    }

    public function getEmptyModel(): ZfExtended_Models_User
    {
        return ZfExtended_Factory::get(ZfExtended_Models_User::class);
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function save(ZfExtended_Models_User $user): void
    {
        $user->save();
    }

    /**
     * @return iterable<ZfExtended_Models_User>
     */
    public function getPmList(array $roles, ?int $customerInContext = null): iterable
    {
        $userModel = ZfExtended_Factory::get(ZfExtended_Models_User::class);

        $users = ZfExtended_Factory::get(ZfExtended_Models_User::class)->loadAllByRole($roles);

        foreach ($users as $user) {
            $userModel->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $userModel->db,
                        'data' => $user,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            $roles = $userModel->getRoles();

            if (in_array(Roles::PM, $roles)) {
                yield clone $userModel;

                continue;
            }

            if ($customerInContext !== null && in_array($customerInContext, $userModel->getCustomersArray())) {
                yield clone $userModel;
            }
        }
    }

    public function delete(ZfExtended_Models_User $user): void
    {
        $user->delete();
    }
}
