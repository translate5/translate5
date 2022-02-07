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

/**
 */
class editor_ConfigController extends ZfExtended_RestController {
    
    protected $entityClass = 'editor_Models_Config';
    
    /**
     * @var editor_Models_Config
     */
    protected $entity;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {
        $this->view->rows = $this->loadConfig();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     * CRUD is currently not implemented, so BadMethod here
     */
    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->'.__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     * CRUD is currently not implemented, so BadMethod here
     */
    public function putAction() {
        ZfExtended_UnprocessableEntity::addCodes([
            'E1025' => 'Field "value" must be provided.',
            'E1363' => 'Configuration value invalid: {errorMsg}',
        ]);

        $this->decodePutData();

        if(!property_exists($this->data, 'value')) {
            throw new ZfExtended_UnprocessableEntity('E1025');
        }
        
        $this->entity->loadByName($this->data->name);

        
        $level = null;

        $typeManager = Zend_Registry::get('configTypeManager');
        /* @var $typeManager ZfExtended_DbConfig_Type_Manager */

        $type = $typeManager->getType($this->entity->getTypeClass());

        $error = null;
        $value = (string) $this->data->value; //the value is validated as string, and is saved as string to DB later
        if(!$type->validateValue($this->entity->getType(), $value, $error)) {
            throw new ZfExtended_UnprocessableEntity('E1363', ['errorMsg' => $error]);
        }
        if(!$type->isValidInDefaults($this->entity, $value)) {
            throw new ZfExtended_UnprocessableEntity('E1363', [
                'errorMsg' => 'The given value(s) is/are not allowed according to the available default values.'
            ]);
        }

        $userGuid = $this->data->userGuid ?? null;
        if(!empty($userGuid)){
            $this->checkUserGuid($userGuid);
            $level = $this->entity::CONFIG_LEVEL_USER;
        }
        
        $taskGuid = $this->data->taskGuid ?? null;
        if(!empty($taskGuid)){
            $this->checkTaskLevelAllowed($taskGuid);
            $level = $this->entity::CONFIG_LEVEL_TASK;
        }
        
        $customerId = $this->data->customerId ?? null;
        if(!empty($customerId)){
            $level = $this->entity::CONFIG_LEVEL_CUSTOMER;
        }
        
        if(empty($level)){
            $level = $this->entity::CONFIG_LEVEL_INSTANCE;
        }
        
        $this->checkConfigUpdateAllowed($level);
        $row = [];
        $logMessage = '';
        $logData = [];
        switch ($level) {
            case $this->entity::CONFIG_LEVEL_USER:
                $userConfig=ZfExtended_Factory::get('editor_Models_UserConfig');
                /* @var $userConfig editor_Models_UserConfig */
                $oldValue = $userConfig->getCurrentValue($userGuid, $this->data->name);
                $userConfig->updateInsertConfig($userGuid,$this->data->name,$value);
                //this value may not be saved! It is just for setting the return value to the gui.
                $this->entity->setValue($value);

                $row['userGuid'] = $userGuid;
                
                $logMessage='Updated user GUI state "{name}" to "{value}" . Old value was:"{oldValue}"';
                $logData = [
                    'userGuid' => $userGuid
                ];
                break;
            case $this->entity::CONFIG_LEVEL_TASK:
                
                $task = ZfExtended_Factory::get('editor_Models_Task');
                /* @var $task editor_Models_Task */
                $task->loadByTaskGuid($taskGuid);
                
                $projectTasks= [$taskGuid];
                if($task->isProject()){
                    //if the current change is for project, load all task project, and set
                    //this config for all project tasks
                    $projectTasks = $task->loadProjectTasks($task->getProjectId(),true);
                    $projectTasks = array_column($projectTasks, 'taskGuid');
                }
                foreach ($projectTasks as $projectTask){
                    $taskConfig = ZfExtended_Factory::get('editor_Models_TaskConfig');
                    /* @var $taskConfig editor_Models_TaskConfig */
                    if(!isset($oldValue)) {
                        $oldValue = $taskConfig->getCurrentValue($projectTask, $this->data->name);
                    }
                    $taskConfig->updateInsertConfig($projectTask,$this->data->name,$value);
                }
                
                //this value may not be saved! It is just for setting the return value to the gui.
                $this->entity->setValue($value);
                
                $row['taskGuid'] = $taskGuid;
                $logMessage='Updated task'.($task->isImporting() ? '-import' : '').' config value "{name}" to "{value}" . Old value was:"{oldValue}" ';
                $logData = [
                    'taskGuid' => $taskGuid
                ];
                break;
            case $this->entity::CONFIG_LEVEL_CUSTOMER:
                $customerConfig=ZfExtended_Factory::get('editor_Models_CustomerConfig');
                /* @var $customerConfig editor_Models_CustomerConfig */
                $oldValue = $customerConfig->getCurrentValue($customerId, $this->data->name);
                $customerConfig->updateInsertConfig($customerId,$this->data->name,$value);
                //this value may not be saved! It is just for setting the return value to the gui.
                $this->entity->setValue($value);
                
                $row['customerId'] = $customerId;
                
                $logMessage='Updated customer config value "{name}" to "{value}" . Old value was:"{oldValue}" ';
                $logData = [
                    'customerId' => $customerId
                ];
                
                break;
            case $this->entity::CONFIG_LEVEL_INSTANCE:
                //update system config
                $this->entity->setValue($value);
                $this->entity->save();
                $logMessage = 'Updated instance config value "{name}" to "{value}" . Old value was:"{oldValue}" ';
                break;
            default:
                break;
        }

        //log the change if there is one
        if(!empty($logMessage)){
            $this->log->info('E1324',$logMessage,
                array_merge([
                    'name' => $this->data->name,
                    'value' => $value,
                    'oldValue' => $oldValue ?? $this->entity->getOldValue('value')
                ],$logData));
        }

        //get a fresh copy of data
        $configRow = $this->entity->toArray();
        $configRow['typeClassGui'] = $type->getGuiViewCls(); //we can overwrite the typeClass here, since php class value is not usable in GUI

        //merge the current entity with the custom config data ($row)
        $this->view->rows = array_merge($row, $configRow);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     * CRUD is currently not implemented, so BadMethod here
     */
    public function getAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->'.__FUNCTION__);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::putAction()
     * CRUD is currently not implemented, so BadMethod here
     */
    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->'.__FUNCTION__);
    }
    
    /***
     * Validate if the current user config load is for the current user
     * @param string $userGuid
     * @throws editor_Models_ConfigException
     */
    protected function checkUserGuid(string $userGuid){
        $userSession = new Zend_Session_Namespace('user');
        if($userSession->data->userGuid !=$userGuid){
            throw new editor_Models_ConfigException('E1299');
        }
    }
    
    /**
     * Check if the current user is allowed to update config with $level
     * @param int $level
     * @throws editor_Models_ConfigException
     */
    protected function checkConfigUpdateAllowed(int $level) {
        $userSession = new Zend_Session_Namespace('user');
        
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->load($userSession->data->id);
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if(!$acl->isInAllowedRoles($user->getRoles(),$user::APPLICATION_CONFIG_LEVEL,$this->entity->getConfigLevelLabel($level))){
            throw new editor_Models_ConfigException('E1292', [
                'level' => $level
            ]);
        }
    }
    
    /***
     * Check if the current config is allowed to be saved for task config level
     * @param string $taskGuid
     * @throws editor_Models_ConfigException
     */
    protected function checkTaskLevelAllowed(string $taskGuid) {
        //if it is task import config, check the task state
        if($this->entity->getLevel() == $this->entity::CONFIG_LEVEL_TASKIMPORT){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            
            //the task is in import state -> all good
            if($task->getState() == $task::STATE_IMPORT){
                return;
            }
            
            //if the requested change is not for project type, throw exception
            if($task->getState() != $task::INITIAL_TASKTYPE_PROJECT){
                throw new editor_Models_ConfigException('E1296', [
                    'name' => $this->entity->getName()
                ]);
            }
            
            //if the current request is for project, all project task should be in state import, if not, throw exception
            $projectTasks = $task->loadProjectTasks($task->getId(),true);
            
            foreach ($projectTasks as $projectTask){
                if($projectTask['state']!=$task::STATE_IMPORT){
                    throw new editor_Models_ConfigException('E1296', [
                        'name' => $this->entity->getName()
                    ]);
                }
            }
        }
    }

    /***
     * Load config base on the requested params.
     * When taskGuid exist in the requested params -> load task specific config
     * When customerId exist in the requested params -> load customer specific config
     * When userGuid exist in the requested params -> load user specific config
     * When no param is provided -> load what the current user is allowed to load
     *
     * @return array
     */
    protected function loadConfig() {
        $taskGuid = $this->getParam('taskGuid');
        if(!empty($taskGuid)){
            return array_values($this->entity->mergeTaskValues($taskGuid));
        }
        $customerId = $this->getParam('customerId');
        if(!empty($customerId)){
            //ignore invalid customer ids
            if(!is_numeric($customerId)){
                return [];
            }
            return array_values($this->entity->mergeCustomerValues($customerId));
        }
        $userGuid = $this->getParam('userGuid');
        if(!empty($userGuid)){
            $this->checkUserGuid($userGuid);
            return array_values($this->entity->mergeUserValues($userGuid));
        }
        
        return array_values($this->entity->mergeInstanceValue());
    }
}
