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

use MittagQI\Translate5\Plugins\IpAuthentication\AclResource;
use MittagQI\ZfExtended\Acl\ResourceManager as ACLResourceManager;
use editor_Plugins_IpAuthentication_Models_IpBaseUser as IpBaseUser;

/**
 * For certain roles where it makes sense it should be possible to authenticate at translate5 only by the fact,
 * that the user comes from a certain IP address. Currently, this makes sense for
 * the roles termCustomerSearch and InstantTranslate (the user must have no other roles).
 */
class editor_Plugins_IpAuthentication_Init extends ZfExtended_Plugin_Abstract {
    protected static string $description = 'Provides the possibility to authenticate InstantTranslate and TermSearch '
                                           .'roles only by IP address (must be configured).';
    protected static bool $enabledByDefault = true;
    protected static bool $activateForTests = true;
    
    public function init() {
        ACLResourceManager::registerResource(AclResource::class);
        $this->eventManager->attach(
            'ZfExtended_Resource_GarbageCollector',
            'cleanUp',
            [$this, 'onGarbageCollectorCleanUp']
        );
        $this->eventManager->attach(
            'LoginController',
            'beforeIndexAction',
            [$this, 'onLoginBeforeIndexAction']
        );
        $this->eventManager->attach(
            'Editor_InstanttranslateController',
            'afterIndexAction',
            [$this, 'onInstantTranslateTermPortalAfterIndexAction']
        );
        $this->eventManager->attach(
            'Editor_TermportalController',
            'afterIndexAction',
            [$this, 'onInstantTranslateTermPortalAfterIndexAction']
        );
    }
    

    public function onInstantTranslateTermPortalAfterIndexAction(Zend_EventManager_Event $event): void
    {
        $view = $event->getParam('view');
        //do not show the logout button if the user is ipbased
        $view->isIpBasedUser = str_starts_with(
            ZfExtended_Authentication::getInstance()->getLogin(),
            IpBaseUser::IP_BASED_USER_LOGIN_PREFIX
        );
    }

    /**
     * On login before action check if the current client request comes from configured ipbased ip address.
     * Create or load ip based user, and set the user session.
     * The LoginController will handle the redirect
     * @throws Zend_Exception
     * @throws ReflectionException
     */
    public function onLoginBeforeIndexAction(): void
    {
        $logger = Zend_Registry::get('logger')->cloneMe('plugin.ipAuthentication');
        /* @var $logger ZfExtended_Logger */
        
        $user = ZfExtended_Factory::get('editor_Plugins_IpAuthentication_Models_IpBaseUser');
        /* @var $user editor_Plugins_IpAuthentication_Models_IpBaseUser */

        // if there is no ip configuration, do nothing
        if(empty($user->getConfiguredIps())){
            return;
        }

        if(!$user->isIpBasedRequest()){
            $logger->debug('E0000', 'Login denied from {ip}', [
                'ip' => $user->getIp(),
            ]);
            return;
        }
        
        $user->handleIpBasedUser();
        $logger->info('E0000', 'Logged in as {user} from {ip}', [
            'user' => $user->getLogin(),
            'ip' => $user->getIp(),
        ]);
        ZfExtended_Authentication::getInstance()->authenticateUser($user);
    }

    /**
     * On garbage collection clean up remove all temrporary users with expired session
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function onGarbageCollectorCleanUp(): void
    {
        //remove all ip based user wich can not be found in the session
        $users = ZfExtended_Factory::get('editor_Plugins_IpAuthentication_Models_IpBaseUser');
        /* @var $users editor_Plugins_IpAuthentication_Models_IpBaseUser */
        
        $users = $users->findAllExpired();
        
        if(empty($users)){
            return;
        }
        
        foreach ($users as $user){
            $model = ZfExtended_Factory::get('ZfExtended_Models_User');
            /* @var $model ZfExtended_Models_User */
            $model->init($user);
            $this->deleteTemporaryUser($model);
        }
    }

    /**
     * Delete the given user(temporary user) with all associations
     *
     *   1. load all tasks where the given user is pm
     *   2. remove all of those tasks and with this all user task associations
     *   3. remove the given user
     * @param ZfExtended_Models_User $user
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function deleteTemporaryUser(ZfExtended_Models_User $user): void
    {
        $taskModel=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $taskModel editor_Models_Task */
        $tasks=$taskModel->loadListByPmGuid($user->getUserGuid());
        
        if(!empty($tasks)){
            $taskGuids = array_column($tasks,'taskGuid');

            //remove all tasks and all files with the task, where the current user is pm
            foreach ($taskGuids as $taskGuid) {
                /* @var $task editor_Models_Task */
                $task = ZfExtended_Factory::get('editor_Models_Task');
                $task->loadByTaskGuid($taskGuid);

                $remover = ZfExtended_Factory::get('editor_Models_Task_Remover', [$task]);
                /* @var $remover editor_Models_Task_Remover */
                $remover->remove(true);
            }
        }
        $user->delete();
    }
}
