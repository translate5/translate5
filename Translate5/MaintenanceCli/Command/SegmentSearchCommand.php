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

use editor_ModelInstances;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Models_Entity_NotFoundException;

class SegmentSearchCommand extends Translate5AbstractCommand
{
    public const ROW_LIMIT = 100;

    public const MAX_TEXT_LENGTH = 120;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'segment:search';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Finds Segment by searching in source or target text.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Searches segments by source or target text. '
                . 'Will not search in internal tags or other markup unless stated. '
                . 'Use "_" or "%" as SQL placeholders, "%" will seperate parts'
                . 'Will only show the first 100 matches max.');

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Source search string. If only target shall be searched, provide empty string'
        );

        $this->addArgument(
            'target',
            InputArgument::OPTIONAL,
            'Target search string'
        );

        $this->addOption(
            'taskId',
            't',
            InputOption::VALUE_REQUIRED,
            'The ID of the task the segment is part of'
        );

        $this->addOption(
            'markup',
            'm',
            InputOption::VALUE_NONE,
            'If given, searches in the Markup as well'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();
        $this->writeTitle('Segment search');

        $source = $this->input->getArgument('source');
        $target = $this->input->hasArgument('target') ? $this->input->getArgument('target') : '';
        $sources = array_filter(explode('%', $source));
        $targets = array_filter(explode('%', $target));

        if (empty($sources) && empty($targets)) {
            $this->io->error('You have to provide a valid source or valid target or both!');

            return self::FAILURE;
        }

        $taskGuid = null;
        $taskId = $input->getOption('taskId');
        if (! empty($taskId)) {
            try {
                $task = editor_ModelInstances::task((int) $taskId);
                $taskGuid = $task->getTaskGuid();
            } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                $this->io->error('Task with the id "' . $taskId . '" could not be found.');

                return self::FAILURE;
            }
        }

        $db = new \editor_Models_Db_SegmentData();
        $columnSuffix = $input->getOption('markup') ? '' : 'ToSort';
        $select = 'SELECT DISTINCT `segmentId`, `taskGuid`, `originalToSort`, `editedToSort`  FROM ' . $db->info($db::NAME) . ' WHERE ';
        if (! empty($sources)) {
            $ors = [];
            foreach ($sources as $source) {
                $value = $db->getAdapter()->quote('%' . $source . '%');
                $ors[] = '`original' . $columnSuffix . '` LIKE ' . $value;
                $ors[] = '`edited' . $columnSuffix . '` LIKE ' . $value;
            }
            $select .= '(`name` = \'source\' AND (' . implode(' OR ', $ors) . '))';
        }
        if (! empty($targets)) {
            $ors = [];
            foreach ($targets as $target) {
                $value = $db->getAdapter()->quote('%' . $target . '%');
                $ors[] = '`original' . $columnSuffix . '` LIKE ' . $value;
                $ors[] = '`edited' . $columnSuffix . '` LIKE ' . $value;
            }
            if (empty($sources)) {
                $select .= '(`name` = \'target\' AND (' . implode(' OR ', $ors) . '))';
            } else {
                $select .= ' OR (`name` = \'target\' AND (' . implode(' OR ', $ors) . '))';
            }
        }
        if (! empty($taskGuid)) {
            $select .= ' AND `taskGuid` = ' . $db->getAdapter()->quote($taskGuid);
        }
        $select .= ' LIMIT ' . self::ROW_LIMIT;
        $rows = $db->getAdapter()->fetchAssoc($select);

        if (count($rows) === 0) {
            $this->io->title('No segments found that contained the search-query');
            $this->io->comment($select);
        } else {
            $headers = [
                'id' => 'Segment id',
                'nr' => 'Segment nr',
                'taskId' => 'Task id',
                'text' => 'Segment text',
            ];

            $segment = new \editor_Models_Segment();
            $table = $this->io->createTable();
            $table->setHeaders($headers);
            foreach ($rows as $row) {
                $text = empty($row['editedToSort']) ? $row['originalToSort'] : $row['editedToSort'];
                $segment->load((int) $row['segmentId']);
                if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
                    $text = mb_substr($text, 0, self::MAX_TEXT_LENGTH - 2) . ' ...';
                }
                $table->addRow([
                    'id' => $row['segmentId'],
                    'nr' => $segment->getSegmentNrInTask(),
                    'taskId' => editor_ModelInstances::taskByGuid($row['taskGuid'])->getId(),
                    'text' => $text,
                ]);
            }
            $table->render();
        }

        return self::SUCCESS;
    }
}
