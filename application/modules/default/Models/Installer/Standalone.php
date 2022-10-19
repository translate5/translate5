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

/**
 */
class Models_Installer_Standalone {
    const INSTALL_INI = '/application/config/installation.ini';
    const CLIENT_SPECIFIC_INSTALL = '/client-specific-installation';
    const CLIENT_SPECIFIC = '/client-specific';
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
    const INSTALLER_VERSION = 17;
    const VENDOR_AUTOLOAD_PHP = '/vendor/autoload.php';

    /**
     * @var string
     */
    protected string $currentWorkingDir;
    
    /**
     * @var array
     * help                → show help
     * maintenance         → deprecated maintenance
     * announceMaintenance → deprecated announceMaintenance
     * dbOnly              → deprecated, use da:u
     * applicationState    → deprecated status
     * updateCheck         → deprecated status
     * license-ignore      → ignore licenses, for automation
     * applicationZipOverride → path to zip file
     * db::host            → db host
     * db::username        → db username
     * db::password        → db password
     * db::database        → db database
     * hostname            → hostname to be used (SSL?)
     * timezone            → timezone to be used!
     */
    protected array $options;
    
    /**
     * @var array
     */
    protected array $dbCredentials = [
            'host' => 'localhost',
            'username' => 'root',
            'password' => '',
            'dbname' => 'translate5',
    ];
    
    protected string $hostname;
    
    protected bool $isInstallation = false;
    
    /**
     * Stores the md5 hash of this file before downloading the update.
     * If the hash is changing after downloading the translate5 package this means
     * updates in the updater itself, so that it has to be restarted!
     * @var string
     */
    protected string $installerHash;
    
    /**
     * contains the called file, the path to this file
     * @var string
     */
    protected string $installerFile;
    
    /**
     * @var Symfony\Component\Console\Application
     */
    protected \Symfony\Component\Console\Application $cli;
    private bool $recreateDb = false;

