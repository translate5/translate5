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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class WorkerCleanCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'worker:clean';
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Removes all done workers from the worker table')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Worker table clean up.');
        
        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Removes defunc workers.');

        $this->addOption(
            'running',
            'r',
            InputOption::VALUE_NONE,
            'Removes the running workers - which makes sense if they were crashed but not set to defunct - keeps done without --all');
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
        
        $this->writeTitle('worker clean up');
        
        $worker = \ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker \ZfExtended_Models_Worker */

        $all = (bool) $this->input->getOption('all');
        $running = (bool) $this->input->getOption('running');

        if($running && !$this->io->confirm('Do you really want to delete the running workers? Are you familiar with the consequences?', false)) {
            return 0;
        }

        //delete running only
        if($running && !$all) {
            $states = [$worker::STATE_RUNNING];
        }
        else {
            //delete as usual done
            $states = [$worker::STATE_DONE];
            if($running) {
                //and running if requested
                $states[] = $worker::STATE_RUNNING;
            }
            if($all) {
                //and defunc if requested
                $states[] = $worker::STATE_DEFUNCT;
            }
        }
        $worker->clean($states);
        $this->io->success('Worker table cleaned - removed workers with state: '.join(', ', $states).' workers');
        return 0;
    }
}
