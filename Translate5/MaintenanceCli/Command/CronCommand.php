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

use MittagQI\Translate5\Tools\Cronjobs;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use Zend_Registry;

class CronCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'cron';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Trigger the internal cron jobs')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Trigger the internal cron jobs - by default the periodical one, daily by calling with --daily');

        $this->addOption(
            name: 'daily',
            mode: InputOption::VALUE_NONE,
            description: 'Sort by row count instead of size.'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $cron = \ZfExtended_Factory::get(Cronjobs::class,[
            Zend_Registry::get('bootstrap')
        ]);
        if ($input->getOption('daily')) {
            $this->io->success('Daily jobs triggered!');
            $cron->daily();
        } else {
            $cron->periodical();
            $this->io->success('Periodical jobs triggered!');
        }


        return self::SUCCESS;
    }
}
