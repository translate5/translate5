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
use editor_Models_Segment;
use editor_Models_Segment_RepetitionHash;
use editor_Models_SegmentFieldManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class SegmentSetContentCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'segment:setcontent';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Sets a segment´s source/target.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Sets a segments source/target. If source or target should not be set, provide the string "NULL". '
                . 'Please note, that setting individual segments may cause incorrect segment-hashes '
                . 'and non-appropriate segment states, so this is only to hotfix tasks!'
            );

        $this->addArgument(
            'segmentId',
            InputArgument::REQUIRED,
            'The ID of the segment to edit. Multiple segments can be comma-seperated (without blanks!)'
        );

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            'Source content to be set. Provide "NULL", if source should not be set.'
        );

        $this->addArgument(
            'target',
            InputArgument::REQUIRED,
            'Target content to be set. Provide "NULL", if target should not be set.'
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
        $this->writeTitle('Segment set source/target');

        $ids = array_values(
            array_unique(
                array_filter(
                    explode(',', $this->input->getArgument('segmentId'))
                )
            )
        );
        /** @var string $source */
        $source = $this->input->getArgument('source');
        /** @var string $target */
        $target = $this->input->getArgument('target');

        if (empty($ids)) {
            $this->io->error('Segment id(s) missing.');

            return self::FAILURE;
        }

        if ($source === 'NULL') {
            $source = null;
        }
        if ($target === 'NULL') {
            $target = null;
        }

        if ($source === null && $target === null) {
            $this->io->error('Source and target cannot be NULL.');

            return self::FAILURE;
        }

        $this->io->comment('SOURCE: ' . $source);
        $this->io->comment('TARGET: ' . $target);

        $errors = [];
        $success = [];

        foreach ($ids as $id) {
            try {
                $segment = ZfExtended_Factory::get(editor_Models_Segment::class);
                $segment->load((int) $id);
                $task = editor_ModelInstances::taskByGuid($segment->getTaskGuid());
                $fields = editor_Models_SegmentFieldManager::getForTaskGuid($segment->getTaskGuid());
                $hasher = ZfExtended_Factory::get(editor_Models_Segment_RepetitionHash::class, [$task]);
                $hasher->setSegment($segment);

                $hashSource = ($source === null) ? $segment->getSource() : $source;
                $hashTarget = ($target === null) ? $segment->getTarget() : $target;
                if ($source !== null) {
                    $toSort = $segment->stripTags($source, true);
                    $segment->setSource($source);
                    $segment->set('sourceToSort', $toSort);
                    if ($fields->isEditable('source')) {
                        $segment->setSourceEdit($source);
                        $segment->set('sourceEditToSort', $toSort);
                    }
                    $segment->set('sourceMd5', $hasher->hashSource($hashSource, $hashTarget));
                }
                if ($target !== null) {
                    $toSort = $segment->stripTags($target, false);
                    $segment->setTarget($target);
                    $segment->setTargetEdit($target);
                    $segment->set('targetToSort', $toSort);
                    $segment->setTargetMd5($hasher->hashTarget($hashTarget, $hashSource));
                }
                $segment->save();

                $what = ($source !== null && $target !== null) ?
                    'source and target' : ($source !== null ? 'only source' : 'omly target');
                $success[] = 'Updated Segment with id "' . $id . '", ' . $what . '.';
            } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                $errors[] = 'Segment with id "' . $id . '" could not be found or had errors [ '
                    . $e->getMessage() . ' ].';
            }
        }

        if (! empty($errors)) {
            $this->io->error($errors);
        }
        if (! empty($success)) {
            $this->io->success($success);
        }

        return self::SUCCESS;
    }
}
