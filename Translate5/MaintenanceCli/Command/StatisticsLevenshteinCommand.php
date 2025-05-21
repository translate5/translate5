<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use editor_Task_Type;
use MittagQI\Translate5\Repository\{SegmentHistoryDataRepository, SegmentHistoryRepository};
use MittagQI\Translate5\Statistics\Helpers\LevenshteinCalcTaskHistory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zend_Db_Adapter_Abstract;
use Zend_Registry;

class StatisticsLevenshteinCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'statistics:levenshtein';

    private Zend_Db_Adapter_Abstract $db;

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Calculate missing levenshtein values in segment history.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Calculate missing levenshtein values for segments/segment_history tables.');

        $this->addOption(
            'since',
            's',
            InputOption::VALUE_REQUIRED,
            'Provide a YYYY-MM-DD date from when the missing levenshtein values should be calculated'
        );

        $this->addOption(
            'taskId',
            't',
            InputOption::VALUE_OPTIONAL,
            'Provide Task Id for which the missing levenshtein values should be calculated'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $start = time();

        $this->io->warning(
            'The existing segments history data will be used to calculate missing levenshtein values.'
        );
        if ($this->input->isInteractive() && ! $this->io->confirm('Do you really want to proceed?', false)) {
            return self::SUCCESS;
        }

        $this->db = \Zend_Db_Table::getDefaultAdapter();

        $oneTaskGuid = '';
        $taskId = (int) $input->getOption('taskId');
        if (! empty($taskId)) {
            $oneTaskGuid = $this->db->fetchOne('SELECT taskGuid FROM LEK_task WHERE id=' . $taskId);
            if (empty($oneTaskGuid)) {
                $this->io->warning("Could not find task with id $taskId");

                return self::FAILURE;
            }
        }

        $totalCount = (int) $this->db->fetchOne(
            'SELECT COUNT(*) FROM LEK_segment_history' . ($oneTaskGuid ? ' WHERE taskGuid="' . $oneTaskGuid . '"' : '')
        );
        $this->io->writeln("\nTotal amount of history records: " . number_format($totalCount) . "\n");

        $createdEarliest = '2000-01-01';
        $dateFormat = 'YYYY-MM-DD';
        $question = new Question(
            'Please enter date since when you\'d like to calculate missing levenshtein values (' . $dateFormat . '): ',
            $createdEarliest
        );
        $created = $input->getOption('since');
        while (! preg_match('#^20\d{2}-[01]\d-[0-3]\d$#', $created)) {
            if (! empty($created)) {
                $this->io->warning('Your selected date is invalid, please enter a valid one (' . $dateFormat . ').');
            }
            $created = $this->io->askQuestion($question);
        }
        $sqlSince = ($created != $createdEarliest) ? ' AND `created`>"' . $created . ' 00:00:00"' : '';

        $this->io->writeln("Processing tasks..\n");

        $allTasks = $this->db->fetchPairs(
            'SELECT taskGuid,workflow FROM LEK_task WHERE ' .
            ($taskId ? 'id=' . $taskId : 'taskType IN ("' .
                implode('","', editor_Task_Type::getInstance()->getTaskTypes()) . '") ORDER BY id')
        );
        $progressBar = new ProgressBar($output, count($allTasks));
        $levenshteinCalc = new LevenshteinCalcTaskHistory($sqlSince);

        foreach ($allTasks as $taskGuid => $workflowName) {
            $levenshteinCalc->calculate($taskGuid, $workflowName);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->writeln("\n");

        $duration = $this->printDuration($start, time());
        $this->io->success(
            'Processing done - ' . $totalCount . ' records processed in ' . $duration
        );

        Zend_Registry::get('logger')
            ->cloneMe('core.db.statistics')
            ->info('E1722', 'Statistics::levensthein - {totalCount} records processed in {duration}', [
                '{totalCount}' => $totalCount,
                '{duration}' => $duration,
            ]);

        return self::SUCCESS;
    }
}
