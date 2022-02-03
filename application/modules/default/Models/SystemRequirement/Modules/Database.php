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

/**#@+
 * @author Marc Mittag
 * @package portal
 * @version 2.0
 *
 */
/**
 */
class Models_SystemRequirement_Modules_Database extends ZfExtended_Models_SystemRequirement_Modules_Abstract {
    /**
     * {@inheritDoc}
     * @see ZfExtended_Models_SystemRequirement_Modules_Abstract::validate()
     */
    function validate(): ZfExtended_Models_SystemRequirement_Result {
        $this->result->id = 'database';
        $this->result->name = 'Database';
        $this->result->badSummary = [
            '<error>Fix the errors and call the script again!</error>',
            'See https://confluence.translate5.net/display/CON/Server+environment+-+configure+from+scratch#Serverenvironmentconfigurefromscratch-mysqlconfigMySQLconfiguration for more information and solutions.'
        ];
        
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        
        $this->checkOpenUpdates();
        $this->checkCharset($db);
        $this->checkTimezones($db);
        $this->checkDbSettings($db);
        $this->checkSkipLock($db);
        $this->checkTableCharsets($db);
        $this->checkDbTriggerCreation($db);
        $this->checkFulltextIndexSettings($db);
        return $this->result;
    }
    
    /**
     * The php and the mysql timezone must be set to the same value, otherwise we will get problems, see TRANSLATE-2030
     * @param Zend_Db_Adapter_Abstract $db
     */
    protected function checkTimezones(Zend_Db_Adapter_Abstract $db) {
        $result = $db->query("SELECT TIME_FORMAT(TIMEDIFF(NOW(), utc_timestamp()), '%H:%i') gmtshift;");
        $res = $result->fetchObject();
        if(empty($res)) {
            return; //should not be
        }
        $mysqlZone = $res->gmtshift;
        if(strpos($mysqlZone, '-') !== 0) {
            $mysqlZone = '+'.$mysqlZone;
        }
        $phpZone = date('P');
        if($mysqlZone == $phpZone) {
            return;
        }
        $msg = 'Your DB timezone (GMT '.$mysqlZone.') and your PHP timezone (GMT '.$phpZone.') differ! Please ensure that PHP (apache and CLI) timezone is set correctly and the DBs timezone is the same!';
        $this->result->error[] = $msg;
    }
    
