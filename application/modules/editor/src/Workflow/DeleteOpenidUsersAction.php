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

namespace MittagQI\Translate5\Workflow;

use editor_Models_TaskUserAssoc;
use editor_Workflow_Actions_Abstract;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_LoginLog;
use ZfExtended_Models_User;

class DeleteOpenidUsersAction extends editor_Workflow_Actions_Abstract
{
    public const FALLBACK_PM_CONFIG = 'runtimeOptions.openid.fallbackPm';
    public function deleteOpenidUsers(bool $logOnDelete = true)
    {
        $openidConfig = Zend_Registry::get('config')->runtimeOptions->openid;
        $userDb = ZfExtended_Factory::get(ZfExtended_Models_User::class)->db;
        $loginLog = ZfExtended_Factory::get(ZfExtended_Models_LoginLog::class)->db;
        $logger = Zend_Registry::get('logger');

        $removeUsersAfterDays = (int) $openidConfig->removeUsersAfterDays;

        if ($removeUsersAfterDays <= 0) {
            return true;
        }

        $fallbackPm = null;

        if ($openidConfig->fallbackPm) {
            $fallbackPm = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $fallbackPm->load($openidConfig->fallbackPm);
        }

        $select = $userDb->select()
            ->setIntegrityCheck(false)
            ->from(['user' => $userDb->info($userDb::NAME)])
            ->join(
                ['loginLog' => $loginLog->info($loginLog::NAME)],
                'user.login = loginLog.login',
                ['max(created) as last_login']
            )
            ->group('login')
            ->where('user.openIdIssuer IS NOT NULL')
            ->having('last_login < CURDATE() - INTERVAL ' . $removeUsersAfterDays . ' DAY')
        ;

        $logins = [];
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $userTaskAssocDb = ZfExtended_Factory::get(editor_Models_TaskUserAssoc::class)->db;

        $pmIds = array_column(
            ZfExtended_Factory::get(ZfExtended_Models_User::class)->loadAllByRole([ACL_ROLE_PM]),
            'id'
        );

        foreach ($userDb->fetchAll($select) as $row) {
            $user->load($row->id);

            if ((int) $openidConfig->fallbackPm === (int) $user->getId()) {
                continue;
            }

            if (in_array($user->getId(), $pmIds) && null === $fallbackPm) {
                continue;
            }

            $logins[] = $row->login;

            $userTaskAssocDb->delete(['userGuid = ?', $user->getUserGuid()]);
            $user->delete();
        }

        if (!empty($logins) && $logOnDelete) {
            $logger->info(
                'E1013',
                sprintf(
                    'SSO Users were deleted after %s days without login: %s',
                    $removeUsersAfterDays,
                    implode(',', $logins)
                )
            );
        }

        return true;
    }
}
