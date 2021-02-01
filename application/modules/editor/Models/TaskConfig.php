<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/
/***
* @method void setId() setId(int $id)
* @method int getId() getId()
* @method void setTaskGuid() setTaskGuid(guid $taskGuid)
* @method guid getTaskGuid() getTaskGuid()
* @method void setName() setName(string $name)
* @method string getName() getName()
* @method void setValue() setValue(string $value)
* @method string getValue() getValue()
*/

class editor_Models_TaskConfig extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = "editor_Models_Db_TaskConfig";
    protected $validatorInstanceClass = "editor_Models_Validator_TaskConfig";
    
    
    /***
     * Internal cache task config cache
     * @var Zend_Config
     */
    protected static $taskCustomerConfig=[];
    
    /***
     *  Return all task specific configs for the given task guid.
     *  For all configs for which there is not task specific overwrite, the overwrite for the task client will be used as a value.
     *  For all configs for which there is no task customer specific overwrite, the instance-level config value will be used
     * @param string $taskGuid
     * @throws editor_Models_ConfigException
     * @return Zend_Config
     */
    public function getTaskConfig(string $taskGuid){
        if(empty($taskGuid)){
            throw new editor_Models_ConfigException('E1297');
        }
        if(isset(self::$taskCustomerConfig[$taskGuid])){
            return self::$taskCustomerConfig[$taskGuid];
        }
        $configModel = ZfExtended_Factory::get('editor_Models_Config');
        /* @var $configModel editor_Models_Config */
        
        //fetch all config from DB
        $dbConfig = ZfExtended_Factory::get('ZfExtended_Models_Config');
        /* @var $dbConfig ZfExtended_Models_Config */
        $base = $dbConfig->loadAll();
        //for merge set config name as array key
        $base = $configModel->nameAsKey($base);
        
        //merge task overwrites with task customer overwrites
        $result = $configModel->mergeTaskValues($taskGuid,$base);
        
        $configOperator = ZfExtended_Factory::get('ZfExtended_Resource_DbConfig');
        /* @var $configOperator ZfExtended_Resource_DbConfig */
        $configOperator->initDbOptionsTree($result);
        $taskConfig = new Zend_Config($configOperator->getDbOptionTree());
        $taskConfig->setReadOnly();
        //cache the config for this request
        self::$taskCustomerConfig[$taskGuid] = $taskConfig;
        return self::$taskCustomerConfig[$taskGuid];
    }
    
    /***
     * Load all configs for given taskGuid
     * @param string $taskGuid
     * @return array
     */
    public function loadByTaskGuid(string $taskGuid) {
        $s = $this->db->select()
        ->where('taskGuid = ?',$taskGuid);
        return $this->db->getAdapter()->fetchAll($s);
    }
    
    /***
     * Update or insert new config for given task
     *
     * @param string $taskGuid
     * @param string $name
     * @param mixed $value
     * @return number
     */
    public function updateInsertConfig(string $taskGuid,string $name, $value) {
        if(is_array($value)){
            $value = implode('","', $value);
            $value = '["'.$value.'"]';
        }
        $sql="INSERT INTO LEK_task_config(taskGuid,name,value) ".
            " VALUES (?,?,?) ".
            " ON DUPLICATE KEY UPDATE value = ? ";
        try {
            return $this->db->getAdapter()->query($sql,[$taskGuid,$name,$value,$value]);
        }
        catch (Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e);
        }
    }
    
    /***
     * Copy all task specific config from $odlTaskGuid to $newTaskGuid
     * @param string $sourceTaskGuid
     * @param string $targetTaskGuid
     */
    public function cloneTaskConfig(string $odlTaskGuid, string $newTaskGuid) {
        $adapter = $this->db->getAdapter();
        $sql = "INSERT INTO LEK_task_config (taskGuid, name, value)
        SELECT ".$adapter->quote($newTaskGuid).", name, value
        FROM  LEK_task_config WHERE taskGuid = ".$adapter->quote($odlTaskGuid)."; ";
        $adapter->query($sql);
    }
}
