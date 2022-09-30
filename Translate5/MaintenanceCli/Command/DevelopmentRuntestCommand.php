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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

class DevelopmentRuntestCommand extends Translate5AbstractCommand
{
    const RELATIVE_TEST_ROOT = 'application/modules/editor/testcases/';
    const RELATIVE_TEST_DIR = self::RELATIVE_TEST_ROOT.'editorAPI/';

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:runtest';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Runs all or a given test a new API test.')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Runs all or a given test a new API test.');

        $this->addArgument('test',
            InputArgument::OPTIONAL,
            'Filename of the Test to be called (optionally with relative path so that tab completion can be used to find the test)'
        );

        $this->addOption(
            'capture',
            'c',
            InputOption::VALUE_NONE,
            'Use this option to re-capture the test data of a test. Probably not all tests are adopted yet to support this switch.');

        $this->addOption(
            'legacy-segment',
            'l',
            InputOption::VALUE_NONE,
            'Use this option when re-capturing segment test data to use the old order of the segment data. Comparing the changes then with git diff is easier. Then commit and re-run the test without this option, then finally commit the result. Only usable with -c');

        $this->addOption(
            'legacy-json',
            'j',
            InputOption::VALUE_NONE,
            'Use this option when re-capturing test data to use the old json style. Comparing the changes then with git diff is easier. Then commit and re-run the test without this option, then finally commit the result. Only usable with -c');

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

