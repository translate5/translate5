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

use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_Worker;

class WorkerListCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'worker:list';

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
            'List also done and defunc workers.'
        );

        $this->addOption(
            'summary',
            's',
            InputOption::VALUE_NONE,
            'List just a summary of worker counts grouped by state and worker type.'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @return int
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $this->writeTitle('worker list');

        $worker = ZfExtended_Factory::get('ZfExtended_Models_Worker');
        /* @var $worker ZfExtended_Models_Worker */

        if ($id = $this->input->getArgument('workerId')) {
            $worker->load($id);
            $data = (array) $worker->getDataObject();
            $this->io->horizontalTable(array_keys($data), [$data]);

            return 0;
        }

        $allWorker = $worker->loadAll();

        $headlines = [
            'id' => 'DB id:',
            'parentId' => 'DB par. id:',
            'state' => 'state:',
            'pid' => 'Process id:',
            'starttime' => 'Starttime:',
            'endtime' => 'Endtime:',
            'duration' => 'Duration:',
            'taskGuid' => 'TaskGuid:',
            'worker' => 'Worker:',
            'progress' => 'Progress:',
        ];

        $isSummary = $this->input->getOption('summary');

        $resultNotListed = [];
        if ($this->input->getOption('all') || $isSummary) {
            $statesToIgnore = [];
        } else {
            $statesToIgnore = [$worker::STATE_DEFUNCT, $worker::STATE_DONE];
        }

        $summary = [];

        $table = $this->io->createTable();
        $table->setHeaders($headlines);
        foreach ($allWorker as $workerRow) {
            if (in_array($workerRow['state'], $statesToIgnore)) {
                settype($resultNotListed[$workerRow['state']], 'integer');
                $resultNotListed[$workerRow['state']]++;

                continue;
            }

            if ($isSummary && $workerRow['state'] !== $worker::STATE_RUNNING) {
                $idx = $workerRow['worker'].'#'.$workerRow['state'];
                if (!array_key_exists($idx, $summary)) {
                    $summary[$idx] = 0;
                }
                $summary[$idx]++;
                continue;
            }

            //resort fields by headline order
            $row = [];
            foreach ($headlines as $key => $title) {
                if ($key === 'progress') {
                    $row[] = round($workerRow[$key] * 100) . '%';
                } elseif ($key === 'duration') {
                    if (empty($workerRow['starttime'])) {
                        $row[] = '';
                    } else {
                        $endtime = empty($workerRow['endtime']) ? date('Y-m-d H:i:s', time()) : $workerRow['endtime'];
                        $row[] = $this->printDuration($workerRow['starttime'], $endtime);
                    }
                } else {
                    $row[] = $workerRow[$key];
                }
            }
            $table->addRow($row);
        }

        $table->render();

        if (! empty($summary)) {
            $table = $this->io->createTable();
            $table->setHeaders(['Worker', 'State', 'Count']);
            foreach ($summary as $key => $value) {
                $key = explode('#', $key);
                $table->addRow([$key[0], $key[1], $value]);
            }
            $table->render();
        }

        if (! empty($resultNotListed)) {
            $this->io->section('Not listed workers:');
            foreach ($resultNotListed as $workerRow => $count) {
                $this->io->text($workerRow . ' count ' . $count);
            }
        }

        return 0;
    }
}
