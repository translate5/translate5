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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

  /**
 * tags the changes between original target and edited target
 * <ins> and <del>-Tags contain the informations like the ones that TrackChanges produces in the frontedn (JS world)
 */

class editor_Models_Export_DiffTagger_TrackChanges extends editor_Models_Export_DiffTagger_Csv {
    
    /**
     * Container to hold the calling task.
     * @var editor_Models_Task
     */
    protected $task = NULL;
    
    /**
     * Container to hold the TrackChanges Tagger
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $tagger = NULL;
    
    /**
     *
     * @param editor_Models_Task $task
     */
    public function __construct(editor_Models_Task $task, ZfExtended_Models_User $user) {
        $this->_changeTimestamp = date('Y-m-d H:i:s');
        $this->task = $task;
        
        // initialize TrackChanges tagger
        $this->tagger = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->tagger->attributeWorkflowstep = $task->getWorkflowStepName().$task->getWorkflowStep();
        
        // to set TrackChanges userTrackingID and userColorNr, we need the TaskUserTracking
        $tempTaskUserTracking = ZfExtended_Factory::get('editor_Models_TaskUserTracking');
        /* @var $tempTaskUserTracking editor_Models_TaskUserTracking */
        $tempTaskUserTracking->loadEntry($task->getTaskGuid(), $user->getUserGuid());
        if (! $tempTaskUserTracking->hasEntry()) {
            $tempTaskUserTracking->insertTaskUserTrackingEntry($task->getTaskGuid(), $user->getUserGuid(), '');
            $tempTaskUserTracking->loadEntry($task->getTaskGuid(), $user->getUserGuid());
        }
        
        $this->tagger->userTrackingId = $tempTaskUserTracking->getId();
        $this->tagger->userColorNr = $tempTaskUserTracking->getTaskOpenerNumberForUser();
    }
    
    /***
     * Surround the content as ins tag
     * @param string $content
     * @return string
     */
    protected function surroundWithIns($content){
        return $this->tagger->createTrackChangesNode(editor_Models_Segment_TrackChangeTag::NODE_NAME_INS, $content);
    }
    
    /***
     * Surround the content as del tag
     * @param string $content
     * @return string
     */
    protected function surroundWithDel($content){
        return $this->tagger->createTrackChangesNode(editor_Models_Segment_TrackChangeTag::NODE_NAME_DEL, $content);
    }
}