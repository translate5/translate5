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

declare(strict_types=1);

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Segment;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class SegmentInfoCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'segment:info';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Prints the segments main data.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Prints the segments main data in a tabular form');

        $this->addArgument(
            'segmentId',
            InputArgument::REQUIRED,
            'The id of the segment to be printed'
        );

        $this->addOption(
            'meta',
            'm',
            InputOption::VALUE_NONE,
            'Show also the meta data of the segment'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @throws ReflectionException
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();
        $this->writeTitle('Segment info');

        $segmentId = $this->input->getArgument('segmentId');
        $segment = ZfExtended_Factory::get(editor_Models_Segment::class);

        try {
            $segment->load((int) $segmentId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            $this->io->error('Segment with the id "' . $segmentId . '" could not be found.');

            return self::FAILURE;
        }

        $this->io->title('Data of segment ' . $segmentId);

        $headers = [
            'name' => 'Name',
            'value' => 'Value',
        ];
        $table = $this->io->createTable();
        $table->setHeaders($headers);
        $rows = [];
        $values = [
            'id',
            'segmentNrInTask',
            'taskGuid',
            'editable',
            'pretrans',
            'matchRate',
            'matchRateType',
            'workflowStepNr',
            'workflowStep',
            'isRepeated',
            'source',
            'sourceMd5',
            'sourceEdit',
            'target',
            'targetMd5',
            'targetEdit',
        ];
        foreach ($values as $val) {
            $rows[] = [
                'name' => $val,
                'value' => ($val === 'source' || $val === 'sourceEdit' || $val === 'target' || $val === 'targetEdit') ?
                    '\'' . str_replace("'", '\\\'', $segment->get($val)) . '\'' : $segment->get($val),
            ];
        }

        $table->setRows($rows);
        $table->render();

        if ($this->input->getOption('meta')) {
            $this->io->section('Meta data of segment');
            $data = $segment->meta()->getDataObject();
            $this->writeAssoc((array) $data);
        }

        return self::SUCCESS;
    }
}
