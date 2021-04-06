<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Wrapper for ZfExtended_SessionController
 * else RestRoutes, ACL authentication, etc. will not work.
 */
class editor_SessionController extends ZfExtended_SessionController {
    const AUTH_HASH_DISABLED = 'disabled';
    const AUTH_HASH_DYNAMIC = 'dynamic';
    const AUTH_HASH_STATIC = 'static';
    
    public function indexAction() {
        if(!empty($_REQUEST['authhash'])) {
            $this->hashauth();
            return;
        }
        parent::indexAction();
    }
    
    public function impersonateAction(){
        $login = $this->getParam('login');
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
        $task->loadByTaskGuid($taskGuid);
        
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflow = $wfm->getByTask($task);
        
        $user = new Zend_Session_Namespace('user');
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        try {
            $tua->loadByParams($user->data->userGuid, $taskGuid,$workflow->getRoleOfStep($task->getWorkflowStepName()));
            $state = $workflow->getInitialUsageState($tua);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //without tua we can open it with edit, nothing will be confirmed then
            $state = $workflow::STATE_EDIT;
        }
        
        
        $params = ['id' => $task->getId(), 'data' => '{"userState":"'.$state.'","id":'.$task->getId().'}'];
        $this->forward('put', 'task', 'editor', $params);
        
        // the static event manager must be used!
        $events = Zend_EventManager_StaticEventManager::getInstance();
        $events->attach('editor_TaskController', 'afterPutAction', function(Zend_EventManager_Event $event) use ($mv){
            //clearing the view vars added in Task::PUT keeps the old content (the session id and token)
            $view = $event->getParam('view');
            $view->clearVars();
            //if a taskGuid was given we clean the MVs after accessing that task to prevent drop MV then create MV
            $mv->cleanUp();
        });
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
        
        ZfExtended_Models_LoginLog::addSuccess($user, "authhash");
        
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $workflow = $wfm->getByTask($task);
        
        $state = $workflow->getInitialUsageState($taskUserAssoc);
        
        //open task
        $params = ['id' => $task->getId(), 'data' => '{"userState":"'.$state.'","id":'.$task->getId().'}'];
        $this->forward('put', 'task', 'editor', $params);
        
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->cleanUp();
        
        $event = Zend_EventManager_StaticEventManager::getInstance();
        $event->attach('editor_TaskController', 'afterPutAction', function() {
            //the redirect must be triggered after the successful PUT OPEN of the task above
            $this->redirect(APPLICATION_RUNDIR.'/editor', ['code' => 302]);
        });
    }
}