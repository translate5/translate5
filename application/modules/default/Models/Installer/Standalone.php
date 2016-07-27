<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
class Models_Installer_Standalone {
    const INSTALL_INI = '/application/config/installation.ini';
    const CLIENT_SPECIFIC_INSTALL = '/client-specific-installation';
    const CLIENT_SPECIFIC = '/client-specific';
    const DB_INIT = '/dbinit/DbInit.sql';
    const ZEND_LIB = '/library/zend';
    const MYSQL_BIN = '/usr/bin/mysql';
    const OS_UNKNOWN = 1;
    const OS_WIN = 2;
    const OS_LINUX = 3;
    const OS_OSX = 4;
    const HOSTNAME_WIN = 'localhost';
    const HOSTNAME_LINUX = 'translate5.local';
    
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
    
    protected $hostname;
    
    protected $isInstallation = false;
    
    /**
     * Options: 
     * mysql_bin => path to mysql binary
     * @param array $options
     */
    public static function mainLinux(array $options = null) {
        $saInstaller = new self(getcwd());
        //TODO move options parameter to constructor instead of multiple usage
        $saInstaller->processDependencies($options);
        $saInstaller->addZendToIncludePath();
        $saInstaller->installation($options);//checks internally if steps are already done
        $saInstaller->cleanUpDeletedFiles(); //must be before initApplication!
        $saInstaller->initApplication();
        $saInstaller->postInstallation();
        $saInstaller->updateDb();
        $saInstaller->done();
    }
    
    /**
     * @param string $currentWorkingDir
     */
    public function __construct($currentWorkingDir) {
        $this->currentWorkingDir = $currentWorkingDir;
        //requiering the following hardcoded since, autoloader must be downloaded with Zend Package
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/License.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Downloader.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Dependencies.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/DbUpdater.php';
        $this->setHostname();
    }
    
    protected function setHostname() {
        $this->hostname = self::HOSTNAME_LINUX;
        if($this->getOS()===  self::OS_WIN){
            $this->hostname = self::HOSTNAME_WIN;
        }
    }
    
    public function processDependencies(array $options) {
        $this->logSection('Checking server for updates and packages:');
        $downloader = new ZfExtended_Models_Installer_Downloader($this->currentWorkingDir);
        
        if(isset($options['applicationZipOverride']) && file_exists($options['applicationZipOverride'])) {
            $zipOverride = $options['applicationZipOverride'];
        }
        else {
            $zipOverride = null;
        }
        
        $depsToAccept = $downloader->pullApplication($zipOverride);
        $this->acceptLicenses($depsToAccept);
        $downloader->pullDependencies(true);
    }
    
    public function installation(array $options = null) {
        //assume installation success if installation.ini exists!
        if(file_exists($this->currentWorkingDir.self::INSTALL_INI)){
            return;
        }
        $this->isInstallation = true;
        $this->logSection('Translate5 Installation');
        
        if(is_array($options) && isset($options['mysql_bin']) && $options['mysql_bin'] != self::MYSQL_BIN) {
            $this->dbCredentials['executable'] = $options['mysql_bin'];
        }
        while(! $this->promptDbCredentials());
        $this->initDb();
        $this->createInstallationIni();
        $this->promptHostname();
        $this->moveClientSpecific();
    }
    
    /**
     * Our ZIP based installation and update process can't deal with file deletions, 
     * so this has currently to be done manually in this method.
     * See this as a workaround and not as a final solution.
     */
    protected function cleanUpDeletedFiles() {
        $deleteList = dirname(__FILE__).'/filesToBeDeleted.txt';
        $toDeleteList = file($deleteList);
        foreach($toDeleteList as $toDelete) {
            //ignore comments
            if(strpos(trim($toDelete), '#') === 0){
                continue;
            }
            $file = new SplFileInfo($this->currentWorkingDir.trim($toDelete));
            if($file->isFile() && $file->isReadable()) {
                unlink($file);
            }
        }
    }
    
    /**
     * inits on new installations the client specific directories
     */
    protected function moveClientSpecific() {
        $source = $this->currentWorkingDir.self::CLIENT_SPECIFIC_INSTALL;
        $target = $this->currentWorkingDir.self::CLIENT_SPECIFIC;
        $targetPub = $this->currentWorkingDir.'/public'.self::CLIENT_SPECIFIC;
        //ignoring errors here, since already exisiting directories should not be moved
        if(file_exists($source.'/public') && !file_exists($targetPub)){
            rename($source.'/public', $targetPub);
        }
        if(file_exists($source) && !file_exists($target)){
            rename($source, $target);
        }
    }
    
