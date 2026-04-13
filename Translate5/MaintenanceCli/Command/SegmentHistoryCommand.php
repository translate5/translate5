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

use editor_Models_Segment;
use editor_Models_Segment_AutoStates;
use editor_Models_Segment_UtilityBroker;
use editor_Models_SegmentField;
use editor_Models_SegmentFieldManager;
use editor_Models_Task;
use MittagQI\Translate5\Repository\{SegmentHistoryDataRepository,
    SegmentHistoryRepository};
use MittagQI\Translate5\Statistics\Dto\SegmentLevenshteinDTO;
use MittagQI\Translate5\Statistics\SegmentLevenshteinRepository;
use MittagQI\Translate5\Statistics\SegmentStatisticsRepository;
use ReflectionException;
use stdClass;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\WebAppBridge\Application;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class SegmentHistoryCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'segment:history';

    protected int $versionCounter = 1;

    protected array $autoStateMap;

    protected editor_Models_Segment_UtilityBroker $segmentUtilities;

    protected function configure(): void
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Shows the segment editing history, from the oldest to the current version.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Shows the segment editing history, from the oldest to the current version.
The segment is identified by id or by taskGuid + segment number in task.
The single versions are showing only the values different to the current one! This could be confusing first.');

        $this->addArgument(
            'segment',
            InputArgument::REQUIRED,
            'Either a instance wide unique segment ID, or with -t|--task the segment number in the given task.'
        );

        $this->addOption(
            'task',
            't',
            InputOption::VALUE_REQUIRED,
            'Give a task ID or taskGuid here, then the argument "segment" is interpreted as segment '
                . 'nr in that task instead as a unique segment id.'
        );

        $this->addOption(
            'no-trackchanges',
            'c',
            InputOption::VALUE_NONE,
            'With this option no track changes are shown - so final content after applying them is rendered'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws ReflectionException
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        Application::$startSession = true;
        $this->initTranslate5AppOrTest();
        $this->writeTitle('Segment history');

        $this->segmentUtilities = new editor_Models_Segment_UtilityBroker();

        $autoStates = new editor_Models_Segment_AutoStates();
        $this->autoStateMap = array_flip($autoStates->getStateMap());

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $taskGuid = $this->findTaskGuid($task);

        $segment = $this->findSegment($taskGuid);
        if ($taskGuid === null) {
            //only segment id was passed as argument
            $task->loadByTaskGuid($segment->getTaskGuid());
        }

        $history = SegmentHistoryRepository::create();
        $historyEntries = array_reverse($history->loadBySegmentId((int) $segment->getId()));
        $historyData = new SegmentHistoryDataRepository();
        $historyDataEntries = $historyData->loadBySegmentId(
            (int) $segment->getId(),
            editor_Models_SegmentField::TYPE_TARGET
        );

        $ids = array_column($historyDataEntries, 'segmentHistoryId');
        $historyDataEntries = array_combine($ids, $historyDataEntries);
        $segmentLevenshteinRows = SegmentLevenshteinRepository::create()->getBySegmentId((int) $segment->getId());
        $historyLevenshtein = [];
        $currentLevenshtein = null;
        foreach ($segmentLevenshteinRows as $row) {
            if ($row->historyId === 0) {
                $currentLevenshtein = $row;

                continue;
            }
            $historyLevenshtein[$row->historyId] = $row;
        }

        $this->io->section("General segment information");
        $this->io->text([
            '<info>    Segment ID:</info> <options=bold>' . $segment->getId() . '</>',
            '<info>    Nr in Task:</info> <options=bold>' . $segment->getSegmentNrInTask() . '</>',
            '<info>Task ID / GUID:</info> <options=bold>' . $task->getId() . ' / ' . $segment->getTaskGuid() . '</>',
        ]);
        foreach ($historyEntries as $entry) {
            $this->showSegment(
                (object) $entry,
                $segment,
                $historyLevenshtein[(int) $entry['id']] ?? null
            );
            if (! empty($historyDataEntries[$entry['id']])) {
                $this->showSegmentContent($historyDataEntries[$entry['id']]);
            }
        }
        $this->showLatestSegment($segment, $currentLevenshtein);
        $data = $segment->getEditableFieldData();
        foreach ($data as $field => $content) {
            $this->showSegmentContent([
                'name' => $field,
                'edited' => $content,
                'duration' => $segment->getDuration(
                    str_replace(editor_Models_SegmentFieldManager::_EDIT_SUFFIX . '$', '', $field . '$')
                ),
            ]);
        }

        $this->showSegmentStatistics($segment);

        return self::SUCCESS;
    }

    /**
     * returns the autostate key
     */
    protected function getAutoState(int $autoState): string
    {
        return $this->autoStateMap[$autoState] ?? 'unknown';
    }

    protected function showSegment(
        stdClass $segmentVersion,
        editor_Models_Segment $segment,
        ?SegmentLevenshteinDTO $levenshteinData = null
    ): void {
        $this->io->section('Version ' . $this->versionCounter++ . ':');
        $result = [
            '     <info>history ID:</info> <options=bold>' . $segmentVersion->id . '</>',
            '        <info>created:</info> <options=bold>' . $segmentVersion->created . '</>',
        ];
        $result[] = '           <info>user:</info> ' . $segmentVersion->userName
            . ' (' . $segmentVersion->userGuid . ')';
        $result[] = '  <info>process state:</info> ' . $this->getAutoState($segmentVersion->autoStateId);
        $result[] = '       <info>editable:</info> ' . $segmentVersion->editable;
        $result[] = '       <info>pretrans:</info> ' . $segmentVersion->pretrans;
        $result[] = '   <info>editedInStep:</info> ' . $segmentVersion->editedInStep;
        if ($segmentVersion->workflowStepNr != $segment->getWorkflowStepNr()
            || $segmentVersion->workflowStep != $segment->getWorkflowStep()) {
            if (empty($segmentVersion->workflowStepNr) && empty($segmentVersion->workflowStep)) {
                $result[] = '       <info>workflow:</info> -na-';
            } else {
                $result[] = '       <info>workflow:</info> ' . $segmentVersion->workflowStep
                    . ' (' . $segmentVersion->workflowStepNr . ')';
            }
        }
        if ($segmentVersion->matchRate != $segment->getMatchRate()) {
            $result[] = '      <info>matchRate:</info> ' . $segmentVersion->matchRate;
        }
        if (mb_stripos($segment->getMatchRateType(), $segmentVersion->matchRateType) !== 0) {
            $result[] = ' <info>matchRate type:</info> ' . $segmentVersion->matchRateType;
        }
        $result[] = '       <info>state id:</info> ' . $segmentVersion->stateId;
        $result[] = '<info>Levensht (Orig):</info> ' . ($levenshteinData?->levenshteinOriginal ?? 0);
        $result[] = '<info>Levensht (Prev):</info> ' . ($levenshteinData?->levenshteinPrevious ?? 0);
        $result[] = '<info>  Length (Prev):</info> ' . ($levenshteinData?->segmentlengthPrevious ?? 0);
        $this->io->text($result);
    }

    /**
     * @throws ReflectionException
     */
    protected function showSegmentContent(array $segment): void
    {
        if ($this->input->getOption('no-trackchanges')) {
            $content = $this->segmentUtilities->internalTag->toExcel($segment['edited']);
        } else {
            $content = $this->segmentUtilities->internalTag->toDebug($segment['edited']);
        }
        if (array_key_exists('duration', $segment)) {
            $this->io->text('  <info>duration (ms):</info> <options=bold>' . $segment['duration'] . '</>');
        }
        $label = str_pad('<info>' . $segment['name'] . ':</info> ', 30, ' ', STR_PAD_LEFT);
        $this->io->text($label . $content);
    }

    protected function showLatestSegment(
        editor_Models_Segment $segment,
        ?SegmentLevenshteinDTO $levenshteinData = null
    ): void {
        $this->io->section('Used/latest version:');
        $result = [
            '  <info>last modified:</info> <options=bold>' . $segment->getTimestamp() . '</>',
            '           <info>user:</info> <options=bold>' . $segment->getUserName()
            . ' (' . $segment->getUserGuid() . ')</>',
            '       <info>editable:</info> <options=bold>' . $segment->getEditable() . '</>',
            '       <info>pretrans:</info> <options=bold>' . $segment->getPretrans() . '</>',
            '   <info>editedInStep:</info> <options=bold>' . $segment->getEditedInStep() . '</>',
            '  <info>process state:</info> <options=bold>' . $this->getAutoState(
                (int) $segment->getAutoStateId()
            ) . '</>',
        ];
        if (empty($segment->getWorkflowStep()) && empty($segment->getWorkflowStepNr())) {
            $result[] = '       <info>workflow:</info> <options=bold>-na-</>';
        } else {
            $result[] = '       <info>workflow:</info> <options=bold>' . $segment->getWorkflowStep()
                . ' (' . $segment->getWorkflowStepNr() . ')</>';
        }
        $result[] = '      <info>matchRate:</info> <options=bold>' . $segment->getMatchRate() . '</>';
        $result[] = '  <info>matchRateType:</info> <options=bold>' . $segment->getMatchRateType() . '</>';
        $result[] = '<info>Levensht (Orig):</info> ' . ($levenshteinData?->levenshteinOriginal ?? 0);
        $result[] = '<info>Levensht (Prev):</info> ' . ($levenshteinData?->levenshteinPrevious ?? 0);
        $result[] = '<info>  Length (Prev):</info> ' . ($levenshteinData?->segmentlengthPrevious ?? 0);
        if (! empty($segment->getStateId())) {
            $result[] = '        <info>stateId:</info> <options=bold>' . $segment->getStateId() . '</>';
        }

        $this->io->text($result);
    }

    /**
     * returns the taskGuid to given option --task, null if option was not given
     */
    protected function findTaskGuid(editor_Models_Task $task): ?string
    {
        $taskId = $this->input->getOption('task');

        if (empty($taskId)) {
            return null;
        }

        try {
            if (is_numeric($taskId)) {
                $task->load($taskId);
            } else {
                $task->loadByTaskGuid($taskId);
            }
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new RuntimeException('No task with ID | taskGuid ' . $taskId . ' could be found!');
        }

        return $task->getTaskGuid();
    }

    /**
     * returns the found segment
     * @param string|null $taskGuid optional, of given segmentId is interpreted as segment nr in task
     * @throws ReflectionException
     */
    protected function findSegment(string $taskGuid = null): editor_Models_Segment
    {
        $segmentId = $this->input->getArgument('segment');

        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */

        try {
            if (empty($taskGuid)) {
                $segment->load($segmentId);
            } else {
                $segment->loadBySegmentNrInTask($segmentId, $taskGuid);
            }

            return $segment;
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw new RuntimeException('No segment with nr|id ' . $segmentId . ' could be found!');
        }
    }

    private function showSegmentStatistics(editor_Models_Segment $segment): void
    {
        $historyAggregationData = SegmentStatisticsRepository::create();
        $durationData = $historyAggregationData->getPosteditingTimeAggregationBySegmentId((int) $segment->getId());
        $levenstheinData = $historyAggregationData->getLevenshteinRowsBySegmentId((int) $segment->getId());

        $this->io->section('Segment history postediting time data');

        $table = $this->io->createTable();
        $table->setHeaders(['Workflow', 'UserGuid', 'Duration (ms)']);
        foreach ($durationData as $row) {
            $table->addRow([
                $row['workflowStepName'],
                $row['userGuid'],
                $row['duration'],
            ]);
        }

        $table->render();

        $this->io->section('Segment history aggregation levenshtein data');
        $table = $this->io->createTable();
        $table->setHeaders([
            'Workflow',
            'UserGuid',
            'Editable',
            'Latest',
            'Levensth. Orig',
            'Levensth. Prev',
            'Length. Prev',
            'MatchRate',
            'Lang Res ID',
        ]);
        foreach ($levenstheinData as $row) {
            $table->addRow([
                $row['workflowName'] . '::' . $row['workflowStepName'],
                $row['userGuid'],
                $row['editable'],
                $row['latestEntry'],
                $row['levenshteinOriginal'],
                $row['levenshteinPrevious'],
                $row['segmentlengthPrevious'],
                $row['matchRate'],
                $row['langResId'] . ' (' . $row['langResType'] . ')',
            ]);
        }

        $table->render();
    }
}
