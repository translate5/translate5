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
        if (version_compare(PHP_VERSION, '5.6.0', '<') || version_compare(PHP_VERSION, '7.0.0', '>=')) {
            $this->errorsEnvironment[] = 'You are using PHP in version '.PHP_VERSION.', translate5 needs a PHP version >= 5.6.0 and < 7.0.0';
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
        if(!$win && stripos($locale, 'utf-8') === false) {
            $this->errorsEnvironment[] = 'Your system locale is not UTF-8 capable, it is set to: LC_CTYPE='.$locale;
            $this->errorsEnvironment[] = 'Please use a UTF-8 based locale to avoid problems with special characters in filenames.';
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
    
    public function checkUsers() {
        session_start();
        $config = Zend_Registry::get('config');
        $db = Zend_Db::factory($config->resources->db);
        $result = $db->query('SELECT count(*) active FROM session where modified + lifetime > unix_timestamp()');
        $activeSessions = $result->fetchObject()->active;
        
        $result = $db->query('SELECT count(*) active FROM session where modified + 3600 > unix_timestamp()');
        $lastHourSessions = $result->fetchObject()->active;
        
        echo "Session Summary:\n";
        echo "Active Sessions:               ".$activeSessions."\n";
        echo "Active Sessions (last hour):   ".$lastHourSessions."\n";
        
        //$result = $db->query('SELECT session_data FROM session where modified + lifetime > unix_timestamp()');
        $result = $db->query('SELECT * FROM session where modified + 3600 > unix_timestamp()');
        
        echo "Session Users (last hour):\n";
        while($row = $result->fetchObject()) {
            session_decode($row->session_data);
            if(!empty($_SESSION['user']) && !empty($_SESSION['user']['data']) && !empty($_SESSION['user']['data']->login)){
                $data = $_SESSION['user']['data'];
                settype($data->firstName, 'string');
                settype($data->surName, 'string');
                settype($data->login, 'string');
                settype($data->email, 'string');
                $username = $data->firstName.' '.$data->surName.' ('.$data->login.': '.$data->email.')';
                echo "                               ".$username."\n";
            }
            else {
                echo "                               No User\n";
            }
        }
        session_destroy();
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