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

use editor_Models_Languages as Languages;
use editor_Models_Segment_UtilityBroker;
use editor_Models_Segment_WordCount;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory as Factory;

/**
 * List and show the content of a tasks import data skeleton file(s)
 */
class TaskSegmentStatisticsCommand extends TaskCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'task:segment:statistics';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Shows some segment statistics to a task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('A task-identifier must be given, then the segment statistics is printend.');

        $this->addArgument(
            'taskIdentifier',
            InputArgument::REQUIRED,
            TaskCommand::IDENTIFIER_DESCRIPTION
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $task = static::findTaskFromArgument(
            $this->io,
            $input->getArgument('taskIdentifier'),
            false,
            TaskCommand::taskTypesWithData()
        );

        if ($task === null) {
            return self::FAILURE;
        }

        $segments = new \editor_Models_Segment_Iterator($task->getTaskGuid());
        $output = [
            'segments' => 0,
            'locked' => 0,
            'pretranslated' => 0,
            'sourcewordcount' => 0,
            'tagsOpen' => 0,
            'tagsClose' => 0,
            'tagsSingle' => 0,
            'tagsWhitespace' => 0,
            'tagsNumber' => 0,
            'tagsReal' => 0,
            'tagsAll' => 0,
        ];

        $langModel = Factory::get(Languages::class);
        $langModel->load($task->getSourceLang());

        $wordCount = Factory::get(editor_Models_Segment_WordCount::class, [
            $langModel->getRfc5646(),
        ]);

        $segmentTools = new editor_Models_Segment_UtilityBroker();

        $this->io->section('Task:');
        $this->writeAssoc([
            'ID (GUID)' => $task->getId() . ' (' . $task->getTaskGuid() . ')',
            'Task Name (Nr)' => $task->getTaskName() . ' (' . $task->getTaskNr() . ')',
        ]);

        $this->io->section('Read segments...');
        $this->io->progressStart((int) $task->getSegmentCount());

        foreach ($segments as $segment) {
            $output['segments']++;
            if ((int) $segment->getEditable() === 0) {
                $output['locked']++;
            }
            if ((int) $segment->getPretrans() > $segment::PRETRANS_NOTDONE) {
                $output['pretranslated']++;
            }
            $wordCount->setSegment($segment);
            $output['sourcewordcount'] += $wordCount->getSourceCount();
            $tagStat = $segmentTools->internalTag->statistic($segment->getSource());

            $output['tagsOpen'] += $tagStat['open'];
            $output['tagsClose'] += $tagStat['close'];
            $output['tagsSingle'] += $tagStat['single'];
            $output['tagsWhitespace'] += $tagStat['whitespace'];
            $output['tagsNumber'] += $tagStat['number'];
            $output['tagsReal'] += $tagStat['tag'];
            $output['tagsAll'] += $tagStat['all'];
            $this->io->progressAdvance();
        }
        $this->io->progressFinish();

        $this->io->section('Segment Statistics');
        $this->writeAssoc($output);

        $this->io->section('Processing states statistics');
        $autostates = new \editor_Models_Segment_AutoStates();
        $autostatesStat = $autostates->getStatistics($task->getTaskGuid());
        $labels = $autostates->getLabelMap();

        $table = $this->io->createTable();
        $table->setHeaders(['Processing State', 'Count']);
        foreach ($autostatesStat as $idx => $count) {
            if ($count > 0) {
                $table->addRow([$labels[$idx], $count]);
            }
        }
        $table->render();

        return static::SUCCESS;
    }
}
