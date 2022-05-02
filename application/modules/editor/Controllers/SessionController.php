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

use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

/**
 * Wrapper for ZfExtended_SessionController
 * else RestRoutes, ACL authentication, etc. will not work.
 */
class editor_SessionController extends ZfExtended_SessionController {
    use TaskContextTrait;

    const AUTH_HASH_DISABLED = 'disabled';
    const AUTH_HASH_DYNAMIC = 'dynamic';
    const AUTH_HASH_STATIC = 'static';

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     */
    public function resyncOperation() {
        try {
            if($this->isTaskProvided()) {
                $this->initCurrentTask();
            }
        } catch (NoAccessException|ZfExtended_Models_Entity_NotFoundException) {
            //if task is not available just do nothing, empty current task is evaluated later
        }

    }

    public function indexAction() {
        if(!empty($_REQUEST['authhash'])) {
            $this->hashauth();
            return;
        }
        parent::indexAction();
    }
    
    /***
     * Will replace the current user session with the provided login.
     * The current user must have api rights to be able to call this action.
     */
    public function impersonateAction(){
        $login = $this->getParam('login');
        if(empty($login)){
            ZfExtended_UnprocessableEntity::addCodes([
                'E1342' => 'The parameter login containing the desired username is missing.'
            ], 'core.authentication.session');
            throw ZfExtended_UnprocessableEntity::createResponse('E1342',
                ['login' => 'Der Parameter login mit dem gewünschten Benutzernamen fehlt.']
            );
        }
        $config = Zend_Registry::get('config');
        $userModel = ZfExtended_Factory::get($config->authentication->userEntityClass);
        /* @var $userModel \ZfExtended_Models_User */
        $userModel->setUserSessionNamespaceWithoutPwCheck($login);
    }
    
    public function postAction() {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView */
        
        if(!parent::postAction()) {
            return;
        }
        settype($this->data->taskGuid, 'string');
        $taskGuid = $this->getParam('taskGuid', $this->data->taskGuid);
        
        //if there is no taskGuid provided, we don't have to load one
        if(empty($taskGuid)) {
            //after successfull login we clean up the MVs like in the normal login
            $mv->cleanUp();
            return;
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        if(is_numeric($taskGuid)) {
            //some one provided the ID instead
            $task->load($taskGuid);
        }
        else {
            $task->loadByTaskGuid($taskGuid);
        }
        $this->view->taskUrlPath = editor_Controllers_Plugins_LoadCurrentTask::makeUrlPath($task->getId());
    }
    
    /**
     * Authentication via a static hash
     * - usage of invalidLoginCounter makes no sense here
     * - singleUserRestriction is also not usable with this authentication
     * @return void|boolean
     */
    protected function hashauth() {
        if($this->isMaintenanceLoginLock()){
            throw new ZfExtended_Models_MaintenanceException('Maintenance scheduled in a few minutes!');
        }
        
        //FIXME ensure that only PMs may do that stuff! → spearte ACL
        
        $enabled = Zend_Registry::get('config')->runtimeOptions->hashAuthentication;
        if($enabled != self::AUTH_HASH_DYNAMIC && $enabled != self::AUTH_HASH_STATIC) {
            $this->log->cloneMe('core.authentication')->error('E1156', 'Tried to authenticate via hashAuthentication, but feature is disabled in the config!');
            throw new ZfExtended_NotAuthenticatedException();
        }
        
        settype($_REQUEST['authhash'], 'string');
        $taskUserAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $taskUserAssoc editor_Models_TaskUserAssoc */
        $taskUserAssoc->loadByHash($_REQUEST['authhash']);
        if($enabled == self::AUTH_HASH_DYNAMIC) {
            $taskUserAssoc->createStaticAuthHash();
            $taskUserAssoc->save();
        }
        
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($taskUserAssoc->getUserGuid());
        $this->setLocale(new Zend_Session_Namespace(), $user);
        $login = $user->getLogin();

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskUserAssoc->getTaskGuid());
        
        $user->setUserSessionNamespaceWithoutPwCheck($login);

        $userSession = new Zend_Session_Namespace('user');

        $session = ZfExtended_Factory::get('ZfExtended_Session');
        /* @var $session ZfExtended_Session */
        // remove the old session (if exist) for the auth-hash user
        $session->cleanForUser($userSession->data->id);

        ZfExtended_Models_LoginLog::addSuccess($user, "authhash");
        
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->cleanUp();
        $this->redirect(editor_Controllers_Plugins_LoadCurrentTask::makeUrlPath($task->getId()), ['code' => 307]);
    }
}