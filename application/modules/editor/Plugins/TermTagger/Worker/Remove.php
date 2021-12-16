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

/**
 * 
 * Removes all Term-Tags and all Qualities regarding terms from all segments from a task
 *
 */
class editor_Plugins_TermTagger_Worker_Remove extends editor_Models_Task_AbstractWorker {
    
    /**
     * This defines the processing mode for the segments we process
     * This worker is used in various situations
     * @var string
     */
    protected $processingMode;
    
    protected function validateParameters($parameters = array()) {
        if(array_key_exists('processingMode', $parameters)){
            $this->processingMode = $parameters['processingMode'];
            return true;
        }
        return false;
    }
    
    protected function work(){
        // removes all term-tags for the current task
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $db->getAdapter()->beginTransaction();
        $sql = $db->select()
            ->from($db, ['id'])
            ->where('taskGuid = ?', $this->taskGuid)
            ->order('id')
            ->forUpdate(true);
        $segmentIds = $db->fetchAll($sql)->toArray();
        $segmentIds = array_column($segmentIds, 'id');
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        foreach($segmentIds as $segmentId){
            $segment->load($segmentId);
            $tags = editor_Segment_Tags::fromSegment($this->task, $this->processingMode, $segment, editor_Segment_Processing::isOperation($this->processingMode));
            // we only need to remove term-tags if there are any
            if(count($tags->getTagsByType(editor_Plugins_TermTagger_Tag::TYPE)) > 0){
                $tags->removeTagsByType(editor_Plugins_TermTagger_Tag::TYPE);
                $tags->flush();                
            }
        }
        $db->getAdapter()->commit();
        
        // remove all qualities (usually this was already done by removing all qualities in an analysis/autoqa operation, but this worker should be able to work universally
        $table = new editor_Models_Db_SegmentQuality();
        $table->removeByTaskGuidAndType($this->taskGuid, editor_Plugins_TermTagger_Tag::TYPE);
        
        return true;
    }
}
