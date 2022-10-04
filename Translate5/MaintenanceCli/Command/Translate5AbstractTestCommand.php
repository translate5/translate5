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
namespace Translate5\MaintenanceCli\Command;

use Symfony\Component\Console\Input\InputOption;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Translate5\MaintenanceCli\Test\Config;

abstract class Translate5AbstractTestCommand extends Translate5AbstractCommand
{
    const RELATIVE_TEST_ROOT = 'application/modules/editor/testcases/';

    const RELATIVE_TEST_DIR = self::RELATIVE_TEST_ROOT.'editorAPI/';

    /**
     * General Options of all test-commands
     */
    protected function configure()
    {
        $this->addOption(
            'xdebug',
            'x',
            InputOption::VALUE_NONE,
            'Send the XDEBUG cookie to enable interactive debugging.');

        $this->addOption(
            'keep-data',
            'k',
            InputOption::VALUE_NONE,
            'Prevents that the test data (tasks, etc) is cleaned up after the test. Useful for debugging a test. Must be implemented in the test itself, so not all tests support that flag yet.');

        $this->addOption(
            'stop-on-error',
            'e',
            InputOption::VALUE_NONE,
            'Leads to the testsuite stopping on the first error (not failure!).');

        $this->addOption(
            'stop-on-failure',
            'f',
            InputOption::VALUE_NONE,
            'Leads to the testsuite stopping on the first failure (not error!).');
    }

    /**
     * Sets the environment and initializes T5 for the given environment
     * @param string $environment
     * @param bool $reinitMissingDb
     * @param bool $forceRecreation
     * @return bool
     * @throws \Zend_Exception
     */
    protected function initTestEnvironment(string $environment, bool $reinitMissingDb, bool $forceRecreation = false) : bool {
        // we reinit the test-db if it does not exist or a recreation is forced
        // this also checks if the instance is set up for tests / API-test are allowed
        if($environment === 'test' && $reinitMissingDb){
            if($this->reInitTestDatabase($forceRecreation)){
                // Crucial: this triggrs the test-sections in the ini-files to be used in the test-bootstrap
                putenv('APPLICATION_ENV=test');
                return true;
            }
            return false;
        }
        // init T5 for the test environment in which the tests have to be run - if wanted
        // this also checks, if the test-environment seems configured in installation.ini
        if($environment === 'test'){
            $this->initTranslate5($environment);
            if($this->checkApiTestsAllowed()){
                // Crucial: this triggrs the test-sections in the ini-files to be used in the test-bootstrap
                putenv('APPLICATION_ENV=test');
            } else {
                return false;
            }
        } else {
            // for running tests on the app database
            $this->initTranslate5();
        }
        return true;
    }

    /**
     * Checks, if the environment is set up to allow API tests
     * @return bool
     */
    protected function checkApiTestsAllowed() : bool
    {
        try {
            $config = \Zend_Registry::get('config');
            if($config?->testSettings?->testsAllowed !== 1){
                $this->io->error('This installation seems not to be set up for API tests.');
                return false;
            }
            return true;
        } catch(\Throwable){
            $this->io->error('Test-config could not be loaded.');
            return false;
        }
    }

