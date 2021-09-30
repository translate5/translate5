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

class editor_Models_CustomerConfig extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = "editor_Models_Db_CustomerConfig";
    protected $validatorInstanceClass = "editor_Models_Validator_CustomerConfig";
    
    
    /***
     * Get the customer specific config for given customer id.
     * If there is no customer overwritte for the config, the instance level value will be used.
     * @param int $customerId
     * @throws editor_Models_ConfigException
     * @return Zend_Config
     */
    public function getCustomerConfig(int $customerId) {
        if(empty($customerId)){
            throw new editor_Models_ConfigException('E1298');
        }
        
        // Step 1: start with systemwide config as base
        $config = new Zend_Config(Zend_Registry::get('config')->toArray(), true);
        
        // Step 2: anything customer-specific?
        $model = ZfExtended_Factory::get('editor_Models_Config');
        /* @var $model editor_Models_Config */
        $result = $model->mergeCustomerValues($customerId);
        $configOperator = ZfExtended_Factory::get('ZfExtended_Resource_DbConfig');
        /* @var $configOperator ZfExtended_Resource_DbConfig */
        $configOperator->initDbOptionsTree($result);
        $config->merge(new Zend_Config($configOperator->getDbOptionTree(),true));
        
        return $config;
    }
    /***
     * Update or insert new config for given customerId
     *
     * @param string $customerId
     * @param string $name
     * @param string $value
     * @return number
     */
    public function updateInsertConfig(int $customerId,string $name,string $value) {
        $sql="INSERT INTO LEK_customer_config(customerId,name,value) ".
            " VALUES (?,?,?) ".
            " ON DUPLICATE KEY UPDATE value = ? ";
        return $this->db->getAdapter()->query($sql,[$customerId,$name,$value,$value]);
    }
    
    /**
     * returns a specific config value for a specific customer
     * @param string $userGuid
     * @param string $name
     * @return string|NULL
     */
    public function getCurrentValue(int $customerId, string $name): ?string {
        try {
            $s = $this->db->select()
            ->where('customerId = ?', $customerId)
            ->where('name = ?', $name);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            return null;
        }
        if (!$row) {
            return null;
        }
        return $row['value'];
    }

    /**
     * Get 'liveSearchMinChars' termportal config option
     * It's getting maximum value among values defined in Zf_configuration
     * and custom values defined for the customers, identified by $customerIds
     *
     * @param string $customerIds Comma-separated
     * @return int
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     */
    public function getLiveSearchMinChars($customerIds) {

        // Get default
        $default = Zend_Registry::get('config')->runtimeOptions->termportal->liveSearchMinChars;

        // Get maximum among clients custom values of this config param
        if ($customerIds)
            $customMax = $this->db->getAdapter()->query('
                SELECT MAX(`value`) 
                FROM `LEK_customer_config` 
                WHERE TRUE
                  AND `name` = "runtimeOptions.termportal.liveSearchMinChars" 
                  AND `customerId` IN (' . $customerIds . ') 
            ')->fetchColumn();

        // Get max and return
        return $customMax ? max($default, $customMax) : $default;
    }
}