    protected function checkTableCharsets(Zend_Db_Adapter_Abstract $db) {
        $result = $db->query("select TABLE_NAME,TABLE_COLLATION from information_schema.TABLES where TABLE_SCHEMA = database() AND TABLE_COLLATION != 'utf8mb4_unicode_ci';");
        while($row = $result->fetchObject()) {
            $this->result->error[] = 'DB table '.$row->TABLE_NAME.' has collation "'.$row->TABLE_COLLATION.'" instead of "utf8mb4_unicode_ci"';
        }

        $result = $db->query('
        SELECT table_schema, table_name, column_name, character_set_name, collation_name
        FROM information_schema.columns
        WHERE character_set_name != "utf8mb4"
        AND table_schema = database()
        ORDER BY table_schema, table_name,ordinal_position');

        while($row = $result->fetchObject()) {
            $this->result->error[] = 'DB column '.$row->table_name.'.'.$row->column_name.' has charset "'.$row->character_set_name.'" instead of "utf8mb4" (collation is '.$row->collation_name.')';
        }
    }
    
    protected function checkSkipLock(Zend_Db_Adapter_Abstract $db) {
        $m = $e = null;
        try {
            $db->query('SELECT 1 FROM DUAL FOR UPDATE SKIP LOCKED');
            return;
        }
        catch(Zend_Db_Statement_Exception $e) {
            $m = $e->getMessage();
        }
        if(strpos($m, 'You have an error in your SQL syntax') !== false) {
            $this->result->error[] = "Your DB version is not supported anymore. Please update to newer version (MariaDB >= 10.6 or MySQL >= 5.7) supporting the JSON functions.";
            return;
        }

        //some other error occured
        throw $e;
    }
    
    protected function checkCharset(Zend_Db_Adapter_Abstract $db) {
        $result = $db->query("SELECT @@character_set_database charset, @@collation_database collation;");
        $res = $result->fetchObject();
        if(empty($res)) {
            return; //should not be
        }
        if($res->charset !== 'utf8mb4') {
            $this->result->error[] = 'Your DBs charset is '.$res->charset.' but should be utf8mb4';
        }
        if($res->collation !== 'utf8mb4_unicode_ci') {
            $this->result->error[] = 'Your DBs collation is '.$res->collation.' but should be utf8mb4_unicode_ci';
        }
    }
    
    protected function checkFulltextIndexSettings(Zend_Db_Adapter_Abstract $db) {
        $result = $db->query("SELECT @@innodb_ft_min_token_size length, @@innodb_ft_enable_stopword stopword;");

        $res = $result->fetchObject();
        if(empty($res)) {
            return; //should not be
        }
        $error = false;
        if($res->length > 1) {
            $this->result->warning[] = 'For proper TermPortal usage: The minimum word length of the fulltext search is '.$res->length.' but must be 1. Set innodb_ft_min_token_size = 1 in DB configuration and restart database.';
            $error = true;
        }
        if($res->stopword > 0) {
            $this->result->warning[] = 'For proper TermPortal usage: The stopword list of the fulltext search must be disabled, but it is enabled. Set innodb_ft_enable_stopword = 0 in DB configuration and restart database.';
            $error = true;
        }
        if($error) {
            $this->result->warning[] = 'After changing the fulltext relevant configuration the fulltext index must be rebuild, see the below linked documentation for further information.';
        }
    }

    protected function checkDbSettings(Zend_Db_Adapter_Abstract $db) {
        // WARNING: if the tested variables are empty in DB, the test is positive!
        $notAllowedSqlModes = array(
            'ONLY_FULL_GROUP_BY',
            'NO_ZERO_IN_DATE',
            'NO_ZERO_DATE',
            'STRICT_TRANS_TABLES',
        );
        $result = $db->query("SHOW VARIABLES WHERE Variable_name = 'sql_mode'");
        $res = $result->fetchObject();
        if(empty($res)) {
            return; //should not be
        }
        $modes = explode(',', $res->Value);
        $found = array_intersect($notAllowedSqlModes, $modes);
        if(empty($found)) {
            return;
        }
        $msg = 'Your DB configuration SQL_MODE uses the following modes, which have to be deactivated before using translate5: ';
        $msg .= join(',', $found);
        $this->result->error[] = $msg;
    }
    
    protected function checkDbTriggerCreation(Zend_Db_Adapter_Abstract $db) {
        $m = $e = null;
        try {
            //since SUPER checking is done before trigger existence check, we can just try to delete a non existent trigger.
            $db->query("DROP TRIGGER updater_super_check");
        }
        catch(Zend_Db_Statement_Exception $e) {
            $m = $e->getMessage();
        }
        if(strpos($m, 'Trigger does not exist, query was: DROP TRIGGER updater_super_check')!== false) {
            //trigger does really not exist, so all is ok
            return;
        }
        
        //SQLSTATE[HY000]: General error: 1419 You do not have the SUPER privilege and binary logging is enabled (you *might* want to use the less safe log_bin_trust_function_creators variable), query was: DROP TRIGGER updater_super_check
        if(strpos($m, 'You do not have the SUPER privilege and binary logging is enabled')!== false) {
            $this->result->error[] = 'Your DB user does not have the SUPER privilege and binary logging is enabled!';
            return;
        }
        
        //some other error occured
        throw $e;
    }

    protected function checkOpenUpdates()
    {
        $dbUpdater = ZfExtended_Factory::get('ZfExtended_Models_Installer_DbUpdater');
        /* @var $dbUpdater ZfExtended_Models_Installer_DbUpdater */
        $dbUpdater->calculateChanges();
        $newCount = count($dbUpdater->getNewFiles());
        $modCount = count($dbUpdater->getModifiedFiles());
        if($newCount > 0) {
            $this->result->warning[] = 'Database is not up to date: there are '.$newCount.' DB files to be imported! call "translate5.[sh|bat] database:update"';
        }
        if($modCount > 0) {
            $this->result->warning[] = 'Database is not up to date: there are '.$modCount.' modified DB files! call "translate5.[sh|bat] database:update"';
        }
    }
}