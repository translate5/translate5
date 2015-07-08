<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
class Models_Installer_Standalone {
    const INSTALL_INI = '/application/config/installation.ini';
    const DB_INIT = '/dbinit/DbInit.sql';
    const ZEND_LIB = '/library/zend';
    const MYSQL_BIN = '/usr/bin/mysql';
    
    /**
     * @var string
     */
    protected $currentWorkingDir;
    
    /**
     * @var array
     */
    protected $dbCredentials = array(
            'host' => 'localhost',
            'username' => 'root',
            'executable' => '',
            'password' => '',
            'database' => 'translate5',
    );
    
    protected $hostname = 'localhost';
    
    /**
     * Options: 
     * mysql_bin => path to mysql binary
     * @param array $options
     */
    public static function mainLinux(array $options = null) {
        $saInstaller = new self(getcwd());
        $saInstaller->processDependencies();
        $saInstaller->addZendToIncludePath();
        $saInstaller->installation($options);//checks internally if steps are already done
        $saInstaller->initApplication();
        $saInstaller->postInstallation();
        $saInstaller->updateDb();
    }
    
    /**
     * @param string $currentWorkingDir
     */
    public function __construct($currentWorkingDir) {
        $this->currentWorkingDir = $currentWorkingDir;
        //requiering the following hardcoded since, autoloader must be downloaded with Zend Package
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Downloader.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Dependencies.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/DbUpdater.php';
    }
    
    public function processDependencies() {
        $this->logSection('Checking server for updates and packages:');
        $downloader = new ZfExtended_Models_Installer_Downloader($this->currentWorkingDir);
        $dependencies = $this->currentWorkingDir.'/application/config/dependencies.json';
        $installedDeps = $this->currentWorkingDir.'/application/config/dependencies-installed.json';
        $downloader->initDependencies($dependencies, $installedDeps);
        $downloader->pull(true);
    }
    
    public function installation(array $options = null) {
        //assume installation success if installation.ini exists!
        if(file_exists($this->currentWorkingDir.self::INSTALL_INI)){
            return;
        }
        $this->logSection('Translate5 Installation');
        
        if(is_array($options) && isset($options['mysql_bin']) && $options['mysql_bin'] != self::MYSQL_BIN) {
            $this->dbCredentials['executable'] = $options['mysql_bin'];
        }
        $this->promptDbCredentials();
        $this->initDb();
        $this->createInstallationIni();
        $this->promptHostname();
    }
    
    /**
     * This are installation step which must be called after initApplication
     */
    protected function postInstallation() {
        if(!empty($this->hostname)) {
            $config = Zend_Registry::get('config');
            $db = Zend_Db::factory($config->resources->db);
            $db->query("update Zf_configuration set value = ? where name = 'runtimeOptions.server.name'", $this->hostname);
        }
    }
    
    /**
     * Adds the downloaded Zend Lib to the include path
     */
    protected function addZendToIncludePath() {
        $zendDir = $this->currentWorkingDir.self::ZEND_LIB;
        if(!is_dir($zendDir)) {
            $this->log("Could not find Zend library ".$zendDir);
            exit;
        }
        $path = get_include_path();
        set_include_path($path.PATH_SEPARATOR.$this->currentWorkingDir.self::ZEND_LIB);
    }
    
    /**
     * prompting the user for the DB credentials
     */
    protected function promptDbCredentials() {
        $this->log('Please enter the MySQL database settings, the database must already exist.');
        $this->log('Default character set must be utf8. This can be done for example with the following command: ');
        $this->log('  CREATE DATABASE IF NOT EXISTS `translate5` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;'."\n");
        
        foreach($this->dbCredentials as $key => $default) {
            //executable is determined by the surrounding bash script
            if($key == 'executable') {
                 continue;
            }
            $prompt = 'Please enter the DB '.$key;
            if(!empty($default)) {
                $prompt .= ' (default: '.$default.')';
            }
            $prompt .= ': ';
            $value = readline($prompt);
            $this->dbCredentials[$key] = empty($value) ? $default : $value;
        }
    }
    
