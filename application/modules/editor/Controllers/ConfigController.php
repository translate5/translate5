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
        $taskGuid = $this->getParam('taskGuid');
        if(!empty($taskGuid)){
            $this->view->rows = $this->entity->mergeTaskValues($taskGuid);
            return;
        }
        
        $customerId = $this->getParam('customerId');
        if(!empty($customerId)){
            $this->view->rows = $this->entity->mergeCustomerValues($customerId);
            return;
        }
        
        $userGuid = $this->getParam('userGuid');
        if(!empty($customerId)){
            $this->view->rows = $this->entity->mergeUserValues($userGuid);
            return;
        }
        //TODO: when i send task guid i know it is about the task specific config. Then set the level as task, and this should load the requrired task data
        // same for customer
        //$domainFilter = $this->getParam('domainFilter');
        
        
        //load all zf configuration values merged with the user config and .ini values
        $this->view->rows = $this->entity->loadAllMerged(null);
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
        $this->decodePutData();
        
        if(!property_exists($this->data, 'value')) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1025' => 'Field "value" must be provided.'
            ]);
            throw new ZfExtended_UnprocessableEntity('E1025');
        }
        
        $this->entity->loadByName($this->data->name);

        $level = null;
        $value = (string) $this->data->value;

        $userGuid = $this->data->userGuid ?? null;
        if(!empty($userGuid)){
            $level = $this->entity::CONFIG_LEVEL_USER;
        }
        
        $taskGuid = $this->data->taskGuid ?? null;
        if(!empty($taskGuid)){
            $level = $this->entity::CONFIG_LEVEL_TASK;
        }
        
        $customerId = $this->data->customerId ?? null;;
        if(!empty($customerId)){
            $level = $this->entity::CONFIG_LEVEL_CUSTOMER;
        }
        $row = [];
        switch ($level) {
            case $this->entity::CONFIG_LEVEL_USER:
                $userConfig=ZfExtended_Factory::get('editor_Models_UserConfig');
                /* @var $userConfig editor_Models_UserConfig */
                $userConfig->updateInsertConfig($userGuid,$this->data->name,$value);
                //this value may not be saved! It is just for setting the return value to the gui.
                $this->entity->setValue($value);

                $row['userGuid'] = $userGuid;
                
                $this->log->debug('E0000', 'Updated user GUI state "{name}" to "{value}"', [
                    'name' => $this->data->name,
                    'value' => $value,
                ]);
                break;
            case $this->entity::CONFIG_LEVEL_TASK:
                $taskConfig=ZfExtended_Factory::get('editor_Models_TaskConfig');
                /* @var $taskConfig editor_Models_TaskConfig */
                $taskConfig->updateInsertConfig($taskGuid,$this->data->name,$value);
                //this value may not be saved! It is just for setting the return value to the gui.
                $this->entity->setValue($value);
                
                $row['taskGuid'] = $taskGuid;
                
                $this->log->debug('E0000', 'Updated task config value "{name}" to "{value}"', [
                    'name' => $this->data->name,
                    'value' => $value,
                ]);
                break;
            case $this->entity::CONFIG_LEVEL_CUSTOMER:
                $customerConfig=ZfExtended_Factory::get('editor_Models_CustomerConfig');
                /* @var $customerConfig editor_Models_CustomerConfig */
                $customerConfig->updateInsertConfig($customerId,$this->data->name,$value);
                //this value may not be saved! It is just for setting the return value to the gui.
                $this->entity->setValue($value);
                
                $row['customerId'] = $customerId;
                
                $this->log->debug('E0000', 'Updated customer config value "{name}" to "{value}"', [
                    'name' => $this->data->name,
                    'value' => $value,
                ]);
                break;
            default:
                break;
        }

        //merge the current entity with the custom config data ($row)
        $this->view->rows = array_merge($row, $this->entity->toArray());
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
    
    /**
     * Check if the current user is allowed to update config with $level
     * @param int $level
     * @throws editor_Models_ConfigException
     */
    protected function isUpdateAllowed(int $level) {
        $userSession = new Zend_Session_Namespace('user');
        
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->load($userSession->data->id);
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if($acl->isInAllowedRoles($user->getRoles(),'stateconfig',$this->entity->getConfigLevelLabel($level))){
            throw new editor_Models_ConfigException('E1292', [
                'level' => $level
            ]);
        }
    }
}
