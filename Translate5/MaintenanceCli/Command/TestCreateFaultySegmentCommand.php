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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCreateFaultySegmentCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'test:createfaultysegment';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('API-Tests: Manipulates a segment to contain a tag-error in the edited target')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('API-Tests: Manipulates a segment to contain a tag-error in the edited target. Useful for testing quality processing.');

        $this->addArgument(
            'segmentId',
            InputArgument::REQUIRED,
            'ID of the segment to be manipulated'
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

        $segmentId = $this->input->getArgument('segmentId');
        if(!is_numeric($segmentId)){
            $this->io->error('The given segment-id is not a number');
            return self::FAILURE;
        }

        $this->initTranslate5AppOrTest();

        $segment = \ZfExtended_Factory::get(\editor_Models_Segment::class);
        try {
            $segment->load(intval($segmentId));
        } catch(\Throwable){
            $this->io->error('The given segment-id was not found');
            return self::FAILURE;
        }

        $task = \editor_ModelInstances::taskByGuid($segment->getTaskGuid());
        $tagCheckEnabled = \editor_Segment_Quality_Manager::instance()->isFullyCheckedType(\editor_Segment_Tag::TYPE_INTERNAL, $task->getConfig());

        // check, if the segment is already faulty
        $qualityTable = new \editor_Models_Db_SegmentQuality();

        // check if the segment already is faulty
        if($tagCheckEnabled){
            $existingFaults = $qualityTable->fetchFiltered(NuLL, $segment->getId(), \editor_Segment_Tag::TYPE_INTERNAL, false, \editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY);
            if($existingFaults->count() > 0){
                $this->io->warning('Segment '.$segment->getId().' is already faulty.');
                return self::SUCCESS;
            }
        } else {
            $fieldTags = $segment->getFieldTags($task, 'target');
            $comparison = new \editor_Segment_Internal_TagComparision($fieldTags, null);
            if($comparison->hasFaults()){
                $this->io->warning('Segment '.$segment->getId().' is already faulty, autoQA is disabled though.');
                return self::SUCCESS;
            }
        }

        // create internal tag error
        $pattern = '#<div class="close.+class="short"[^>]*>&lt;/([0-9]+)&gt;</span>.+</div>#U'; // see \editor_Models_Segment_InternalTag::REGEX_ENDTAG;
        $target = $segment->getTargetEdit();
        $numEndTags = preg_match_all($pattern, $target);
        // if we have an internal tag, we remove one opener, otherwise we simply add a invalid one
        if($numEndTags !== false && $numEndTags > 0){
            // remove first opener tag
            $target = preg_replace_callback($pattern, function($matches){ return ''; }, $target, 1);
            $msg = 'Removed first closing internal tag from segment "'.\editor_Segment_Tag::strip($target).'".';
        } else {
            // adds an unclosed internal tag to the front
            $target = trim('<div class="open 672069643d223122 internal-tag ownttip"><span class="short" title="<g id=&quot;1&quot;>" id="ext-element-848">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;g id="1"&gt;</span></div> '.trim($target));
            $msg = 'Added unclosed internal tag to segment "'.\editor_Segment_Tag::strip($target).'".';
        }

        // cave segment with fault
        $segment->setTargetEdit($target);
        $segment->save();

        // add quality if the tag-check is active
        if($tagCheckEnabled){
            $quality = $qualityTable->createRow([], \Zend_Db_Table_Abstract::DEFAULT_DB);
            /* @var $quality \editor_Models_Db_SegmentQualityRow */
            $quality->segmentId = $segment->getId();
            $quality->taskGuid = $segment->getTaskGuid();
            $quality->field = 'target';
            $quality->type = \editor_Segment_Tag::TYPE_INTERNAL;
            $quality->category = \editor_Segment_Internal_TagComparision::TAG_STRUCTURE_FAULTY;
            $quality->save();
        }

        $this->io->success($msg);

        return self::SUCCESS;
    }
}