    /**
     * This are installation step which must be called after initApplication
     */
    protected function postInstallation() {
        if(!$this->isInstallation){
            return;
        }
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
            $value = $this->prompt($prompt);
            $this->dbCredentials[$key] = empty($value) ? $default : $value;
        }
        
        echo PHP_EOL.PHP_EOL.'Confirm the given DB Credentials:'.PHP_EOL.PHP_EOL;
        foreach($this->dbCredentials as $key => $value) {
            //executable is determined by the surrounding bash script
            if($key == 'executable') {
                 continue;
            }
            echo $key.': '.$value.PHP_EOL;
        }
        return 'y' === strtolower($this->prompt(PHP_EOL.'Confirm the entered data with "y", press any other key to reenter DB credentials.'.PHP_EOL));
    }
    
    /**
     * prompts for all new licenses to be accepted
     * @param array $depsToAccept
     */
    protected function acceptLicenses(array $depsToAccept) {
        $first = true;
        foreach($depsToAccept as $dep) {
            $licenses = ZfExtended_Models_Installer_License::create($dep);
            foreach ($licenses as $license){
                if($first) {
                    $this->logSection('Third party library license agreements:', '-');
                    $first = false;
                }
                if(!$license->checkFileExistance()) {
                    echo 'WARNING: configured license file not found!'.PHP_EOL;
                }
                $read = '';
                do {
                    echo $license->getAgreementTitle().PHP_EOL.PHP_EOL;
                    $read = strtolower($this->prompt($license->getAgreementText().PHP_EOL.PHP_EOL.'  y or n: '));
                } while ($read != 'n' && $read != 'y');
                if($read == 'n') {
                    die(PHP_EOL.'You have to accept all third party licenses in order to install Translate5.'.PHP_EOL);
                }
                echo PHP_EOL.PHP_EOL;
            }
        }
    }
    
    /**
     * @return int
     */
    static public function getOS() {
        switch (true) {
            case stristr(PHP_OS, 'DAR'): return self::OS_OSX;
            case stristr(PHP_OS, 'WIN'): return self::OS_WIN;
            case stristr(PHP_OS, 'LINUX'): return self::OS_LINUX;
            default : return self::OS_UNKNOWN;
        }
    }
    
    protected function prompt($message = 'prompt: ', $hidden = false) {
        if (PHP_SAPI !== 'cli') {
            return false;
        }
        echo $message;
        $ret = 
            $hidden
            ? exec(
                PHP_OS === 'WINNT' || PHP_OS === 'WIN32'
                ? __DIR__ . '\prompt_win.bat'
                : 'read -s PW; echo $PW'
            )
            : rtrim(fgets(STDIN), PHP_EOL)
        ;
        if ($hidden) {
            echo PHP_EOL;
        }
        return $ret;
    }

    /**
     * prompt the user for the hostname, since this config is needed in the DbConfig
     */
    protected function promptHostname() {
        $prompt = "\nPlease enter the hostname of the virtual host which will serve Translate5";
        $prompt .= ' (default: '.$this->hostname.'): ';
        $value = $this->prompt($prompt);
        $this->hostname = empty($value) ? $this->hostname : $value;
    }
    
    /**
     * Applies the DbInit.sql
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
        
        $dbupdater = new ZfExtended_Models_Installer_DbUpdater();
        if(!$dbupdater->executeSqlFile($exec, $db, $dbInit, $output)) {
            $this->log('Error on Importing '.self::DB_INIT.' file, stopping installation. Called command: '.$exec.".\n".'Result of Command: '.print_r($output,1));
            exit;
        }
        $this->log('Translate5 tables created.');
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
        $content[] = 'runtimeOptions.sendMailDisabled = 1';
        
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
    
    protected function done() {
        $this->log("\nTranslate5 installation / update done.\n");
        if(!empty($this->hostname)) {
            $this->log("\nPlease visit http://".$this->hostname."/ to enjoy Translate5.\n");
            $this->log("For informations how to set up openTMSTermTagger or enable the application to send E-Mails, see http://confluence.translate5.net.\n\n");
        }
        $this->log('  In case of errors on installation / update please visit http://confluence.translate5.net');
        $this->log('  or post a message in translate5 user group, which is linked from http://www.translate5.net/index/usage/.');
    }
    
    protected function log($msg) {
        echo $msg."\n";
    }
    
    protected function logSection($msg, $lineChar = '=') {
        echo "\n".$msg."\n";
        echo str_pad('', strlen($msg), $lineChar)."\n\n";
    }
}