    /**
     * Starts the unit test for a single test, a suite or all tests
     * @param string|null $testPath
     * @param string|null $testSuite
     */
    protected function startApiTest(string $testPath = null, string $testSuite = null)
    {
        try {
            $verbose = '--verbose'; // '--debug'
            $stopOnError = '';
            $stopOnFailure = '';
            $testPathOrDir = '';
            $configurationOption = '--no-configuration';
            $configurationFile = '';
            $bootstrapOption = '--bootstrap';
            $bootstrapFile = self::RELATIVE_TEST_ROOT.'bootstrap.php';
            $suiteOption = '';
            $suiteFile = '';

            // environment stuff needed for all tests (using environment variables here keeps compatibility with plain apiTest.sh call)
            putenv('APPLICATION_ROOT='.__DIR__);

            if($this->input->getOption('xdebug')){
                putenv('XDEBUG_ENABLE=1');
            }

            if($this->input->getOption('keep-data')){
                putenv('KEEP_DATA=1');
            }

            // command options usable for all tests
            if($this->input->getOption('stop-on-error')){
                $stopOnError = '--stop-on-error';
            }
            if($this->input->getOption('stop-on-failure')){
                $stopOnFailure = '--stop-on-failure';
            }

            // test / suite / all specific stuff. Note that DO_CAPTURE is defined in the concrete command for a single test
            if($testPath !== null){
                $testPathOrDir = $testPath;
                $this->io->note('Running test \''.basename($testPath).'\'');

            } else if($testSuite !== null){

                $this->io->note('Running suite \''.$testSuite.'\'');
                // defining the configuration-file-option to read the suites from
                $configurationOption = '--configuration';
                // QUIRK: why do we have to set an absolute path here to get the tests running ? With relative pathes, PHPUnit finds the configuration but can not find the linked files ...
                $configurationFile = APPLICATION_ROOT.'/'.self::RELATIVE_TEST_ROOT.'phpunit.xml';
                // defining the suite to use
                $suiteOption = '--testsuite';
                $suiteFile = $testSuite;
                // must not be set when using a suite, otherwise the suite will never be triggered ...
                $testPathOrDir = '';
                putenv('DO_CAPTURE=0');
            } else {
                putenv('DO_CAPTURE=0');
                $testPathOrDir = 'application';
            }

            $assembly = [
                'phpunit',
                '--colors',
                $verbose,
                $stopOnError,
                $stopOnFailure,
                '--testdox-text',
                'last-test-result.txt',
                '--cache-result-file',
                '.phpunit.result.cache',
                $configurationOption,
                $configurationFile,
                $bootstrapOption,
                $bootstrapFile,
                $suiteOption,
                $suiteFile,
                $testPathOrDir
            ];

            // die(implode(' ', $assembly)."\n");

            // start PHPUnit with neccessary options
            $command = new \PHPUnit\TextUI\Command();
            $command->run($assembly);
            $this->io->success('Last test result stored in TEST_ROOT/last-test-result.txt');

        }  catch(\Throwable $e) {
            $this->io->error($e->getMessage()."\n\n".$e->getTraceAsString());
        }
    }

    /**
     * Retrieves all suites by parsing the phpunit.xml
     * If this file is corrupt or not available, returns a single item hinting at the problem
     * @return array
     */
    protected function getAllSuiteNames(): array
    {
        try {
            // misusing PHPUnit private loader here, but it's the easiest way to have the correct options etc.
            $document = (new \PHPUnit\Util\Xml\Loader)->loadFile(self::RELATIVE_TEST_ROOT.'phpunit.xml', false, true, true);
            $xpath = new \DOMXPath($document);
            /* @var \DOMElement[] $elements */
            $elements = [];
            $nodes = $xpath->query('testsuites/testsuite');
            if ($nodes->length === 0) {
                $nodes = $xpath->query('testsuite');
            }
            if ($nodes->length === 1) {
                $elements[] = $nodes->item(0);
            } else {
                foreach ($nodes as $testSuiteNode) {
                    $elements[] = $testSuiteNode;
                }
            }
            $suiteNames = [];
            foreach ($elements as $element) {
                $name = (string) $element->getAttribute('name');
                if(!empty($name)){
                    $suiteNames[] = $name;
                }
            }
            if(count($suiteNames) === 0){
                die('No suites defined in "'.self::RELATIVE_TEST_ROOT.'phpunit.xml"'."\n"); // since this is called on command-creation we simply die...
            }
            return $suiteNames;
        } catch (\Throwable) {
            die('File "'.self::RELATIVE_TEST_ROOT.'phpunit.xml" is missing.'."\n"); // since this is called on command-creation we simply die...
        }
    }

