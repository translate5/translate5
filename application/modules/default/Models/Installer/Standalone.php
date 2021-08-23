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
    const JIRA_URL = 'https://jira.translate5.net/browse/';
    
    /**
     * Increase this value to force a restart of the updater while updating
     * @var integer
     */
    const INSTALLER_VERSION = 16;
    
    /**
     * @var string
     */
    protected $currentWorkingDir;
    
    /**
     * @var array
     */
    protected $options;
    
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
     * Stores the md5 hash of this file before downloading the update.
     * If the hash is changing after downloading the translate5 package this means
     * updates in the updater itself, so that it has to be restarted!
     * @var string
     */
    protected $installerHash;
    
    /**
     * contains the called file, the path to this file
     * @var string
     */
    protected $installerFile;
    
    /**
     * @var Models_Installer_PreconditionCheck
     */
    protected $preconditonChecker;
    
    /**
     * @var Symfony\Component\Console\Application
     */
    protected $cli;
    
    /**
     * Options:
     * mysql_bin => path to mysql binary
     * @param array $options
     */
    public static function mainLinux(array $options = null) {
        $saInstaller = new self(getcwd(), $options);
        $saInstaller->checkAndCallTools();
        $saInstaller->checkGitAndInit();
        $saInstaller->processDependencies();
        $saInstaller->checkEnvironment();
        $saInstaller->addZendToIncludePath();
        $saInstaller->installation();//checks internally if steps are already done
        $saInstaller->cleanUpDeletedFiles(); //must be before initApplication!
        $saInstaller->initApplication();
        $saInstaller->postInstallation();
        $saInstaller->updateDb(); //this does also cache cleaning!
        $saInstaller->checkDb();
        $saInstaller->done();
    }
    
    /**
     * @param string $currentWorkingDir
     * @param array $options options from outside
     */
    protected function __construct($currentWorkingDir, array $options) {
        $this->options = $options;
        $this->currentWorkingDir = $currentWorkingDir;
        if(empty($this->options['zend'])) {
            $this->options['zend'] = $this->currentWorkingDir.self::ZEND_LIB;
        }
        //requiering the following hardcoded since, autoloader must be downloaded with Zend Package
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/License.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Downloader.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Dependencies.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/DbUpdater.php';
        require_once $this->currentWorkingDir.'/application/modules/default/Models/Installer/PreconditionCheck.php';
        $this->preconditonChecker = new Models_Installer_PreconditionCheck();
        $this->setHostname();
    }
    
    protected function setHostname() {
        $this->hostname = self::HOSTNAME_LINUX;
        if($this->getOS()===  self::OS_WIN){
            $this->hostname = self::HOSTNAME_WIN;
        }
    }
    
    protected function checkAndCallTools() {
        if(!empty($this->options['help'])) {
            $this->showHelp();
            exit;
        }
        if(!empty($this->options['maintenance'])) {
            $this->addZendToIncludePath();
            $this->maintenanceMode();
            exit;
        }
        if(!empty($this->options['announceMaintenance'])) {
            $this->addZendToIncludePath();
            $this->maintenanceMode();
            exit;
        }
        if(!empty($this->options['dbOnly'])) {
            $this->log(PHP_EOL.'Deprecated - call via ./install-and-update.sh: see ./translate5.[sh|bat] list database !');
            $this->addZendToIncludePath();
            $this->initApplication();
            $this->checkDb();
            $this->updateDb();
            exit;
        }
        if(!empty($this->options['applicationState'])) {
            $this->addZendToIncludePath();
            $this->initApplication();
            echo json_encode(ZfExtended_Debug::applicationState());
            exit;
        }
        if(!empty($this->options['updateCheck'])) {
            $this->addZendToIncludePath();
            $this->initApplication();
            $this->preconditonChecker->checkUsers();
            $this->preconditonChecker->checkWorkers();
            exit; //exiting here completly after checkrun
        }
    }
    
    protected function showHelp() {
        echo "\n";
        echo "  Usage: install-and-update.sh\n";
        echo "  or:    install-and-update.sh [OPTION]...\n";
        echo "  or:    install-and-update.sh [ZIPFILE]\n";
        echo "\n";
        echo "  Without parameters: updates translate5 installation with latest public release.\n";
        echo "  Without ZIP File as parameter: ZIP must be a valid translate5 release, updates the current installation with the release given in the ZIP file.\n";
        echo "  Without other arguments: do some maintenance tasks listed below.\n";
        echo "\n\n";
        echo "  Arguments: \n";
        echo "    ZIPFILE                         Optional, updates the installation with the given release from the ZIP file.";
        echo "    --help                          shows this help text\n";
        echo "    --check                         shows some status information about the current installation,\n";
        echo "                                    to decide if maintenance mode is needed or not\n";
        echo "\n\n";
        echo "  For other maintenance tasks call ./translate5.[sh|bat] list! ";
        echo "\n\n";
    }
    
    protected function maintenanceMode() {
        $this->initTranslate5CliBridge();
        $this->log(PHP_EOL.'Deprecated - maintain maintenance via ./install-and-update.sh: see ./translate5.[sh|bat] list maintenance !');
        if(!empty($this->options['announceMaintenance'])) {
            $input = new Symfony\Component\Console\Input\ArrayInput([
                'command' => 'maintenance:announce',
                'timestamp' => $this->options['announceMaintenance'],
                '--message' => $this->options['announceMessage'],
            ]);
            $this->cli->run($input);
            return;
        }
        switch ($this->options['maintenance']) {
            case '0':
            case 'false':
            case 'Off':
            case 'OFF':
            case 'off':
                $input = new Symfony\Component\Console\Input\ArrayInput([
                    'command' => 'maintenance:disable',
                ]);
                $this->cli->run($input);
                break;
            
            case 'show':
                $input = new Symfony\Component\Console\Input\ArrayInput([
                    'command' => 'maintenance:status',
                ]);
                $this->cli->run($input);
                break;
            
            default:
                $input = new Symfony\Component\Console\Input\ArrayInput([
                'command' => 'maintenance:set',
                'timestamp' => $this->options['maintenance'],
                '--message' => $this->options['announceMessage'],
                ]);
                $this->cli->run($input);
        };
    }
    
    protected function checkGitAndInit() {
        $this->installerFile = __FILE__;
        $this->installerHash = md5_file($this->installerFile);
        if(file_exists('.git')) {
            die("\n\n A .git file/directory does exist in the project root!
    Please use git to update your installation and call ./translate5.sh database:update \n\n");
        }
    }
    
    protected function checkEnvironment() {
        $this->initTranslate5CliBridge();
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'system:check',
            '--pre-installation' => null,
            '--ansi' => null,
        ]);
        $this->cli->run($input);
        $this->log('');
    }

    /**
     * Asks the user for his timezone while installation
     * @return string|null
     * @throws Exception
     */
    protected function askTimzone(): ?string {
        if(is_array($this->options) && ($this->options['license-ignore'] ?? false)){
            //FIXME check log after next installation and remove the below md5 based check if not needed anymore!
            error_log("IGNORE LICENSE BY PARAM");
            return null;
        }
        //FIXME since above options is not usable yet, we just check for the host to ignore the license question
        //when using console kit we can replace this with an undocumented switch
        if(md5(gethostname()) === '52c30971e2fe1d24879b307b44e0966f') {
            //FIXME check log after next installation and remove the below md5 based check if not needed anymore!
            error_log("IGNORE LICENSE BY HOST");
            return null;
        }
        $this->initTranslate5CliBridge();
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'installer:timezone',
        ]);
        $this->cli->run($input);
        $this->log('');
        return $this->cli->get('installer:timezone')->getTimezone();
    }
    
    protected function initTranslate5CliBridge()
    {
        if(!empty($this->cli)) {
            return;
        }
        require_once $this->currentWorkingDir.'/vendor/autoload.php';
        $this->cli = new Symfony\Component\Console\Application();
        $this->cli->setAutoExit(false);
        $this->cli->add(new Translate5\MaintenanceCli\Command\SystemCheckCommand());
        $this->cli->add(new Translate5\MaintenanceCli\Command\DatabaseUpdateCommand());
        $this->cli->add(new Translate5\MaintenanceCli\Command\MaintenanceAnnounceCommand());
        $this->cli->add(new Translate5\MaintenanceCli\Command\MaintenanceSetCommand());
        $this->cli->add(new Translate5\MaintenanceCli\Command\MaintenanceDisableCommand());
        $this->cli->add(new Translate5\MaintenanceCli\Command\MaintenanceCommand());
        $this->cli->add(new Translate5\MaintenanceCli\Command\InstallerTimezoneCommand());
    }
    
    protected function checkMyselfForUpdates() {
        if($this->installerHash !== md5_file($this->installerFile)) {
            die("\n\n The translate5 Updater has updated it self, please restart the install-and-update script!\n\n");
        }
    }
    
    protected function processDependencies() {
        $options = $this->options;
        $this->logSection('Checking server for updates and packages:');
        $downloader = new ZfExtended_Models_Installer_Downloader($this->currentWorkingDir);
        
        if(isset($options['applicationZipOverride']) && file_exists($options['applicationZipOverride'])) {
            $zipOverride = $options['applicationZipOverride'];
        }
        else {
            $zipOverride = null;
        }
        
        $depsToAccept = $downloader->pullApplication($zipOverride);
        $this->checkMyselfForUpdates();
        $this->acceptLicenses($depsToAccept);
        $downloader->pullDependencies(true);
    }
    
    protected function installation() {
        $options = $this->options;
        
        //assume installation success if installation.ini exists!
        if(file_exists($this->currentWorkingDir.self::INSTALL_INI)){
            return;
        }
        $this->isInstallation = true;
        $this->logSection('Translate5 Installation');
        
        if(is_array($options) && isset($options['mysql_bin']) && $options['mysql_bin'] != self::MYSQL_BIN) {
            $this->dbCredentials['executable'] = $options['mysql_bin'];
        }
        if(!is_array($options) || empty($options['db::host']) || empty($options['db::username']) || empty($options['db::password']) || empty($options['db::database'])) {
            while(! $this->promptDbCredentials());
        } else {
            $this->dbCredentials['host']     = $options['db::host'];
            $this->dbCredentials['username'] = $options['db::username'];
            $this->dbCredentials['password'] = $options['db::password'];
            $this->dbCredentials['database'] = $options['db::database'];
        }

        $timezone = $this->askTimzone();
        
        $this->createInstallationIni(['timezone' => $timezone]);
        if(! $this->checkDb()) {
            unlink($this->currentWorkingDir.self::INSTALL_INI);
            $this->log("\nFix the above errors and restart the installer! DB Config ".self::INSTALL_INI." was automatically removed therefore.\n");
            exit;
        }
        $this->initDb();
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
            $toDelete = trim($toDelete);
            //ignore comments
            if(empty($toDelete) || strpos($toDelete, '#') === 0){
                continue;
            }
            $cwd = realpath($this->currentWorkingDir);
            $toDelete = realpath($this->currentWorkingDir.$toDelete);
            if(!$toDelete){
                continue;
            }
            //ensure that file/dir to be deleted is in the currentWorkingDir
            if(strpos($toDelete, $cwd) !== 0 || $cwd == $toDelete) {
                $this->log('Won\'t delete file '.$toDelete);
                continue;
            }
            $file = new SplFileInfo($toDelete);
            if($file->isFile() && $file->isReadable()) {
                unlink($file);
            }
            if($file->isDir() && $file->isReadable()) {
                ZfExtended_Models_Installer_Downloader::removeRecursive($file);
                rmdir($file);
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
     * @deprecated moved into abstract module
     * Adds the downloaded Zend Lib to the include path
     */
    protected function addZendToIncludePath() {
        if(file_exists($this->currentWorkingDir.'/vendor/autoload.php')) {
            require_once $this->currentWorkingDir.'/vendor/autoload.php';
        }
    }
    
    /**
     * prompting the user for the DB credentials
     */
    protected function promptDbCredentials() {
        $this->log('Please enter the MySQL database settings, the database must already exist.');
        $this->log('Default character set must be utf8mb4. This can be done for example with the following command: ');
        $this->log('  CREATE DATABASE IF NOT EXISTS `translate5` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'."\n");
        
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
        if(is_array($this->options) && ($this->options['license-ignore'] ?? false)){
            return;
        }
        //if install-and-update.sh is called on the server and above options is not usable, there fore this fallback
        if(md5(gethostname()) === '52c30971e2fe1d24879b307b44e0966f') {
            return;
        }
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
        if(is_array($this->options) && !empty($this->options['hostname'])) {
            $this->hostname = $this->options['hostname'];
            return;
        }
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
        
        $dbupdater = new ZfExtended_Models_Installer_DbUpdater($db, $exec, $this->currentWorkingDir);
        if(!$dbupdater->executeSqlFile($exec, $db, $dbInit, $output)) {
            $this->log('Error on Importing '.self::DB_INIT.' file, stopping installation. Called command: '.$exec.".\n".'Result of Command: '.print_r($output,1));
            exit;
        }
        $this->log('Translate5 tables created.');
    }
    
    /**
     * Creates the installation.ini
     * @param array $additionalParameters
     * @return boolean
     */
    protected function createInstallationIni(array $additionalParameters) {
        $content = array();
        $content[] = '[application]';
        $content[] = 'resources.db.params.host = "'.$this->dbCredentials['host'].'"';
        $content[] = 'resources.db.params.username = "'.$this->dbCredentials['username'].'"';
        $content[] = 'resources.db.params.password = "'.$this->dbCredentials['password'].'"';
        $content[] = 'resources.db.params.dbname = "'.$this->dbCredentials['database'].'"';
        if(!empty($this->dbCredentials['executable'])) {
            $content[] = 'resources.db.executable = "'.$this->dbCredentials['executable'].'"';
        }
        if(!empty($additionalParameters['timezone'])) {
            $content[] = '';
            $content[] = 'phpSettings.date.timezone = "'.$additionalParameters['timezone'].'"';
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
        
        Zend_Registry::set('config', new Zend_Config([
            'resources' => new Zend_Config([
                'db' => new Zend_Config([
                    'adapter' => "PDO_MYSQL",
                    'isDefaultTableAdapter' => 1,
                    'params' => new Zend_Config([
                        'charset' => "utf8mb4",
                        'host' => $this->dbCredentials['host'],
                        'username' => $this->dbCredentials['username'],
                        'password' => $this->dbCredentials['password'],
                        'dbname' => $this->dbCredentials['database'],
                    ])
                ])
            ])
        ]));
        
        return ($bytes > 0);
    }
    
    /**
     * returns true if the DB is OK
     * @return bool
     */
    protected function checkDb(): bool {
        $this->initTranslate5CliBridge();
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'system:check',
            'module' => 'database',
            '--pre-installation' => null,
            '--ansi' => null,
        ]);
        return $this->cli->run($input) === 0;
    }
    
    /**
     * Applies all DB alter statement files to the DB
     */
    protected function updateDb() {
        $changelog = ZfExtended_Factory::get('editor_Models_Changelog');
        /* @var $changelog editor_Models_Changelog */
        $beforeMaxChangeLogId = $changelog->getMaxId();

        $this->initTranslate5CliBridge();
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'database:update',
            '--import' => null,
        ]);
        $this->cli->run($input);
        
        $newChangeLogEntries = $changelog->moreChangeLogs($beforeMaxChangeLogId, $changelog::ALL_GROUPS);
        $version = ZfExtended_Utils::getAppVersion();
        $changelog->updateVersion($beforeMaxChangeLogId, $version);
        $this->sendChangeLogs($newChangeLogEntries, $version);
    }
    
    /**
     * Send the given changelogs to the admin users.
     * @param array $newChangeLogEntries
     */
    protected function sendChangeLogs($newChangeLogEntries, $version) {
        if(empty($newChangeLogEntries)) {
            return;
        }
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $admins = $user->loadAllByRole(['admin']);
        //Zend_Registry::set('Zend_Locale', 'en');
        $mail = ZfExtended_Factory::get('ZfExtended_Mailer', ['utf8']);
        /* @var $mail ZfExtended_Mailer */
        $mail->setSubject("ChangeLog to translate5 version ".$version.' on '.$this->hostname);
        $html  = 'Your translate5 installation on '.$this->hostname.' was updated to version <b>'.$version.'</b>.<br><br>';
        $html .= '<b><u>ChangeLog</u></b><br>';
        
        $byType = ['bugfix' => [], 'feature' => [], 'change' => []];
        $typeLabels = ['feature' => 'New Features:', 'change' => 'Changes:', 'bugfix' => 'Fixes:'];
        foreach($newChangeLogEntries as $entry) {
            $byType[$entry['type']][] = $entry;
        }
        foreach($typeLabels as $type => $label) {
            $entries = $byType[$type];
            if(empty($entries)) {
                continue;
            }
            $html .= '<br><b>'.$label.'</b><br>';
            foreach($entries as $entry) {
                $html .= '<p style="margin:0;padding:0 0 5px 0;">';
                if(preg_match('/^[A-Z0-9]+-[0-9]+$/', $entry['jiraNumber'])) {
                    $link = '<a href="'.self::JIRA_URL.$entry['jiraNumber'].'">'.htmlspecialchars($entry['jiraNumber']).'</a>';
                }
                else {
                    $link = $entry['jiraNumber'];
                }
                $html .= $link.': '.htmlspecialchars($entry['title']);
                if(!empty($entry['description']) && $entry['title'] != $entry['description']) {
                    $html .= '<p style="font-size:smaller;padding:0;margin:0;">'.htmlspecialchars($entry['description']).'</p>';
                }
                $html .= '</p>';
            }
        }
        $html .= '<br><br>This e-mail was created automatically by the translate5 install and update script.';
        
        $mail->setBodyHtml($html);
        //if there are no admins or we are in installation process, no e-mails are sent
        if(empty($admins) || $this->isInstallation) {
            return;
        }
        foreach($admins as $admin) {
            //$mail->setFrom('thomas@mittagqi.com'); //system mailer?
            $mail->addTo($admin['email'], $admin['firstName'].' '.$admin['surName']);
        }
        $mail->send();
    }
    
    /**
     * @deprecated moved into abstract modules
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
        ZfExtended_BaseIndex::$addMaintenanceConfig = true;
        $index = ZfExtended_BaseIndex::getInstance();
        $index->initApplication()->bootstrap();
        $index->addModuleOptions('default');
        
        //set the hostname to the configured one:
        $config = Zend_Registry::get('config');
        if(!$this->isInstallation){
            $this->hostname = $config->runtimeOptions->server->name;
        }
        $version = ZfExtended_Utils::getAppVersion();
        $this->log('Current translate5 version '.$version);
    }
    
    protected function done() {
        $version = ZfExtended_Utils::getAppVersion();
        $this->log("\nTranslate5 installation / update to version $version done.\n");
        if(!empty($this->hostname)) {
            $this->log("\nPlease visit http://".$this->hostname."/ to enjoy Translate5.\n");
            $this->log("For informations how to set up openTMSTermTagger or enable the application to send E-Mails, see http://confluence.translate5.net.\n\n");
        }
        $this->log('  In case of errors on installation / update please visit http://confluence.translate5.net');
        $this->log('  or write an email to support@translate5.net');
    }
    
    protected function log($msg) {
        echo $msg."\n";
    }
    
    protected function logSection($msg, $lineChar = '=') {
        echo "\n".$msg."\n";
        echo str_pad('', strlen($msg), $lineChar)."\n\n";
    }
}
