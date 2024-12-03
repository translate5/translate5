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

namespace MittagQI\Translate5\JobAssignment\LspJob\DataProvider;

use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\User\ActionAssert\Permission\UserActionPermissionAssert;
use MittagQI\Translate5\User\ActionAssert\UserAction;
use MittagQI\Translate5\User\Model\User;
use PDO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Select;
use Zend_Db_Table;
use Zend_Db_Table_Row;

class PermissionAwareUserFetcher
{
    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
        private readonly ActionPermissionAssertInterface $userActionPermissionAssert,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
            UserActionPermissionAssert::create(),
        );
    }

    /**
     * @return array{userId: int, userGuid: string, longUserName: string}[]
     */
    public function fetchVisible(Zend_Db_Select $select, User $viewer): array
    {
        $users = [];
        $user = new User();

        $stmt = $this->db->query($select);

        $context = new PermissionAssertContext($viewer);

        while ($userData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $user->db,
                        'data' => $userData,
                        'stored' => true,
                        'readOnly' => true,
                    ]
                )
            );

            if ($this->userActionPermissionAssert->isGranted(UserAction::Read, $user, $context)) {
                $users[] = [
                    'userId' => (int) $user->getId(),
                    'userGuid' => $user->getUserGuid(),
                    'longUserName' => $user->getUsernameLong(),
                ];
            }
        }

        return $users;
    }
}
