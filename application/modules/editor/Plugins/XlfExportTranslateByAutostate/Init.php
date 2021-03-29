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

/**
 * Initial Class of Plugin "XlfExportTranslateByAutostate"
 * This Plugin is for Across Connection where we have to abuse trans-units,
 * therefore it works only if in the task a foreignId (acrossId) is set!
 *
 * translate="yes/no" for filtering changed segments coming from translate5
 *
 * All segments modified are getting translate = yes
 * Modified means with a reviewed autostate, and real changed content or new comments.
 */
class editor_Plugins_XlfExportTranslateByAutostate_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * Regarding the autostates all review states where content was really modified are making translate yes,
     * so all other makes translate no then.
     * @var array
     */
    protected $translateYesStates = [
            editor_Models_Segment_AutoStates::REVIEWED,
            editor_Models_Segment_AutoStates::REVIEWED_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR,
            editor_Models_Segment_AutoStates::REVIEWED_TRANSLATOR_AUTO,
            editor_Models_Segment_AutoStates::REVIEWED_PM,
            editor_Models_Segment_AutoStates::REVIEWED_PM_AUTO,
    ];
    
    public function init() {
        $this->eventManager->attach('editor_Models_Export_FileParser_Xlf', 'writeTransUnit', array($this, 'handleWriteUnit'));
    }
    
    /**
     * Parameters are:
        'tag' => $tag,
        'key' => $key,
        'tagOpener' => $opener,
        'xmlparser' => $xmlparser,
        'segments' => $segments,
     * @param Zend_EventManager_Event $event
     */
    public function handleWriteUnit(Zend_EventManager_Event $event) {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        $foreignId = $task->getForeignId();
        if(empty($foreignId)) {
            return;
        }
        $segments = $event->getParam('segments');
        $autoStates = [];
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */
            $autoStates[] = $segment->getAutoStateId();
        }
        $autoStates = array_unique($autoStates);
        $usedTranslateYesStates = array_intersect($this->translateYesStates, $autoStates);

        //if there are no translateYes states used and no new comments, then it is translateNo
        $translateYes = $this->hasNewComments($segments) || !empty($usedTranslateYesStates);
        
        $attributes = $event->getParam('attributes');
        settype($attributes, 'array');
        $attributes['translate'] = $translateYes ? 'yes' : 'no';
        //setting back the attributes in the event for further handlers,
        // and the transunit attributes are generated from that array
        $event->setParam('attributes', $attributes);
    }
    
    /**
     * returns true if there were made some new comments in the segments
     * @param array $segments
     * @return boolean
     */
    protected function hasNewComments(array $segments) {
        $comment = ZfExtended_Factory::get('editor_Models_Comment');
        $nonImportedComments = function($item) {
            return $item['userGuid'] != editor_Models_Import_FileParser_Xlf_Namespaces_Across::USERGUID;
        };
        /* @var $comment editor_Models_Comment */
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */
            $commentsRendered = $segment->getComments();
            if(empty($commentsRendered)) {
                continue;
            }
            $comments = $comment->loadBySegmentAndTaskPlain((int) $segment->getId(), $segment->getTaskGuid());
            //filter out the imported comments and consider only newly written comments
            $comments = array_filter($comments, $nonImportedComments);
            if(!empty($comments)) {
                return true;
            }
        }
        return false;
    }
}