    /**
     * Erases the current test-DB and creates it from scratch. Returns the success of the action
     * @param bool $forceRecreation: if not set the DB will only be recreated if it does not exist
     * @return bool
     * @throws \Zend_Exception
     */
    protected function reInitTestDatabase(bool $forceRecreation = false): bool
    {
        $testDbExists = true;
        // Somehow dirty but we must initialize the app anyway ...
        try {
            $translate5 = new Application();
            $translate5->init('test'); // crucial: use test-environment to get the test-configurations
        } catch (\Throwable $e){
            if(str_contains($e->getMessage(), 'Unknown database')) {
                $testDbExists = false;
                // we must make sure here es well, that this instance is set up as a test-system
                $config = \Zend_Registry::get('config');
                if($config?->testSettings?->testsAllowed !== 1){
                    $this->io->error('This installation seems not to be set up for API tests.');
                    return false;
                }
            } else {
                // other Problem, cancel
                $this->io->error($e->getMessage()."\n\n".$e->getTraceAsString());
                return false;
            }
        }
        // in some cases it's only wanted to create the DB if it does not exist
        $config = \Zend_Registry::get('config');
        if($testDbExists && !$forceRecreation){
            $this->io->note('Database \''.$config->resources->db->params->dbname.'\' already exists');
            return true;
        }
        $this->io->note('Recreate database \''.$config->resources->db->params->dbname.'\'');

        try {
            // evaluate database params
            $baseIndex = \ZfExtended_BaseIndex::getInstance();
            $application = $baseIndex->initApplication();
            $config = $application->getOption('resources');
            $config = $config['db']['params'];
            $testSettings = $application->getOption('testSettings');
            if(empty($testSettings) || $testSettings['testsAllowed'] !== 1){
                $this->io->error('This installation seems not to be set up for API tests.');
                return false;
            }

            $applicationDbName = $testSettings['applicationDbName']; // we need the application db-name from a seperate value (created with the test:addinisection Command) as the db-params are overridden
            // check, if configured test-db meets our expectaions
            if(empty($applicationDbName)){
                $this->io->error('The configured application database is missing or wrongly set in installation.ini!');
                return false;
            }
            if($applicationDbName === $config['dbname']){
                $this->io->error('The configured test database in installation.ini must not be the application database!');
                return false;
            }
            $testDbName = Config::createTestDatabaseName($applicationDbName);
            if($config['dbname'] !== $testDbName){
                $this->io->error('The configured test database in installation.ini [test:application] must be \''.$testDbName.'\'!');
                return false;
            }

            // retrieve the needed configs from the application DB
            $configs = $this->getApplicationConfiguration($applicationDbName, $config['host'], $config['username'], $config['password']);

            // delete (if needed) and recreate DB. recreate tables
            if(
                $this->recreateDatabase($config['host'], $config['username'], $config['password'], $config['dbname'], $testDbExists)
                && $this->recreateTables($configs, 'test')
            ){
                $this->io->note('Successfully recreated database \''.$config['dbname'].'\'');
                // cleanup/reinitialize the test data dirs
                $this->reInitDataDirectory(Config::DATA_DIRECTORY);
                return true;
            }
        } catch(\Throwable $e) {
            $this->io->error($e->getMessage()."\n\n".$e->getTraceAsString());
        }
        return false;
    }

    /**
     * Erases the current application-DB and creates it from scratch
     * This must be confirmed by the user
     * @return bool
     */
    protected function reInitApplicationDatabase(): bool
    {
        try {
            // evaluate database params
            $translate5 = new Application();
            $translate5->init(); // for recreating the app database we use the normal environment
            $baseIndex = \ZfExtended_BaseIndex::getInstance();
            $config = $baseIndex->initApplication()->getOption('resources');
            $config = $config['db']['params'];

            if(!$this->io->confirm('Shall the current database "'.$config['dbname'].'" really be erased and recreated from scratch?')) {
                return false;
            }

            // get current configs first
            $configs = $this->getCurrentConfiguration();

            // delete and recreate DB. recreate tables
            if(
                $this->recreateDatabase($config['host'], $config['username'], $config['password'], $config['dbname'], true)
                && $this->recreateTables($configs, 'application')
            ){
                $this->io->note('Successfully recreated database \''.$config['dbname'].'\'');
                // cleanup/reinitialize the "normal" data dirs
                $this->reInitDataDirectory('data');
                return true;
            }
        } catch(\Throwable $e) {
            $this->io->error($e->getMessage()."\n\n".$e->getTraceAsString());
        }
        return false;
    }

    /**
     * Cleans up the user-data when recreating the database
     */
    private function reInitDataDirectory(string $dataDirectory): void
    {
        if(!is_dir($dataDirectory)){
            mkdir($dataDirectory, 0777, true);
        }
        foreach(Config::getUserDataFolders() as $folder){
            $userDir = $dataDirectory.'/'.$folder;
            if(is_dir($userDir)){
                \ZfExtended_Utils::recursiveDelete($userDir);
            }
            mkdir($userDir, 0777);
        }
        $this->io->note('Successfully cleaned user-data directory \'/'.$dataDirectory.'\'');
    }

