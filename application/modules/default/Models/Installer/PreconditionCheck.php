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
     * Error Messages while checking environment are added here
     * @var array
     */
    protected $infosEnvironment = array();
    
    /**
     * Error Messages while checking DB are added here
     * @var array
     */
    protected $errorsDb = array();
    
    /**
     * checks the implemented environment checks, stops on error
     */
    public function checkEnvironment() {
        $this->checkGitInstallation();
        $this->checkPhpVersion();
        $this->checkLocale();
        $this->checkPhpExtensions();
        
        if(!empty($this->infosEnvironment)) {
            $msg = 'Some system requirements of translate5 are not optimal: ';
            echo("\n".$msg."\n\n\n  - ".join("\n  - ", $this->infosEnvironment)."\n");
        }
        if(!empty($this->errorsEnvironment)) {
            $msg = 'Some system requirements of translate5 are not met: ';
            $this->stop($msg."\n  - ".join("\n  - ", $this->errorsEnvironment)."\n");
        }
    }
    
    /**
     * checks the needed PHP version of translate5
     */
    protected function checkPhpVersion() {
        if (version_compare(PHP_VERSION, '7.3', '<')) {
            $this->errorsEnvironment[] = 'You are using PHP in version '.PHP_VERSION.', translate5 needs a PHP version >= 7.3.0';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $this->errorsEnvironment[] = 'Please update your xampp package manually or reinstall Translate5 with the latest windows installer from http://www.translate5.net';
                $this->errorsEnvironment[] = 'Warning: Reinstallation can lead to data loss! Please contact support@translate5.net when you need assistance in data conversion!';
            }
        }
    }
    
    /**
     * Ensure that the correct locale is set
     */
    protected function checkLocale() {
        $locale = setlocale(LC_CTYPE, 0);
        $win = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if(!$win && stripos($locale, 'utf-8') === false && stripos($locale, 'utf8') === false) {
            $this->errorsEnvironment[] = 'Your system wide used locale is not UTF-8 capable, it is set to: LC_CTYPE='.$locale;
            $this->errorsEnvironment[] = 'Please use a UTF-8 based locale like en_US.UTF-8 to avoid problems with special characters in filenames.';
        }
        if ($win) {
            $this->infosEnvironment[] = 'You are using WINDOWS as server environment. Please ensure that the configuration runtimeOptions.fileSystemEncoding is set correct.';
        }
    }
    
    /**
     * Checks the needed PHP extensions
     */
    protected function checkPhpExtensions() {
        $loaded = get_loaded_extensions();
        $needed = [
            'dom',
            'fileinfo',
            'gd',
            'iconv',
            'intl',
            'mbstring',
            'pdo_mysql',
            'zip',
            'curl'
        ];
        $missing = array_diff($needed, $loaded);
        if(empty($missing)) {
            return;
        }
        $this->errorsEnvironment[] = 'The following PHP extensions are not loaded or not installed, but are needed by translate5: '."\n    ".join(", ", $missing);
        
        if(extension_loaded('gd')) {
            $gdinfo = gd_info();
            if(!$gdinfo['FreeType Support']) {
                $this->errorsEnvironment[] = 'The PHP extension GD needs to be installed with freetype support!';
            }
        }
    }
    
    /**
     * Checks if this is a git installation
     */
    protected function checkGitInstallation() {
        if(file_exists('.git')) {
            $msg  = 'A .git file/directory does exist in the project root!'."\n";
            $msg .= '    Please use git to update your installation and call database import via GUI or use parameter --database.';
            $this->errorsEnvironment[] = $msg;
        }
    }
    
    /**
     * checks the implemented DB checks, stops on error
     */
    public function checkDb() {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        
        $this->checkCharset($db);
        $this->checkTimezones($db);
        $this->checkDbSettings($db);
        $this->checkDbTriggerCreation($db);
        
        if(!empty($this->errorsDb)) {
            $msg = 'Some Database requirements of translate5 are not met: ';
            $msg2 = 'See https://confluence.translate5.net/display/CON/Server+environment+-+configure+from+scratch#Serverenvironmentconfigurefromscratch-mysqlconfigMySQLconfiguration for more information and solutions.';
            $this->stop($msg."\n  - ".join("\n  - ", $this->errorsDb)."\n\n".$msg2."\n");
        }
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
        $this->errorsDb[] = $msg;
    }
    
    protected function checkCharset(Zend_Db_Adapter_Abstract $db) {
        $result = $db->query("SELECT @@character_set_database charset, @@collation_database collation;");
        $res = $result->fetchObject();
        if(empty($res)) {
            return; //should not be
        }
        if($res->charset !== 'utf8') {
            $this->errorsDb[] = 'Your DBs charset is '.$res->charset.' but should be utf8';
        }
        if($res->collation !== 'utf8_general_ci') {
            $this->errorsDb[] = 'Your DBs collation is '.$res->collation.' but should be utf8_general_ci';
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
    
    public function checkUsers() {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        $result = $db->query('SELECT count(*) active FROM session where modified + lifetime > unix_timestamp()');
        $activeSessions = $result->fetchObject()->active;
        
        $result = $db->query('SELECT count(*) active FROM session where modified + 3600 > unix_timestamp()');
        $lastHourSessions = $result->fetchObject()->active;
        
        echo "Session Summary:\n";
        echo "Active Sessions:               ".$activeSessions."\n";
        echo "Active Sessions (last hour):   ".$lastHourSessions."\n";
    }
    
    public function checkWorkers() {
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        $result = $db->query('SELECT count(*) cnt, state FROM Zf_worker group by state');
        echo "Workers:\n";
        while($row = $result->fetchObject()) {
            echo "        ".str_pad($row->state, 23).$row->cnt."\n";
        }
    }
}