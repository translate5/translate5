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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
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
class Models_Installer_PreconditionCheck {
    /**
     * Error Messages while checking environment are added here
     * @var array
     */
    protected $errorsEnvironment = array();
    
    /**
     * Error Messages while checking DB are added here
     * @var array
     */
    protected $errorsDb = array();
    
    /**
     * checks the implemented environment checks, stops on error
     */
    public function checkEnvironment() {
        $this->checkPhpVersion();
        if(!empty($this->errorsEnvironment)) {
            $msg = 'Some system requirements of translate5 are not met: ';
            $this->stop($msg."\n  - ".join("\n  - ", $this->errorsEnvironment)."\n");
        }
    }
    
    /**
     * checks the needed PHP version of translate5
     */
    protected function checkPhpVersion() {
        if (version_compare(PHP_VERSION, '5.6.0', '<') || version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $this->errorsEnvironment[] = 'You are using PHP in version '.PHP_VERSION.', translate5 needs a PHP version >= 5.6.0 and < 7.0.0';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $this->errorsEnvironment[] = 'Please update your xampp package manually or reinstall Translate5 with the latest windows installer from http://www.translate5.net';
                $this->errorsEnvironment[] = 'Warning: Reinstallation can lead to data loss! Please contact support@translate5.net when you need assistance in data conversion!';
            }
        }
    }
    
    /**
     * checks the implemented DB checks, stops on error
     */
    public function checkDb() {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        
        $this->checkDbSettings($db);
        $this->checkDbTriggerCreation($db);
        
        if(!empty($this->errorsDb)) {
            $msg = 'Some Database requirements of translate5 are not met: ';
            $msg2 = 'See http://confluence.translate5.net/display/TIU/Server+environment+-+configure+from+scratch#mysqlconfig for more information and solutions.';
            $this->stop($msg."\n  - ".join("\n  - ", $this->errorsDb)."\n\n".$msg2."\n");
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
        $this->errorsDb[] = $msg;
    }
    
    protected function checkDbTriggerCreation(Zend_Db_Adapter_Abstract $db) {
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
                $this->errorsDb[] = 'Your DB user does not have the SUPER privilege and binary logging is enabled!';
                return;
            }
            
            //some other error occured
            throw $e;
    }
    
    protected function stop($msg) {
        //when reusing this class at other places (php runtime) change stop behaviour dynamically
        die("\n$msg\n\n");
    }
}