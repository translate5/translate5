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

namespace MittagQI\Translate5\Test\Integration\Workflow;

use DateTime;
use MittagQI\Translate5\Test\UnitTestAbstract;
use MittagQI\Translate5\Workflow\DeleteOpenidUsersAction;
use stdClass;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_LoginLog;
use ZfExtended_Models_User;
use ZfExtended_Utils;

class DeleteOpenidUsersActionTest extends UnitTestAbstract
{
    private const USER_LOGIN = 'DeleteOpenidUsersActionTest';

    private const PM_USER_LOGIN = 'PM_DeleteOpenidUsersActionTest';

    private const FALLBACK_PM_USER_LOGIN = 'Fallback_PM_DeleteOpenidUsersActionTest';

    private const LAST_LOGIN_INTERVAL_IN_DB = 9;

    private $config;

    public function setUp(): void
    {
        $this->config = Zend_Registry::get('config');
        $date = (new DateTime('- ' . self::LAST_LOGIN_INTERVAL_IN_DB . ' days'))->format('Y-m-d H:i:s');
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->setLogin(self::USER_LOGIN);
        $user->setUserGuid(ZfExtended_Utils::guid(true));
        $user->setOpenIdIssuer(bin2hex(random_bytes(8)));
        $user->save();

        $userLoginLog = ZfExtended_Factory::get(ZfExtended_Models_LoginLog::class);
        $userLoginLog->setLogin(self::USER_LOGIN);
        $userLoginLog->setCreated($date);
        $userLoginLog->save();

        $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $pmUser->setLogin(self::PM_USER_LOGIN);
        $pmUser->setUserGuid(ZfExtended_Utils::guid(true));
        $pmUser->setRoles([ACL_ROLE_PM]);
        $pmUser->setOpenIdIssuer(bin2hex(random_bytes(8)));
        $pmUser->save();

        $pmUserLoginLog = ZfExtended_Factory::get(ZfExtended_Models_LoginLog::class);
        $pmUserLoginLog->setLogin(self::PM_USER_LOGIN);
        $pmUserLoginLog->setCreated($date);
        $pmUserLoginLog->save();
    }

    public function tearDown(): void
    {
        static::setConfig($this->config);
        $whereLoginIn = sprintf(
            "login  IN ('%s', '%s', '%s')",
            self::USER_LOGIN,
            self::PM_USER_LOGIN,
            self::FALLBACK_PM_USER_LOGIN
        );
        ZfExtended_Factory::get(ZfExtended_Models_User::class)->db->delete($whereLoginIn);
        ZfExtended_Factory::get(ZfExtended_Models_LoginLog::class)->db->delete($whereLoginIn);
    }

    public function testOnZeroDaysNoUsersAreDeleted(): void
    {
        $this->setUpConfig(0, null);
        $action = new DeleteOpenidUsersAction();
        $action->deleteOpenidUsers(false);

        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->loadByLogin(self::USER_LOGIN);

            static::assertNotEmpty($user->getId(), 'User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('User was deleted');
        }

        try {
            $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pmUser->loadByLogin(self::PM_USER_LOGIN);

            static::assertNotEmpty($pmUser->getId(), 'PM User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('PM User was deleted');
        }
    }

    public function testOnLessDaysNoUsersAreDeleted(): void
    {
        $this->setUpConfig(self::LAST_LOGIN_INTERVAL_IN_DB + 1, null);
        $action = new DeleteOpenidUsersAction();
        $action->deleteOpenidUsers(false);

        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->loadByLogin(self::USER_LOGIN);

            static::assertNotEmpty($user->getId(), 'User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('User was deleted');
        }

        try {
            $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pmUser->loadByLogin(self::PM_USER_LOGIN);

            static::assertNotEmpty($pmUser->getId(), 'PM User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('PM User was deleted');
        }
    }

    public function testOnExactDaysNoUsersAreDeleted(): void
    {
        $this->setUpConfig(self::LAST_LOGIN_INTERVAL_IN_DB, null);
        $action = new DeleteOpenidUsersAction();
        $action->deleteOpenidUsers(false);

        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->loadByLogin(self::USER_LOGIN);

            static::assertNotEmpty($user->getId(), 'User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('User was deleted');
        }

        try {
            $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pmUser->loadByLogin(self::PM_USER_LOGIN);

            static::assertNotEmpty($pmUser->getId(), 'PM User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('PM User was deleted');
        }
    }

    public function testOnMoreDaysAndNoFallbackPmOnlyUserIsDeleted(): void
    {
        $this->setUpConfig(self::LAST_LOGIN_INTERVAL_IN_DB - 1, null);
        $action = new DeleteOpenidUsersAction();
        $action->deleteOpenidUsers(false);

        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->loadByLogin(self::USER_LOGIN);

            static::assertEmpty($user->getId(), 'User was not deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::assertTrue(true);
        }

        try {
            $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pmUser->loadByLogin(self::PM_USER_LOGIN);

            static::assertNotEmpty($pmUser->getId(), 'PM User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('PM User was deleted');
        }
    }

    public function testOnMoreDaysAndFallbackPmProvidedAllUsersAreDeleted(): void
    {
        $fallbackPmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $fallbackPmUser->setLogin(self::FALLBACK_PM_USER_LOGIN);
        $fallbackPmUser->setUserGuid(ZfExtended_Utils::guid(true));
        $fallbackPmUser->save();

        $this->setUpConfig(self::LAST_LOGIN_INTERVAL_IN_DB - 1, (int) $fallbackPmUser->getId());
        $action = new DeleteOpenidUsersAction();
        $action->deleteOpenidUsers(false);

        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->loadByLogin(self::USER_LOGIN);

            static::assertEmpty($user->getId(), 'User was not deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::assertTrue(true);
        }

        try {
            $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pmUser->loadByLogin(self::PM_USER_LOGIN);

            static::assertEmpty($pmUser->getId(), 'PM User was not deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::assertTrue(true);
        }
    }

    public function testOnMoreDaysForFallbackPm(): void
    {
        $fallbackPmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $fallbackPmUser->loadByLogin(self::PM_USER_LOGIN);

        $this->setUpConfig(self::LAST_LOGIN_INTERVAL_IN_DB - 1, (int) $fallbackPmUser->getId());
        $action = new DeleteOpenidUsersAction();
        $action->deleteOpenidUsers(false);

        try {
            $pmUser = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $pmUser->loadByLogin(self::PM_USER_LOGIN);

            static::assertNotEmpty($pmUser->getId(), 'Fallback PM User was deleted');
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            static::fail('Fallback PM User was deleted');
        }
    }

    public function setUpConfig(?int $removeUsersAfterDays, ?int $fallbackPm): void
    {
        $config = new stdClass();
        $config->runtimeOptions = new stdClass();
        $config->runtimeOptions->openid = new stdClass();
        $config->runtimeOptions->openid->removeUsersAfterDays = $removeUsersAfterDays;
        $config->runtimeOptions->openid->fallbackPm = $fallbackPm;

        static::setConfig($config);
    }
}