    /**
     * Recreates the database from scratch
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbname
     * @param bool $exists
     * @return bool
     */
    private function recreateDatabase(string $host, string $username, string $password, string $dbname, bool $exists=false): bool
    {
        // we need to use PDO, Zend works only with databases
        $pdo = new \PDO('mysql:host='.$host, $username, $password);
        if($exists){
            try {
                $pdo->query('DROP DATABASE '.$dbname.';');
                $this->io->note('Dropped database '.$dbname);
            } catch (\PDOException $e){
                $this->io->error($e->getMessage()."\n\n".$e->getTraceAsString());
                return false;
            }
        }
        // now create DB from scratch
        $pdo->query('CREATE DATABASE '.$dbname.' DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        return true;
    }

    /**
     * Recreates the database-tables from scratch
     * @param array $configs
     * @param string $environment
     * @return bool
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     */
    private function recreateTables(array $configs, string $environment): bool
    {
        // Create the tables from scratch
        $updater = new \ZfExtended_Models_Installer_DbUpdater();
        //add the test SQL path
        $updater->addAdditonalSqlPath(APPLICATION_PATH.'/modules/editor/testcases/database/');
        // init DB
        if ($updater->initDb() && $updater->importAll() && !$updater->hasErrors()) {
            // re-init app to get fresh config && other stuff
            $this->initTranslate5($environment);
            // encrypt test-user passworts
            \editor_Utils::initDemoAndTestUserPasswords();
            // add needed plugins
            $this->initPlugins();
            // add the needed configs
            $this->initConfiguration($configs);
            return true;
        }
        if($updater->hasErrors()){
            $this->io->error($updater->getErrors());
        }
        if($updater->hasWarnings()){
            $this->io->error($updater->getWarnings());
        }
        return false;
    }

    /**
     * The here added system configuration is neccessary for the tests to be constant
     * @param array $neededConfigs
     */
    private function initConfiguration(array $neededConfigs): void
    {
        $config = new \editor_Models_Config();
        // set the predefined or dynamically evaluated values
        foreach($neededConfigs as $name => $value) {
            if($value !== null){
                $config->update($name, $value);
            }
        }
    }

    /**
     * Retrieves the "dynamic" config values that need to be copied from the application DB as they hardly can be set statically to suit all installations
     * @param string $applicationDbName
     * @param string $host
     * @param string $username
     * @param string $password
     * @return array
     */
    private function getApplicationConfiguration(string $applicationDbName, string $host, string $username, string $password): array
    {
        $this->io->note('Copying config-values from database \''.$applicationDbName.'\'');
        $pdo = new \PDO('mysql:host='.$host.';dbname='.$applicationDbName, $username, $password);
        $neededConfigs = Config::getTestConfigs();
        foreach($neededConfigs as $name => $value){
            if($value === null){ // value should be taken from application DB
                $query = 'SELECT `value` FROM `Zf_configuration` WHERE `name` = ?';
                $stmt = $pdo->prepare($query);
                $stmt->execute([$name]);
                $appVal = $stmt->fetchColumn();
                if($appVal !== false){
                    $neededConfigs[$name] = $appVal;
                }
            }
        }
        return $neededConfigs;
    }

    /**
     * Retrieves the configuration of the currently loaded T5
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    private function getCurrentConfiguration(): array
    {
        $this->io->note('Copying config-values from current database');
        $configModel = new \editor_Models_Config();
        $neededConfigs = Config::getApplicationConfigs();
        foreach($neededConfigs as $name => $value){
            if($value === null){ // value should be taken from current application DB
                $neededConfigs[$name] = $configModel->getCurrentValue($name);
            }
        }
        return $neededConfigs;
    }

    /**
     * Writes the Plugins marked as test-relevant to the database
     * @throws \Zend_Exception
     */
    private function initPlugins(): void
    {
        /** @var \ZfExtended_Plugin_Manager $pluginmanager */
        $pluginmanager = \Zend_Registry::get('PluginManager');
        $pluginmanager->activateTestRelevantOnly();
    }

    /**
     * ABANDONED CONCEPT: copy db-configs to ini-file ... code may be useful some day
     * @param string $iniFilecontent
     * @return int
     * @throws \Zend_Db_Statement_Exception
     */
    private function appendConfigsToIniFileContent(string &$iniFilecontent): void
    {
        $written = 0;
        $missing = 0;
        // now write the values from the DB to the installation.ini
        $config = new \editor_Models_Config();
        foreach(Config::CONFIGS as $name => $value){
            if($value === null){ // value should be taken from existing config
                $dbValue = $config->getCurrentValue($name);
                if($dbValue === null || $dbValue === ''){
                    $iniFilecontent .= '; '.$name.' = ? TODO: not found in application DB, set manually'."\n"; // value not found: user needs to take action
                    $missing++;
                } else {
                    if($dbValue !== 'true' && $dbValue !== 'false' && !ctype_digit($dbValue)){
                        $dbValue = str_contains($dbValue, '"') ? '\''.str_replace('\'', '\\\'', $dbValue).'\'' : '"'.$dbValue.'"';
                    }
                    $iniFilecontent .= $name.' = '.$dbValue."\n";
                    $written++;
                }
            }
        }
        $this->io->note($written.' configs have been added from the configuration.');
        if($missing > 0){
            $this->io->warning($missing.' configs have not been found in the application DB. Please set them manually!');
        }
    }
}
