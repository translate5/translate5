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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestApplicationRunCommand extends Translate5AbstractTestCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:apprun';
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Runs all tests for the application environment.')
        
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Runs all API-tests for the aplication environment and NOT the test-environment.');

        $this->addArgument('testorsuite',
            InputArgument::OPTIONAL,
            'Filename of the Test to be called (don\'t forget *.php) or name of a suite'
        );

        $this->addOption(
            'recreate-database',
            'r',
            InputOption::VALUE_NONE,
            'Use this option to recreate the application database. This will also clean the /data directory contents.');

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

        $testOrSuite = $this->input->getArgument('testorsuite');
        $extension = empty($testOrSuite) ? '' : strtolower(pathinfo($testOrSuite, PATHINFO_EXTENSION));
        $testPath = ($extension === 'php') ? $testOrSuite : null;
        $testSuite = ($extension === '' && !empty($testOrSuite)) ? $testOrSuite : null;

        // reinitialize the database & data directory if we should
        if($this->input->getOption('recreate-database')) {
            if (!$this->reInitApplicationDatabase()){
                return 0;
            }
            // cleanup he "normal" data dirs
            $this->reInitDataDirectory('data');
        }
        return $this->startApiTest($testPath, $testSuite, 'application');
    }
}
