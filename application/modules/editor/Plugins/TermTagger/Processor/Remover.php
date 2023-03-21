<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\TermTagger\Processor;

use editor_Models_Db_SegmentQuality;
use editor_Models_Task;
use editor_Plugins_TermTagger_Tag;
use editor_Segment_Processing;
use editor_Segment_Tags;
use MittagQI\Translate5\Plugins\TermTagger\Configuration;
use MittagQI\Translate5\Plugins\TermTagger\Service;
use MittagQI\Translate5\Segment\AbstractProcessor;
use MittagQI\Translate5\Service\DockerServiceAbstract;
use ZfExtended_Exception;

/**
 * Encapsulates the removal of the term-tags of groups of segment-tags
 * This can be neccessary, when the terminology has been removed from a task
 * In the constructor also all qualities are removed
 *
 * @property Service $service;
 */
final class Remover extends AbstractProcessor
{
    /**
     * Is used as interval between the batches in the looped processing
     * This reduces the risk of deadlocks
     * @var int
     */
    protected int $loopingPause = 330;

    /**
     * Special: The Termtagger-Worker will also be queued for import-workers, actually not having a terminology applied
     * This is because at that point of the import the terminology is not yet set
     * This processor removes the term-tags from the segments if no terminology is set, what is not needed for an import obviously
     * Also, removing the terminology does not utilize a service, so one processing worker using bigger batch-sizes is enough in any case
     * @param int $workerIndex
     * @return bool
     */
    public function prepareWorkload(int $workerIndex): bool
    {
        if($this->processingMode !== editor_Segment_Processing::IMPORT){
            // when terms are removed the qualities also need to be removed - this is done much more efficiently for the whole task
            $table = new editor_Models_Db_SegmentQuality();
            $table->removeByTaskGuidAndType($this->task->getTaskGuid(), editor_Plugins_TermTagger_Tag::TYPE);
            return true;
        }
        return false;
    }

    /**
     * @return int
     */
    public function getBatchSize(): int
    {
        return Configuration::REMOVAL_BATCH_SIZE;
    }

    /**
     * Processes a batch of segments
     * @param editor_Segment_Tags[] $segmentsTags
     */
    public function processBatch(array $segmentsTags)
    {
        foreach ($segmentsTags as $segmentTags) {
            $this->process($segmentTags, true);
        }
    }

    /**
     * Processes a single segment
     * @param editor_Segment_Tags $segmentTags
     * @param bool $saveTags
     */
    public function process(editor_Segment_Tags $segmentTags, bool $saveTags = true)
    {
        if (count($segmentTags->getTagsByType(editor_Plugins_TermTagger_Tag::TYPE)) > 0) {
            $segmentTags->removeTagsByType(editor_Plugins_TermTagger_Tag::TYPE);
            if ($saveTags) {
                $segmentTags->save(false); // we do not save qualities as they are removed batch-wise in the quality provider using ::removeAllQualities
            }
        }
    }
}
