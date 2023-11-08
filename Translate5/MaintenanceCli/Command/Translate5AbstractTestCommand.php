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

use MittagQI\Translate5\Test\TestConfiguration;
use PDO;
use Symfony\Component\Console\Input\InputOption;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Zend_Registry;
use ZfExtended_Models_Installer_DbUpdater;
use ZfExtended_Plugin_Manager;

abstract class Translate5AbstractTestCommand extends Translate5AbstractCommand
{
    const RELATIVE_TEST_ROOT = 'application/modules/editor/testcases/';

    const RELATIVE_TEST_DIR = self::RELATIVE_TEST_ROOT . 'editorAPI/';

    /**
     * Enables the -m option to let the current tests to be run as master-tests
     * @var bool
     */
    protected static bool $canMimicMasterTest = true;

    /**
     * Enables the -k option to let the current test to not cleanup resources & generated files
     * @var bool
     */
    protected static bool $canKeepTestData = true;

    /**
     * Enables the -s option to skip the passed tests from running the suite
     * @var bool
     */
    protected static bool $canSkipTests = true;

    /**
     * Some configs need the base-url
     * To not fetch multiple times, we cache it
     * @var string
     */
    protected static string $applicationBaseUrl;

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
            'stop-on-error',
            'e',
            InputOption::VALUE_NONE,
            'Leads to the testsuite stopping on the first error (not failure!).');

        $this->addOption(
            'stop-on-failure',
            'f',
            InputOption::VALUE_NONE,
            'Leads to the testsuite stopping on the first failure (not error!).');

        if (static::$canKeepTestData) {
            $this->addOption(
                'keep-data',
                'k',
                InputOption::VALUE_NONE,
                'Prevents that the test data (tasks, etc) is cleaned up after the test.'
                . ' Useful for debugging a test. Must be implemented in the test itself,'
                . ' so not all tests support that flag yet.');
        }

        if (static::$canMimicMasterTest) {
            $this->addOption(
                'master-test',
                'm',
                InputOption::VALUE_NONE,
                'Leads to the testsuite running in master mode.'
                . ' Be aware that this might create costs for using paid external APIs.');
        }

        if (static::$canSkipTests) {
            $this->addOption(
                'skip',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Excludes the given API-test from running a suite.'
                . ' Provide only the pure classname without namespace.'
                . ' Note, that Unit-tests can not be skipped.');
        }
    }

    /**
     * Sets the environment and initializes T5 for the given environment
     * @param string $environment
     * @param bool $reinitMissingDb
     * @param bool $forceRecreation
     * @return bool
     * @throws \Zend_Exception
     */
    protected function initTestEnvironment(string $environment, bool $reinitMissingDb, bool $forceRecreation = false): bool
    {
        // we reinit the test-db if it does not exist or a recreation is forced
        // this also checks if the instance is set up for tests / API-test are allowed
        if ($environment === 'test' && $reinitMissingDb) {
            if ($this->reInitTestDatabase($forceRecreation)) {
                // Crucial: this triggrs the test-sections in the ini-files to be used in the test-bootstrap
                putenv('APPLICATION_ENV=test');
                return true;
            }
            return false;
        }
        // init T5 for the test environment in which the tests have to be run - if wanted
        // this also checks, if the test-environment seems configured in installation.ini
        if ($environment === 'test') {
            $this->initTranslate5($environment);
            if ($this->checkApiTestsAllowed()) {
                // Crucial: this triggrs the test-sections in the ini-files to be used in the test-bootstrap
                putenv('APPLICATION_ENV=test');
            } else {
                return false;
            }
        } else {
            // for running tests on the app database
            $this->initTranslate5();
            return $this->checkApiTestsAllowed();
        }
        return true;
    }

    /**
     * Checks, if the environment is set up to allow API tests
     * @return bool
     */
    protected function checkApiTestsAllowed(): bool
    {
        try {
            $config = Zend_Registry::get('config');
            if ($config?->testSettings?->testsAllowed !== 1) {
                $this->io->error('This installation seems not to be set up for API tests [].');
                return false;
            }
            return true;
        } catch (\Throwable) {
            $this->io->error('Test-config could not be loaded.');
            return false;
        }
    }

    /**
     * Starts the unit test for a single test, a suite or all tests
     * @param string|null $testPath
     * @param string|null $testSuite
     * @throws \PHPUnit\TextUI\Exception
     */
    protected function startApiTest(string $testPath = null, string $testSuite = null)
    {
        // the PHPunit configuration used for all tests and defining the bootstrapper
        $configurationFilePath = APPLICATION_ROOT . '/' . self::RELATIVE_TEST_ROOT . 'phpunit.xml';
        $verbose = '--verbose'; // '--debug'
        $stopOnError = '';
        $stopOnFailure = '';
        $testPathOrDir = '';
        $suiteOption = '';
        $suiteFile = '';

        // environment stuff needed for all tests (using environment variables here keeps compatibility with plain apiTest.sh call)
        putenv('APPLICATION_ROOT=' . __DIR__);

        if ($this->input->getOption('xdebug')) {
            putenv('XDEBUG_ENABLE=1');
        }

        // keeping the data only make sense for a single test
        if (static::$canKeepTestData && $testPath !== null && $this->input->getOption('keep-data')) {
            putenv('KEEP_DATA=1');
        }

        if (static::$canMimicMasterTest && $this->input->getOption('master-test')) {
            putenv('MASTER_TEST=1');
        }

        // skipping tests makes only sense for suites/all
        if (static::$canSkipTests && !empty($this->input->getOption('skip'))) {
            putenv('SKIP_TESTS=' . implode(',', $this->input->getOption('skip')));
        }

        // command options usable for all tests
        if ($this->input->getOption('stop-on-error')) {
            $stopOnError = '--stop-on-error';
        }
        if ($this->input->getOption('stop-on-failure')) {
            $stopOnFailure = '--stop-on-failure';
        }

        // test / suite / all specific stuff. Note that DO_CAPTURE is defined in the concrete command for a single test
        if ($testPath !== null) {

            $testPathOrDir = $testPath;
            $this->io->note('Running test \'' . basename($testPath) . '\'');
            putenv('IS_SUITE=0');

        } else if ($testSuite !== null) {

            $this->io->note('Running suite \'' . $testSuite . '\'');
            // defining the suite to use
            $suiteOption = '--testsuite';
            $suiteFile = $testSuite;
            // must not be set when using a suite, otherwise the suite will never be triggered ...
            $testPathOrDir = '';
            putenv('DO_CAPTURE=0');
            putenv('IS_SUITE=1');

        } else {

            putenv('DO_CAPTURE=0');
            putenv('IS_SUITE=1');
            $testPathOrDir = 'application';
        }

        $assembly = [
            'phpunit',
            $verbose,
            $stopOnError,
            $stopOnFailure,
            '--cache-result-file='.APPLICATION_ROOT.'/data/cache/.phpunit.result.cache',
            '--testdox-text='.APPLICATION_ROOT.'/data/tmp/last-test-result.txt',
            '--configuration',
            $configurationFilePath,
            $suiteOption,
            $suiteFile,
            $testPathOrDir
        ];

        // die(implode(' ', $assembly)."\n");

        // start PHPUnit with neccessary options
        $command = new \PHPUnit\TextUI\Command();
        $command->run($assembly);
    }

    /**
     * Convenience-function to make pathes pinting to plugin-folders easier usable
     * @param string $testPath
     * @return string
     */
    protected function normalizeSingleTestPath(string $testPath): string
    {
        // just filename
        if($testPath === basename($testPath)) {
            return self::RELATIVE_TEST_DIR.$testPath;
        }
        // path in plugins
        $tmpPath = str_replace('/PrivatePlugins/', '/Plugins/', '/'.ltrim($testPath, './'));
        if(str_contains($tmpPath, '/Plugins/')){
            $parts = explode('/Plugins/', $tmpPath);
            return 'application/modules/editor/Plugins/'.array_pop($parts);
        }
        // otherwise
        return $testPath;
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
            $document = (new \PHPUnit\Util\Xml\Loader)->loadFile(self::RELATIVE_TEST_ROOT . 'phpunit.xml', false, true, true);
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
                $name = (string)$element->getAttribute('name');
                if (!empty($name)) {
                    $suiteNames[] = $name;
                }
            }
            if (count($suiteNames) === 0) {
                die('No suites defined in "' . self::RELATIVE_TEST_ROOT . 'phpunit.xml"' . "\n"); // since this is called on command-creation we simply die...
            }
            return $suiteNames;
        } catch (\Throwable) {
            die('File "' . self::RELATIVE_TEST_ROOT . 'phpunit.xml" is missing.' . "\n"); // since this is called on command-creation we simply die...
        }
    }

    /**
     * Erases the current test-DB and creates it from scratch. Returns the success of the action
     * @param bool $forceRecreation : if not set the DB will only be recreated if it does not exist
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
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Unknown database')) {
                $testDbExists = false;
                // we must make sure here es well, that this instance is set up as a test-system
                $config = Zend_Registry::get('config');
                if ($config?->testSettings?->testsAllowed !== 1) {
                    $this->io->error('This installation seems not to be set up for API tests.');
                    return false;
                }
            } else {
                // other Problem, cancel
                $this->io->error($e->getMessage() . "\n\n" . $e->getTraceAsString());
                return false;
            }
        }
        // in some cases it's only wanted to create the DB if it does not exist
        $config = Zend_Registry::get('config');
        if ($testDbExists && !$forceRecreation) {
            $this->io->note('Database \'' . $config->resources->db->params->dbname . '\' already exists');
            return true;
        }
        $this->io->note('Recreate database \'' . $config->resources->db->params->dbname . '\'');

        try {
            // evaluate database params
            $baseIndex = \ZfExtended_BaseIndex::getInstance();
            $application = $baseIndex->initApplication();
            $config = $application->getOption('resources');
            $config = $config['db']['params'];
            $testSettings = $application->getOption('testSettings');
            if (empty($testSettings) || $testSettings['testsAllowed'] !== 1) {
                $this->io->error('This installation seems not to be set up for API tests.');
                return false;
            }

            $applicationDbName = $testSettings['applicationDbName']; // we need the application db-name from a seperate value (created with the test:addinisection Command) as the db-params are overridden
            // check, if configured test-db meets our expectaions
            if (empty($applicationDbName)) {
                $this->io->error('The configured application database is missing or wrongly set in installation.ini!');
                return false;
            }
            if ($applicationDbName === $config['dbname']) {
                $this->io->error('The configured test database in installation.ini must not be the application database!');
                return false;
            }
            $testDbName = TestConfiguration::createTestDatabaseName($applicationDbName);
            if ($config['dbname'] !== $testDbName) {
                $this->io->error('The configured test database in installation.ini [test:application] must be \'' . $testDbName . '\'!');
                return false;
            }

            // retrieve the needed configs from the application DB
            $appConfigs = $this->getApplicationConfiguration($applicationDbName, $config['host'], $config['username'], $config['password']);

            // delete (if needed) and recreate DB. recreate tables
            if (
            $this->recreateDatabase($config['host'], $config['username'], $config['password'], $config['dbname'], $testDbExists)
                && $this->recreateTables($appConfigs, 'test')
            ) {
                $this->io->note('Successfully recreated database \'' . $config['dbname'] . '\'');
                // cleanup/reinitialize the test data dirs
                $this->reInitDataDirectory(TestConfiguration::DATA_DIRECTORY);
                return true;
            }
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage() . "\n\n" . $e->getTraceAsString());
        }
        return false;
    }

    /**
     * Erases the current application-DB and creates it from scratch
     * This must be confirmed by the user or the correct database-name must be passed
     * @param string|null $databaseName
     * @return bool
     */
    protected function reInitApplicationDatabase(string $databaseName = null): bool
    {
        try {
            // evaluate database params
            $translate5 = new Application();
            $translate5->init(); // for recreating the app database we use the normal environment
            $baseIndex = \ZfExtended_BaseIndex::getInstance();
            $config = $baseIndex->initApplication()->getOption('resources');
            $config = $config['db']['params'];
            // check given db-name or prompt for one
            if ($databaseName !== null && $config['dbname'] !== $databaseName) {
                $this->io->error('The passed database-name "' . $databaseName . '" does not match te application database "' . $config['dbname'] . '".');
                return false;
            } else if ($databaseName === null && $this->io->ask('To really recreate the database "' . $config['dbname'] . '" type it\'s name to confirm') !== $config['dbname']) {
                $this->io->error('The given name does not match "' . $config['dbname'] . '"...');
                return false;
            }
            $this->io->note('Recreate database \'' . $config['dbname'] . '\'');

            // get current configs first
            $configs = $this->getCurrentConfiguration();

            // delete and recreate DB. recreate tables
            if (
                $this->recreateDatabase($config['host'], $config['username'], $config['password'], $config['dbname'], true)
                && $this->recreateTables($configs, 'application')
            ) {
                $this->io->note('Successfully recreated database \'' . $config['dbname'] . '\'');
                // cleanup/reinitialize the "normal" data dirs
                $this->reInitDataDirectory('data');
                return true;
            }
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage() . "\n\n" . $e->getTraceAsString());
        }
        return false;
    }

    /**
     * Cleans up the user-data when recreating the database
     */
    private function reInitDataDirectory(string $dataDirectory): void
    {
        if(PHP_OS_FAMILY != 'Windows'){
            $info = $this->fetchOwnerAndGroup('data'); // we take the owner and group of the /data dir as a reference, default is www-data for cases, where that dir is missing
        }
        if (!is_dir($dataDirectory)) {
            mkdir($dataDirectory, 0777, true);
            if (PHP_OS_FAMILY != 'Windows') { // TODO FIXME: on windows this may lead to an unusable installation if called with elevated rights
                chown($dataDirectory, $info->owner);
                chgrp($dataDirectory, $info->group);
            }
        }
        foreach (TestConfiguration::getUserDataFolders() as $folder) {
            $userDir = $dataDirectory . '/' . $folder;
            if (is_dir($userDir)) {
                \ZfExtended_Utils::recursiveDelete($userDir, ['gitignore'], true, false);
            } else {
                mkdir($userDir, 0777);
                if (PHP_OS_FAMILY != 'Windows') { // TODO FIXME: on windows this may lead to an unusable installation if called with elevated rights
                    chown($userDir, $info->owner);
                    chgrp($userDir, $info->group);
                }
            }
        }
        $this->io->note('Successfully cleaned user-data directory \'/' . $dataDirectory . '\'');
    }

    /**
     * Helper to retrieve owner & group of an directory. defaults to www-data for both, if no dir given or evaluation not successful
     * @param string|null $directory
     * @return \stdClass with "props" owner and "group"
     */
    private function fetchOwnerAndGroup(string $directory = null): \stdClass
    {
        $info = new \stdClass;
        $info->owner = 'www-data';
        $info->group = 'www-data';
        if ($directory && is_dir($directory)) {
            $oinfo = @posix_getpwuid(@fileowner($directory));
            $ginfo = @posix_getgrgid(@filegroup($directory));
            if ($oinfo !== false && $ginfo !== false) {
                $info->owner = $oinfo['name'];
                $info->group = $ginfo['name'];
            }
        }
        return $info;
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
    private function recreateDatabase(string $host, string $username, string $password, string $dbname, bool $exists = false): bool
    {
        $updater = new ZfExtended_Models_Installer_DbUpdater();

        try {

            // Get DbConfig instance
            $dbConfig = \ZfExtended_Factory
                ::get('ZfExtended_Models_Installer_DbConfig')
                ->initFromArray([
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'dbname' => $dbname
                ]);

            $updater->createDatabase($dbConfig, $exists);
            if ($exists) {
                $this->io->note('Dropped database ' . $dbname);
            }
        } catch (\PDOException $e) {
            $this->io->error($e->getMessage() . "\n\n" . $e->getTraceAsString());
            return false;
        }

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
        $updater = new ZfExtended_Models_Installer_DbUpdater();
        //add the test SQL path
        $updater->addAdditonalSqlPath(APPLICATION_PATH . '/modules/editor/testcases/database/');
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
        if ($updater->hasErrors()) {
            $this->io->error($updater->getErrors());
        }
        if ($updater->hasWarnings()) {
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
        foreach ($neededConfigs as $name => $value) {
            if ($value !== null) {
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
        $this->io->note('Copying config-values from database \'' . $applicationDbName . '\'');
        $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $applicationDbName, $username, $password);
        $neededConfigs = TestConfiguration::getTestConfigs();
        foreach ($neededConfigs as $name => $value) {
            // value should be taken from application DB if defined as null
            if ($value === null) {
                $appVal = $this->fetchApplicationConfigurationVal($pdo, $name);
                if ($appVal !== false) {
                    $neededConfigs[$name] = $appVal;
                }

            // value needs to be complemented with base-url of current installation. This is e.g. needed, when fake-APIs are used
            } else if(str_contains($value, TestConfiguration::BASE_URL)){

                $baseUrl = $this->getApplicationBaseUrl($pdo);
                $neededConfigs[$name] = str_replace(TestConfiguration::BASE_URL, $baseUrl, $value);
            }
        }
        return $neededConfigs;
    }

    /**
     * Retrieves the base-URL from of the application config DB
     * @param PDO $pdo
     * @return string
     */
    private function getApplicationBaseUrl(PDO $pdo): string
    {
        if(!isset(static::$applicationBaseUrl)){
            static::$applicationBaseUrl =
                $this->fetchApplicationConfigurationVal($pdo, 'runtimeOptions.server.protocol')
                .$this->fetchApplicationConfigurationVal($pdo, 'runtimeOptions.server.name');
        }
        return static::$applicationBaseUrl;
    }

    /**
     * Retrieves a single config-value out of the application config DB
     * @param PDO $pdo
     * @return string
     */
    private function fetchApplicationConfigurationVal(PDO $pdo, string $name): ?string
    {
        $query = 'SELECT `value` FROM `Zf_configuration` WHERE `name` = ?';
        $stmt = $pdo->prepare($query);
        $stmt->execute([$name]);
        $appVal = $stmt->fetchColumn();
        if ($appVal !== false && $appVal !== null) {
            return (string)$appVal;
        }
        return null;
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
        $neededConfigs = TestConfiguration::getApplicationConfigs();
        foreach ($neededConfigs as $name => $value) {
            if ($value === null) { // value should be taken from current application DB
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
        /** @var ZfExtended_Plugin_Manager $pluginmanager */
        $pluginmanager = Zend_Registry::get('PluginManager');
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
        foreach (TestConfiguration::CONFIGS as $name => $value) {
            if ($value === null) { // value should be taken from existing config
                $dbValue = $config->getCurrentValue($name);
                if ($dbValue === null || $dbValue === '') {
                    $iniFilecontent .= '; ' . $name . ' = ? TODO: not found in application DB, set manually' . "\n"; // value not found: user needs to take action
                    $missing++;
                } else {
                    if ($dbValue !== 'true' && $dbValue !== 'false' && !ctype_digit($dbValue)) {
                        $dbValue = str_contains($dbValue, '"') ? '\'' . str_replace('\'', '\\\'', $dbValue) . '\'' : '"' . $dbValue . '"';
                    }
                    $iniFilecontent .= $name . ' = ' . $dbValue . "\n";
                    $written++;
                }
            }
        }
        $this->io->note($written . ' configs have been added from the configuration.');
        if ($missing > 0) {
            $this->io->warning($missing . ' configs have not been found in the application DB. Please set them manually!');
        }
    }
}
