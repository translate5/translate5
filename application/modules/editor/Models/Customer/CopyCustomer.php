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
 * Copy configuration and user assignments from one customer to another.
 */
class editor_Models_Customer_CopyCustomer {

    /***
     * Copy user assignments + runtimeOptions.import.initialTaskUsageMode and runtimeOptions.workflow.initialWorkflow configs
     * from the $source customer to the $target customer
     * @param int $source
     * @param int $target
     * @return void
     * @throws Zend_Db_Table_Exception
     */
    public function copyUserAssoc(int $source, int $target){
        /** @var editor_Models_UserAssocDefault $model */
        $model = ZfExtended_Factory::get('editor_Models_UserAssocDefault');

        $adapter = $model->db->getAdapter();

        $sql = "DELETE FROM LEK_user_assoc_default where customerId = ?";
        // delete all user associations for the target customer
        $adapter->query($sql,[$target]);

        // remove the usage mode and the initial workflow config before the new values are copied
        $sql = "DELETE FROM LEK_customer_config where customerId = ? and name in('runtimeOptions.import.initialTaskUsageMode','runtimeOptions.workflow.initialWorkflow')";
        // delete all user associations for the target customer
        $adapter->query($sql,[$target]);

        // get all table columns
        $columns = $model->db->info($model->db::COLS);
        array_shift($columns); // remove the id from the column

        $sql = "INSERT IGNORE INTO LEK_user_assoc_default (".implode(',',$columns).") ";
        array_shift($columns); // remove the customerId from the column and replace it with ?
        array_unshift($columns,'?');
        $sql .= "SELECT ".implode(',',$columns)." FROM LEK_user_assoc_default where customerId = ?";

        $adapter = $model->db->getAdapter();
        // copy the default user assignments
        $adapter->query($sql,[$target,$source]);

        /** @var editor_Models_Customer_CustomerConfig $model */
        $model = ZfExtended_Factory::get('editor_Models_Customer_CustomerConfig');

        // in this action, additionally copy the usage mode and the initial workflow
        $sql = "INSERT IGNORE INTO LEK_customer_config (customerId, name, value) 
                SELECT  ?, name, value FROM LEK_customer_config where customerId = ? and name in('runtimeOptions.import.initialTaskUsageMode','runtimeOptions.workflow.initialWorkflow')";
        $adapter = $model->db->getAdapter();
        $adapter->query($sql,[$target,$source]);
    }

    /***
     * Copy all customer specific configs from $source customer to $target customer
     * @param int $source
     * @param int $target
     * @return void
     */
    public function copyConfig(int $source, int $target){
        /** @var editor_Models_Customer_CustomerConfig $model */
        $model = ZfExtended_Factory::get('editor_Models_Customer_CustomerConfig');

        $adapter = $model->db->getAdapter();
        $sql = "DELETE FROM LEK_customer_config where customerId = ?";
        // remove all existing configs before new values are copied
        $adapter->query($sql,[$target]);

        $sql = "INSERT IGNORE INTO LEK_customer_config (customerId, name, value) 
                SELECT  ?, name, value FROM LEK_customer_config where customerId = ?";
        $adapter->query($sql,[$target,$source]);
    }
}
