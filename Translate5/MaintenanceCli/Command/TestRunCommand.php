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

class TestRunCommand extends Translate5AbstractTestCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:run';
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Runs a single test.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Runs a single API-test.');

        $this->addArgument('test',
            InputArgument::REQUIRED,
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
            'recreate-database',
            'r',
            InputOption::VALUE_NONE,
            'Use this option to recreate the test database before running the test.');

        $this->addOption(
            'skip-pretests',
            's',
            InputOption::VALUE_NONE,
            'Use this option to skip testing the environment like test-users, worker-state, etc. preceding running the given test/suite. This reduces the API-requests to a single appstate and a single login request before the test runs.');

        parent::configure();
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);

        $test = trim($this->input->getArgument('test'), '.');
        $testPath = null;
        if(!empty($test)) {
            $testPath = empty(pathinfo($test, PATHINFO_EXTENSION)) ? $test.'.php' : $test;
            $testPath = $this->normalizeSingleTestPath($testPath);
            if(!file_exists($testPath)) {
                throw new \RuntimeException('The given Test does not exist: '.$test);
            }
        }
        // capturing options
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
        } else {
            putenv('DO_CAPTURE=0');
        }
        // test-dev option to skip pretests
        if($this->input->getOption('skip-pretests')){
            putenv('SKIP_PRETESTS=1');
        }

        if($this->initTestEnvironment('test', true, $this->input->getOption('recreate-database'))){
            $this->startApiTest($testPath);
        }
        return 0;
    }
}
