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

class WorkerListCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'worker:list';
    
    /**
     * @var InputInterface
     */
    protected $input;
    
    /**
     * @var OutputInterface
     */
    protected $output;
    
    /**
     * @var SymfonyStyle
     */
    protected $io;
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Prints a list of current workers or details about one worker')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Prints a list of current workers.');
        
        $this->addArgument(
            'workerId',
            InputArgument::OPTIONAL,
            'Worker ID to show details for.'
        );
        
        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'List also done and defunc workers.');
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
        
        $this->writeTitle('worker list');
        
        $worker = \ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker \ZfExtended_Models_Worker */
        
        if($id = $this->input->getArgument('workerId')) {
            $worker->load($id);
            $data = (array) $worker->getDataObject();
            $this->io->horizontalTable(array_keys($data), [$data]);
            return 0;
        }
        
        $allWorker = $worker->loadAll();
        
        $headlines = [
            'id'        => 'DB id:',
            'parentId'  => 'DB par. id:',
            'state'     => 'state:',
            'pid'       => 'Process id:',
            'starttime' => 'Starttime:',
            'endtime'   => 'Endtime:',
            'taskGuid'  => 'TaskGuid:',
            'worker'    => 'Worker:',
        ];
        
        $resultNotListed = [];
        if($this->input->getOption('all')) {
            $statesToIgnore = [];
        }
        else {
            $statesToIgnore = [$worker::STATE_DEFUNCT, $worker::STATE_DONE];
        }
        
        $rows = [];
        foreach($allWorker as $worker) {
            if(in_array($worker['state'], $statesToIgnore)){
                settype($resultNotListed[$worker['state']], 'integer');
                $resultNotListed[$worker['state']]++;
                continue;
            }
            $row = [];
            foreach($headlines as $key => $title) {
                $row[] = $worker[$key];
            }
            $rows[] = $row;
        }
        
        $this->io->table($headlines, $rows);
        
        if(!empty($resultNotListed)) {
            $this->io->section('Not listed workers:');
            foreach($resultNotListed as $worker => $count) {
                $this->io->text($worker.' count '.$count);
            }
        }
        return 0;
    }
}
