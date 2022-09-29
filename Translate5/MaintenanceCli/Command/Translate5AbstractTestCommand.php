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
     * Starts the unit test for a single test, the suite or all tests
     * @param string|null $testPath
     * @param string|null $testSuite
     * @return int
     * @throws \PHPUnit\TextUI\Exception
     */
    protected function startApiTest(string $testPath = null, string $testSuite = null) : int
    {
        $stopOnError = '';
        $stopOnFailure = '';
        $suiteOption = '';
        $testPathOrSuite = '';

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
            $testPathOrSuite = $testPath;
        } else if($testSuite !== null){
            $suiteOption = '--testsuite';
            $testPathOrSuite = $testSuite;
            putenv('DO_CAPTURE=0');
        } else {
            putenv('DO_CAPTURE=0');
            $testPathOrSuite = 'application';
        }

        $assembly = [
            'phpunit',
            '--colors',
            '--verbose',
            $stopOnError,
            $stopOnFailure,
            '--testdox-text',
            'last-test-result.txt',
            '--cache-result-file',
            '.phpunit.result.cache',
            '--bootstrap',
            self::RELATIVE_TEST_ROOT.'bootstrap.php',
            $suiteOption,
            $testPathOrSuite
        ];
        // start PHPUnit with neccessary options
        $command = new \PHPUnit\TextUI\Command();
        $command->run($assembly);
        $this->io->success('Last test result stored in TEST_ROOT/last-test-result.txt');

        return 0;
    }

    /**
     * Overwritten to initialize T5 with the [test] section in the ini files
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     */
    protected function initTranslate5()
    {
        $this->translate5 = new Application();
        $this->translate5->init('test'); // crucial: use test-environment to start the test
    }

    /**
     * Erases the current test-DB and creates it from scratch
     * @return void
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     */
    protected function reInitDatabase(): bool
    {
        $testDbExists = true;
        // Somehow dirty but we must initialize the app anyway ...
        try {
            $translate5 = new Application();
            $translate5->init('test'); // crucial: use test-environment to get the test-configurations
        } catch (\Throwable $e){
            if(str_contains($e->getMessage(), 'Unknown database')) {
                $testDbExists = false;
            } else {
                // other Problem, cancel
                die($e->getMessage()."\n\n".$e->getTraceAsString());
            }
        }
        // evaluate database params
        $baseIndex = \ZfExtended_BaseIndex::getInstance();
        $config = $baseIndex->initApplication()->getOption('resources');
        $applicationDbName = $config['db']['applicationDbName']; // we need the application db-name from a seperate value (created with the test:addinisection Command) as the db-params are overridden
        $config = $config['db']['params'];
        $pdo = new \PDO('mysql:host='.$config['host'], $config['username'], $config['password']);

        // check, if configured test-db meets our expectaions
        if($config['dbname'] !== Config::DATABASE_NAME){
            $this->io->error('The configured test database in installation.ini [test:application] must be \''.Config::DATABASE_NAME.'\'!');
            return false;
        }

        // drop an existing DB
        if($testDbExists){
            try {
                $pdo->query('DROP DATABASE '.$config['dbname'].';');
                $this->io->info('Dropped database '.$config['dbname']);
            }
            catch (\PDOException $e){
                $this->io->error($e->getMessage()."\n\n".$e->getTraceAsString());
                return false;
            }
        }
        // now create DB from scratch
        //default character set utf8mb4 collate utf8mb4_unicode_ci
        $sql = 'create database %s default character set utf8mb4 collate utf8mb4_unicode_ci';

        // we create the database first - we have to use raw PDO since Zend_Db needs a database...
        $pdo->query(sprintf($sql, $config['dbname']));

        $updater = new \ZfExtended_Models_Installer_DbUpdater();
        //add the test SQL path
        $updater->addAdditonalSqlPath(APPLICATION_PATH.'/modules/editor/testcases/database/');

        //init DB
        if ($updater->initDb() && $updater->importAll() && !$updater->hasErrors()) {
            // encrypt test-user passworts
            \editor_Utils::initDemoAndTestUserPasswords();
            // add needed plugins
            $this->initPlugins();
            // add the needed configs
            $configs = $this->getApplicationConfiguration($applicationDbName, $config['host'], $config['username'], $config['password']);
            $this->initConfiguration($configs);

            return true;
        }
        $updater->hasErrors() && $this->io->error($updater->getErrors());
        $updater->hasWarnings() && $this->io->warning($updater->getWarnings());
        return false;
    }

    /**
     * Removes the testdata-directory contents
     */
    protected function reInitDataDirectory(): void
    {
        $dir = APPLICATION_ROOT.'/'.Config::DATA_DIRECTORY;
        if(is_dir($dir)){
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        } else {
            mkdir($dir, 0777, true);
        }
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
        $this->io->info('Copying config-values from database \''.$applicationDbName.'\'');
        $pdo = new \PDO('mysql:host='.$host.';dbname='.$applicationDbName, $username, $password);
        $neededConfigs = Config::CONFIGS;
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
     * Writes the Plugins marked as test-relevant to the database
     * @throws \Zend_Exception
     */
    private function initPlugins(): void
    {
        /** @var \ZfExtended_Plugin_Manager $pluginmanager */
        $pluginmanager = \Zend_Registry::get('PluginManager');
        $pluginmanager->activateTestRelevantOnly();
    }
}