    /**
     * @param array $options
     * @throws Zend_Db_Exception
     * @throws Zend_Exception
     * @throws Zend_Mail_Exception
     * @throws Exception
     */
    public static function mainLinux(array $options = []): void
    {
        //initially we have to load the locales from the environment
        setlocale(LC_ALL, '');
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
     * @param array $options
     * @throws Zend_Db_Exception
     * @throws Zend_Exception
     * @throws Zend_Mail_Exception
     * @throws Exception
     */
    public static function developerInstall(array $options = []): void
    {
        //initially we have to load the locales from the environment
        setlocale(LC_ALL, '');
        $saInstaller = new self(getcwd(), $options);
        $saInstaller->checkEnvironment();

        //FIXME HERE for dev installations we need extjs 6.2 and extjs 7.0.0
        // so needing a DEV flag in dependencies config
        // and a method which initially downloads the dev flagged deps once
        // $saInstaller->processDevDependencies(); or so

        //for developer/docker installations we re-create the DB to ensure collation etc
        $saInstaller->recreateDb = true;

        $saInstaller->installation();//checks internally if steps are already done
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
    protected function __construct(string $currentWorkingDir, array $options) {
        $this->options = $options;
        $this->currentWorkingDir = $currentWorkingDir;
        define('APPLICATION_ROOT', $this->currentWorkingDir);
        define('APPLICATION_PATH', $this->currentWorkingDir.DIRECTORY_SEPARATOR.'application');
        //requiering the following hardcoded since, autoloader must be downloaded with Zend Package
        require_once $this->currentWorkingDir.'/library/ZfExtended/Utils.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/License.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Downloader.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/Dependencies.php';
        require_once $this->currentWorkingDir.'/library/ZfExtended/Models/Installer/DbUpdater.php';
        $this->setHostname();
    }
    
    protected function setHostname(): void
    {
        $this->hostname = self::HOSTNAME_LINUX;
        if($this->getOS()===  self::OS_WIN){
            $this->hostname = self::HOSTNAME_WIN;
        }
    }

    /**
     * @throws Zend_Exception
     * @throws Exception
     */
    protected function checkAndCallTools(): void
    {
        if(!empty($this->options['help'])) {
            $this->showHelp();
            exit;
        }
        if(!empty($this->options['maintenance'])) {
            $this->log(PHP_EOL.'Deprecated - call ./translate5.sh maintenance');
            exit;
        }
        if(!empty($this->options['announceMaintenance'])) {
            $this->log(PHP_EOL.'Deprecated - call ./translate5.sh maintenance');
            exit;
        }
        if(!empty($this->options['dbOnly'])) {
            $this->log(PHP_EOL.'Deprecated - call via ./install-and-update.sh: see ./translate5.[sh|bat] list database !');
            exit;
        }
        if(!empty($this->options['applicationState'])) {
            $this->log(PHP_EOL.'Deprecated - call ./translate5.sh status');
            exit;
        }
        if(!empty($this->options['updateCheck'])) {
            $this->log(PHP_EOL.'Deprecated - call ./translate5.sh status');
            exit;
        }
    }
    
    protected function showHelp(): void
    {
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
        echo "\n\n";
        echo "  For other maintenance tasks call: \n";
        echo "    ./translate5.[sh|bat] list";
        echo "\n\n";
    }

    protected function checkGitAndInit() {
        $this->installerFile = __FILE__;
        $this->installerHash = md5_file($this->installerFile);
        if(file_exists('.git')) {
            die("\n\n A .git file/directory does exist in the project root!
    Please use git to update your installation and call ./translate5.sh database:update \n\n");
        }
    }

    /**
     * @throws Exception
     */
    protected function checkEnvironment() {
        $this->initTranslate5CliBridge();
        $input = new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'system:check',
            '--pre-installation' => null,
            '--ansi' => null,
        ]);
        if($this->cli->run($input) !== 0){
            die(1);
        }
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
    
    protected function initTranslate5CliBridge(): void
    {
        if(!empty($this->cli)) {
            return;
        }
        require_once $this->currentWorkingDir. self::VENDOR_AUTOLOAD_PHP;
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
    
    protected function checkMyselfForUpdates(): void
    {
        if($this->installerHash !== md5_file($this->installerFile)) {
            die("\n\n The translate5 Updater has updated it self, please restart the install-and-update script!\n\n");
        }
    }
    
    protected function processDependencies(): void
    {
        $o = $this->options;
        $this->logSection('Checking server for updates and packages:');
        $downloader = new ZfExtended_Models_Installer_Downloader($this->currentWorkingDir);
        
        if(isset($o['applicationZipOverride']) && file_exists($o['applicationZipOverride'])) {
            $zipOverride = $o['applicationZipOverride'];
        }
        else {
            $zipOverride = null;
        }
        
        $depsToAccept = $downloader->pullApplication($zipOverride);
        $this->checkMyselfForUpdates();
        $this->acceptLicenses($depsToAccept);
        $downloader->pullDependencies(true);
    }

    /**
     * @throws Zend_Exception
     * @throws Exception
     */
    protected function installation(): void
    {
        $o = $this->options;

        //assume installation success if installation.ini exists!
        if(file_exists($this->currentWorkingDir.self::INSTALL_INI)){
            return;
        }
        $this->isInstallation = true;
        $this->logSection('Translate5 Installation');
        
        if(!is_array($o) || empty($o['db::host']) || empty($o['db::username']) || empty($o['db::password']) || empty($o['db::database'])) {
            while(! $this->promptDbCredentials()){};
        } else {
            $this->dbCredentials['host']     = $o['db::host'];
            $this->dbCredentials['username'] = $o['db::username'];
            $this->dbCredentials['password'] = $o['db::password'];
            $this->dbCredentials['dbname'] = $o['db::database'];
        }

        if(empty($o['timezone'])) {
            $timezone = $this->askTimzone();
        }
        else {
            $timezone = $o['timezone'];
        }

        // use chosen timezone and store it in ini
        date_default_timezone_set($timezone);
        $this->createInstallationIni(['timezone' => $timezone]);

        if($this->recreateDb) {
            $dbupdater = new ZfExtended_Models_Installer_DbUpdater();
            $conf = $this->dbCredentials;
            $conf['dropIfExists'] = true;
            $dbupdater->createDatabase(... $conf);
        }

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
    protected function cleanUpDeletedFiles(): void
    {
        $deleteList = dirname(__FILE__).'/filesToBeDeleted.txt';
        $toDeleteList = file($deleteList);
        foreach($toDeleteList as $toDelete) {
            $toDelete = trim($toDelete);
            //ignore comments
            if(empty($toDelete) || str_starts_with($toDelete, '#')){
                continue;
            }
            $cwd = realpath($this->currentWorkingDir);
            $toDelete = realpath($this->currentWorkingDir.$toDelete);
            if(!$toDelete){
                continue;
            }
            //ensure that file/dir to be deleted is in the currentWorkingDir
            if(!str_starts_with($toDelete, $cwd) || $cwd == $toDelete) {
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
    protected function moveClientSpecific(): void
    {
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
     * @throws Zend_Db_Exception
     * @throws Zend_Exception
     */
    protected function postInstallation(): void
    {
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
    protected function addZendToIncludePath(): void
    {
        if(file_exists($this->currentWorkingDir. self::VENDOR_AUTOLOAD_PHP)) {
            require_once $this->currentWorkingDir. self::VENDOR_AUTOLOAD_PHP;
        }
    }
    
    /**
     * prompting the user for the DB credentials
     */
    protected function promptDbCredentials(): bool
    {
        $this->log('Please enter the MySQL database settings, the database must already exist.');
        $this->log('Default character set must be utf8mb4. This can be done for example with the following command: ');
        $this->log('  CREATE DATABASE IF NOT EXISTS `translate5` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'."\n");
        
        foreach($this->dbCredentials as $key => $default) {
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
            echo $key.': '.$value.PHP_EOL;
        }
        return 'y' === strtolower($this->prompt(PHP_EOL.'Confirm the entered data with "y", press any other key to reenter DB credentials.'.PHP_EOL));
    }
    
    /**
     * prompts for all new licenses to be accepted
     * @param array $depsToAccept
     */
    protected function acceptLicenses(array $depsToAccept): void
    {
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
    static public function getOS(): int
    {
        switch (true) {
            case stristr(PHP_OS, 'DAR'): return self::OS_OSX;
            case stristr(PHP_OS, 'WIN'): return self::OS_WIN;
            case stristr(PHP_OS, 'LINUX'): return self::OS_LINUX;
            default : return self::OS_UNKNOWN;
        }
    }
    
    protected function prompt($message = 'prompt: ', $hidden = false): bool|string
    {
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
    protected function promptHostname(): void
    {
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
    protected function initDb(): void
    {
        $this->log("\nCreating the database base layout...");

        $dbupdater = new ZfExtended_Models_Installer_DbUpdater();
        if(! $dbupdater->initDb()) {
            $this->log('Error on creating initial DB structure, stopping installation. Result: '.print_r($dbupdater->getErrors(),1));
            exit;
        }
        $this->log('Translate5 tables created.');
        $warnings = $dbupdater->getWarnings();
        if(!empty($warnings)) {
            $this->log('There were the following warnings: ');
            $this->log(join(PHP_EOL), $warnings);
        }
    }

    /**
     * Creates the installation.ini
     * @param array $additionalParameters
     * @return boolean
     * @throws Exception
     */
    protected function createInstallationIni(array $additionalParameters): bool
    {
        $content = [];
        $content[] = '[application]';
        $content[] = 'resources.db.params.host = "'.$this->dbCredentials['host'].'"';
        $content[] = 'resources.db.params.username = "'.$this->dbCredentials['username'].'"';
        $content[] = 'resources.db.params.password = "'.$this->dbCredentials['password'].'"';
        $content[] = 'resources.db.params.dbname = "'.$this->dbCredentials['dbname'].'"';
        $content[] = '';
        $content[] = '; secret for encryption of the user passwords';
        $content[] = '; WHEN YOU CHANGE THAT ALL PASSWORDS WILL BE INVALID!';
        $content[] = 'runtimeOptions.authentication.secret = '.bin2hex(random_bytes(32));

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
                        'dbname' => $this->dbCredentials['dbname'],
                    ])
                ])
            ])
        ]));
        
        return ($bytes > 0);
    }

    /**
     * returns true if the DB is OK
     * @return bool
     * @throws Exception
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
     * @throws Zend_Mail_Exception
     * @throws Exception
     */
    protected function updateDb(): void
    {
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
     * @param $version
     * @throws Zend_Mail_Exception
     */
    protected function sendChangeLogs(array $newChangeLogEntries, $version): void
    {
        if(empty($newChangeLogEntries)) {
            return;
        }
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $admins = $user->loadAllByRole(['admin']);
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
     * @throws Zend_Exception
     * @deprecated moved into abstract modules
     * generates a Zend Application like environment with all needed registry entries filled
     */
    protected function initApplication(): void
    {
        $_SERVER['REQUEST_URI'] = '/database/forceimportall';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['HTTP_HOST'] = 'localhost';
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

    /**
     * @throws Zend_Exception
     * @throws Zend_Db_Exception
     */
    protected function done(): void
    {
        if($this->isInstallation){
            //since passwords are encrypted, we have to do that for the demo users too
            editor_Utils::initDemoAndTestUserPasswords();
        }

        $version = ZfExtended_Utils::getAppVersion();
        $this->log("\nTranslate5 installation / update to version $version done.\n");
        if(!empty($this->hostname)) {
            $this->log("\nPlease visit http://".$this->hostname."/ to enjoy Translate5.\n");
            $this->log("For informations how to set up openTMSTermTagger or enable the application to send E-Mails, see http://confluence.translate5.net.\n\n");
        }
        $this->log('  In case of errors on installation / update please visit http://confluence.translate5.net');
        $this->log('  or write an email to support@translate5.net');
    }
    
    protected function log($msg): void
    {
        echo $msg."\n";
    }
    
    protected function logSection($msg, $lineChar = '='): void
    {
        echo "\n".$msg."\n";
        echo str_pad('', strlen($msg), $lineChar)."\n\n";
    }
}