    /**
     * prompt the user for the hostname, since this config is needed in the DbConfig
     */
    protected function promptHostname() {
        $prompt = "\nPlease enter the hostname of the virtual host which will serve Translate5";
        $prompt .= ' (default: localhost): ';
        $value = readline($prompt);
        $this->hostname = empty($value) ? 'localhost' : $value;
    }
    
    /**
     * Applies the DbInit.sql
     * @return boolean
     */
    protected function initDb() {
        $this->log("\nCreating the database base layout...");
        $dbInit = $this->currentWorkingDir.'/'.self::DB_INIT;
        $exec = empty($this->dbCredentials['executable']) ? self::MYSQL_BIN : $this->dbCredentials['executable'];
        
        $db = new stdClass();
        $db->host = $this->dbCredentials['host'];
        $db->username = $this->dbCredentials['username'];
        $db->password = $this->dbCredentials['password'];
        $db->dbname = $this->dbCredentials['database'];
        
        $cmd = ZfExtended_Models_Installer_DbUpdater::makeDbCommand($exec, $db);
        $call = sprintf($cmd, escapeshellarg($dbInit));
        exec($call, $output, $result);
        if($result > 0) {
            $this->log('Error on Importing '.self::DB_INIT.' file. Called command: '.$call.".\n".'Result of Command: '.print_r($output,1));
            return false;
        }
        $this->log('Translate5 tables created.');
        return true;
    }
    
    /**
     * Creates the installation.ini
     * @return boolean
     */
    protected function createInstallationIni() {
        $content = array();
        $content[] = '[application]';
        $content[] = 'resources.db.params.host = "'.$this->dbCredentials['host'].'"';
        $content[] = 'resources.db.params.username = "'.$this->dbCredentials['username'].'"';
        $content[] = 'resources.db.params.password = "'.$this->dbCredentials['password'].'"';
        $content[] = 'resources.db.params.dbname = "'.$this->dbCredentials['database'].'"';
        if(!empty($this->dbCredentials['executable'])) {
            $content[] = 'resources.db.params.executable = "'.$this->dbCredentials['executable'].'"';
        }
        $content[] = '';
        $content[] = 'resources.mail.defaultFrom.email = support@translate5.net';
        
        $bytes = file_put_contents($this->currentWorkingDir.self::INSTALL_INI, join("\n",$content));
        if($bytes > 0) {
            $this->log("\nDB Config successfully stored in .".self::INSTALL_INI."!\n");
        } else {
            $this->log("\nDB Config could NOT be stored in .".self::INSTALL_INI."!\n");
        }
        return ($bytes > 0);
    }
    
    /**
     * Applies all DB alter statement files to the DB
     */
    protected function updateDb() {
        $this->logSection('Updating Translate5 database scheme');
        
        $dbupdater = ZfExtended_Factory::get('ZfExtended_Models_Installer_DbUpdater');
        /* @var $dbupdater ZfExtended_Models_Installer_DbUpdater */
        $stat = $dbupdater->importAll();
        
        $errors = $dbupdater->getErrors();
        if(!empty($errors)) {
            $this->log("DB Update not OK\nErrors: \n".print_r($errors,1));
            return;
        }
        
        $this->log("DB Update OK\n  New statement files: ".$stat['new']."\n  Modified statement files: ".$stat['modified']."\n");
    }
    
    /**
     * generates a Zend Application like environment with all needed registry entries filled  
     */
    protected function initApplication() {
        $_SERVER['REQUEST_URI'] = '/database/forceimportall';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_HOST'] = 'localhost';
        define('APPLICATION_PATH', $this->currentWorkingDir.DIRECTORY_SEPARATOR.'application');
        define('APPLICATION_ENV', 'application');

        require_once 'Zend/Session.php';
        Zend_Session::$_unitTestEnabled = true;
        require_once 'library/ZfExtended/BaseIndex.php';
        $index = ZfExtended_BaseIndex::getInstance();
        $index->initApplication()->bootstrap();
        $index->addModuleOptions('default');
    }
    
    protected function log($msg) {
        echo $msg."\n";
    }
    
    protected function logSection($msg) {
        echo "\n".$msg."\n";
        echo str_pad('', strlen($msg), '=')."\n\n";
    }
}