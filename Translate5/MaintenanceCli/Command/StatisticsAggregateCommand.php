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
use MittagQI\Translate5\Configuration\KeyValueStorage;
use MittagQI\Translate5\Segment\SegmentHistoryAggregation;
use MittagQI\Translate5\Statistics\AbstractStatisticsDB;
use MittagQI\Translate5\Statistics\Helpers\{AggregateTaskHistory, SyncEditable};
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zend_Db_Table;
use Zend_Registry;

class StatisticsAggregateCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'statistics:aggregate';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Aggregates segments history data into Statistics DB.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('Aggregates segments history data into Statistics DB.');

        $this->addOption(
            'compact',
            null,
            InputOption::VALUE_NONE,
            'Compact table/database (resource intensive for large DBs)'
        );

        $this->addOption(
            'purge',
            null,
            InputOption::VALUE_NONE,
            'Delete aggregated history data'
        );

        $this->addOption(
            'since',
            's',
            InputOption::VALUE_REQUIRED,
            'Provide a YYYY-MM-DD date from when the data should be imported'
        );

        $this->addOption(
            'taskId',
            't',
            InputOption::VALUE_OPTIONAL,
            'Provide Task Id for which the data should be imported'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();
        $start = time();

        $statDB = Zend_Registry::get('statistics');
        /* @var $statDB AbstractStatisticsDB */

        if (! $statDB->isAlive()) {
            $this->io->warning("Could not connect to Statistics DB!");

            return self::FAILURE;
        }

        if ($this->input->getOption('purge')) {
            return $this->purgeAggregatedData($statDB);
        }

        $this->io->warning(
            'The existing segments history data will be aggregated into Statistics DB. Make sure Statistics Database status is OK in System Check.'
        );
        if ($this->input->isInteractive() && ! $this->io->confirm('Do you really want to proceed?', false)) {
            return self::SUCCESS;
        }

        $db = Zend_Db_Table::getDefaultAdapter();

        $oneTaskGuid = '';
        $taskId = (int) $input->getOption('taskId');
        if (! empty($taskId)) {
            $oneTaskGuid = $db->fetchOne('SELECT taskGuid FROM LEK_task WHERE id=' . $taskId);
            if (empty($oneTaskGuid)) {
                $this->io->warning("Could not find task with id $taskId");

                return self::FAILURE;
            }
        }

        $totalCount = (int) $db->fetchOne(
            'SELECT COUNT(*) FROM LEK_segment_history' . ($oneTaskGuid ? ' WHERE taskGuid="' . $oneTaskGuid . '"' : '')
        );
        $this->io->writeln("\nTotal amount of history records: " . number_format($totalCount) . "\n");

        $createdEarliest = '2000-01-01';
        $dateFormat = 'YYYY-MM-DD';
        $question = new Question(
            'Please enter date since when you\'d like to aggregate history records (' . $dateFormat . '): ',
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

        if (! $taskId) {
            // get last Ids to save as starting points for periodical syncs of editable by cron
            $newLastSegmentHistoryId = (int) $db->fetchOne('SELECT MAX(id) FROM LEK_segment_history');
            $newLastSegmentId = (int) $db->fetchOne('SELECT MAX(id) FROM LEK_segments');
        }

        $this->io->writeln("Processing tasks..\n");

        $allTasks = $db->fetchPairs(
            'SELECT taskGuid,workflow FROM LEK_task WHERE ' .
            ($taskId ? 'id=' . $taskId : 'taskType IN ("' .
                implode('","', editor_Task_Type::getInstance()->getTaskTypes()) . '") ORDER BY id')
        );

        $progressBar = new ProgressBar($output, count($allTasks));
        $aggregateTask = new AggregateTaskHistory($sqlSince);

        foreach ($allTasks as $taskGuid => $workflowName) {
            if (! $aggregateTask->aggregateData($taskGuid, $workflowName)) {
                $this->io->writeln("\n");
                $this->io->warning(
                    "Failed to add records into statistics table " . SegmentHistoryAggregation::TABLE_NAME
                );

                return self::FAILURE;
            }

            $progressBar->advance();
        }

        if (! $taskId) {
            $storage = new KeyValueStorage();
            $storage->set(SyncEditable::paramLastSegmentHistoryId, $newLastSegmentHistoryId);
            $storage->set(SyncEditable::paramLastSegmentId, $newLastSegmentId);
        }

        $progressBar->finish();
        $this->io->writeln("\n");

        $compact = $this->input->getOption('compact');
        $this->io->writeln('Optimizing' . ($compact ? '/compacting' : '') . ' imported data..');

        $statDB->optimize(SegmentHistoryAggregation::TABLE_NAME, $compact);
        $statDB->optimize(SegmentHistoryAggregation::TABLE_NAME_LEV, $compact);

        $duration = $this->printDuration($start, time());
        $this->io->success(
            'Processing done - ' . $totalCount . ' records processed in ' . $duration
        );

        Zend_Registry::get('logger')
            ->cloneMe('core.db.statistics')
            ->info('E1722', 'Statistics::aggregate - {totalCount} records processed in {duration}', [
                'totalCount' => $totalCount,
                'duration' => $duration,
            ]);

        return self::SUCCESS;
    }

    private function purgeAggregatedData(AbstractStatisticsDB $statDb): int
    {
        $this->io->warning(
            'The existing segments history data will be deleted. Make sure Statistics Database status is OK in System Check.'
        );
        if (! $this->io->confirm('Do you really want to proceed?', false)) {
            return self::SUCCESS;
        }

        $this->io->writeln('Deleting data..');
        $statDb->truncate(SegmentHistoryAggregation::TABLE_NAME);
        $statDb->truncate(SegmentHistoryAggregation::TABLE_NAME_LEV);
        $this->io->success('Processing done');

        return self::SUCCESS;
    }
}
