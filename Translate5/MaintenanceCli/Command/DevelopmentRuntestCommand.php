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
            $this->io->confirm('Yes, I will check the modified data files thoroughly!');
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

        $command = new \PHPUnit\TextUI\Command();
        $command->run([
            'phpunit',
            '--colors',
            '--verbose',
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
}
