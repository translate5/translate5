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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\Test\Config;

class TestRunAllCommand extends Translate5AbstractTestCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:runall';
    
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Runs all.')
        
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Runs all API-tests.');

        $this->addOption(
            'skip-database-reset',
            's',
            InputOption::VALUE_NONE,
            'Will skip the database-reset before running the tests.');

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

        if($this->input->getOption('skip-database-reset')){

            // skip database reinit (we will not check, if the DB exists ...)
            $this->io->info('Will work on current DB \''.Config::DATABASE_NAME.'\'');

        } else {

            // reinitialize the database & data directory
            if(!$this->reInitDatabase()){
                return 0;
            }
            $this->reInitDataDirectory();
        }
        $this->initTranslate5();

        return $this->startApiTest();
    }
}