//        $this->addOption(
//            'name',
//            'N',
//            InputOption::VALUE_REQUIRED,
//            'Force a name (must end with Test!) instead of getting it from the branch.');
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //errors in the space of the testsuite should boil out directly
        //ini_set('error_log', '/dev/stderr');
        $this->initInputOutput($input, $output);

        $this->initTranslate5();

        $testGiven = $this->input->getArgument('test');
        if(!empty($testGiven)) {
            if($testGiven  === basename($testGiven)) {
                $testGiven = self::RELATIVE_TEST_DIR.$testGiven;
            }
            if(!file_exists($testGiven)) {
                throw new \RuntimeException('Given Test does not exist: ' . $testGiven);
            }
        }

        //using environment variables here keeps compatibility with plain apiTest.sh call
        putenv('APPLICATION_ROOT='.__DIR__);

        if($this->input->getOption('xdebug')){
            putenv('XDEBUG_ENABLE=1');
        }

        if($this->input->getOption('keep-data')){
            putenv('KEEP_DATA=1');
        }

        if($this->input->getOption('capture')){
            putenv('DO_CAPTURE=1');
            $this->io->warning([
                'Check the modified test data files thoroughly and conscientiously with git diff,',
                'if the performed changes are really desired and correct right now!',
                'Sloppy checking or "just comitting the changes" may make the test useless!',
            ]);
            if(!$this->io->confirm('Yes, I will check the modified data files thoroughly!')){
                putenv('DO_CAPTURE=0');
            }
            if($this->input->getOption('legacy-segment')){
                putenv('LEGACY_DATA=1');
            }
            if($this->input->getOption('legacy-json')){
                putenv('LEGACY_JSON=1');
            }
        }
        else {
            putenv('DO_CAPTURE=0');
        }

        $stopOnError = '';
        $stopOnFailure = '';

        if($this->input->getOption('stop-on-error')){
            $stopOnError = '--stop-on-error';
        }
        if($this->input->getOption('stop-on-failure')){
            $stopOnFailure = '--stop-on-failure';
        }

        $command = new \PHPUnit\TextUI\Command();
        $command->run([
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
            $testGiven ?? 'application' // PHP Unit searches recursivly for files named *Test.php
        ]);
	$this->io->success('Last test result stored in TEST_ROOT/last-test-result.txt');

        return 0;
    }

    /**
     * Overwritten to check if DB exists, if not create it
     * @return void
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     */
    protected function initTranslate5()
    {

        // SAMPLE CODE TO DROP DB IN HERE FOR TESTING PURPOSES ONLY
        // WE EXPLICITLY DECIDED NOT TO AUTOMATE THAT, but that its the devs
        // responsibility to provide an empty DB
//        try {
//            $pdox = new \PDO('mysql:host=127.0.0.1', 'root', 'XXX');
//            $pdox->query('drop database translate5;');
//        }
//        catch (\PDOException $e){
//            error_log($e);
//        }

        try {
            parent::initTranslate5();

            $test = $this->input->getArgument('test');
            //if a single test was given, we run that on the current DB
            if(!empty($test)) {
                return;
            }
            //if the whole testsuite is running, on an existing DB, we consider that as an error:
            $config = \Zend_Registry::get('config');
            $db = $config->resources->db->params->dbname;
            $this->io->error([
                'The configured database "'.$db.'" exists!',
                'Since the run of the whole testsuite wants to create a clean one, drop it, call: ',
                '  mysql -h localhost -u root -p -e "drop database '.$db.';"',
                'or change the DB in the installation.ini to a non existing one (but must be still accessible by the user)'."\n".
                'or run just a single test',
            ]);
            die(1);
        }
        catch (\Zend_Db_Adapter_Exception $e) {
            if(! str_contains($e->getMessage(), 'Unknown database')) {
                throw $e;
            }
        }
        $this->initDatabase();
    }

    /**
     * @return void
     * @throws \Zend_Db_Exception
     * @throws \Zend_Exception
     */
    private function initDatabase(): void
    {
//start application - without bootstrapping - is loading the needed configurations only
        $baseIndex = \ZfExtended_BaseIndex::getInstance();
        $config = $baseIndex->initApplication()->getOption('resources');
        $config = $config['db']['params'];

        //default character set utf8mb4 collate utf8mb4_unicode_ci
        $sql = 'create database %s default character set utf8mb4 collate utf8mb4_unicode_ci';

        // we create the database first - we have to use raw PDO since Zend_Db needs a database...
        $pdo = new \PDO('mysql:host=' . $config['host'], $config['username'], $config['password']);
        $pdo->query(sprintf($sql, $config['dbname']));

        $updater = new \ZfExtended_Models_Installer_DbUpdater();
        //add the test SQL path
        $updater->addAdditonalSqlPath(APPLICATION_PATH . '/modules/editor/testcases/database/');

        //init DB
        if ($updater->initDb() && $updater->importAll() && !$updater->hasErrors()) {
            \editor_Utils::initDemoAndTestUserPasswords();
            parent::initTranslate5(); //re-init application
            $this->initConfiguration();
            $this->initPlugins();
            return;
        }
        $updater->hasErrors() && $this->io->error($updater->getErrors());
        $updater->hasWarnings() && $this->io->warning($updater->getWarnings());
        die(1);
    }

    /**
     * The here added system configuration (service URLS) should match for most setups, and can be overwritten in installation.ini
     *
     * Before adding an application config here:
     *   1. try to set the value via task-config.ini specific for that test
     *   2. If that is not possible, add it here but ensure that the test which needs it, checks if the value is set before
     * The here added application configuration must be finally valid on all machines!
     *
     * @return void
     * @throws \Zend_Exception
     */
    private function initConfiguration(): void
    {
        //see method header!
        // values were just taken over from master instance, not tested if they really must be set to that value!
        $testConfig = [
            'runtimeOptions.customers.anonymizeUsers' => 1,
            'runtimeOptions.editor.notification.userListColumns' => '["surName","firstName","email","role","state","deadlineDate"]',
            'runtimeOptions.import.enableSourceEditing' => 1,
            'runtimeOptions.import.sdlxliff.importComments' => 1,
            'runtimeOptions.import.xlf.preserveWhitespace' => 0,
            'runtimeOptions.InstantTranslate.minMatchRateBorder' => 70,
            'runtimeOptions.plugins.Okapi.server' => '{"okapi-longhorn":"http://localhost:8080/okapi-longhorn/","okapi-longhorn-143":"http://localhost:8080/okapi-longhorn_143/"}',
            'runtimeOptions.plugins.Okapi.serverUsed' => 'okapi-longhorn-143',
            'runtimeOptions.plugins.SpellCheck.languagetool.url.gui' => 'http://localhost:8081/v2',
            'runtimeOptions.plugins.SpellCheck.liveCheckOnEditing' => 1,
            'runtimeOptions.plugins.VisualReview.directPublicAccess' => 1,
            'runtimeOptions.tbx.termLabelMap' => '{"legalTerm": "permitted", "admittedTerm": "permitted", "preferredTerm": "preferred", "regulatedTerm": "permitted", "deprecatedTerm": "forbidden", "supersededTerm": "forbidden", "standardizedTerm": "permitted"}',
        ];

        //the following configs must be set in installation.ini otherwise we ask to annoy the developers to set them
        $localConfig = [
            'runtimeOptions.plugins.VisualReview.shellCommandPdf2Html',
            'runtimeOptions.server.name',
            'runtimeOptions.server.protocol',
            'runtimeOptions.plugins.DeepL.authkey',
            'runtimeOptions.plugins.FrontEndMessageBus.socketServer.httpHost',
        ];

        $config = new \editor_Models_Config();

        //set predefined values
        foreach($testConfig as $name => $value) {
            $config->update($name, $value);
        }

        //annoy the developer to set some local values in the ini
        foreach($localConfig as $localConf) {
            $config->loadByName($localConf);
            if(!$config->hasIniEntry()) {
                $value = $this->io->ask('Please provide a local value for "'.$localConf.'" or set it in the installation.ini');
                $config->update($localConf, $value);
            }
        }
    }

    private function initPlugins()
    {
        /** @var \ZfExtended_Plugin_Manager $pluginmanager */
        $pluginmanager = \Zend_Registry::get('PluginManager');
        $pluginmanager->activateTestRelevantOnly();
    }
}
