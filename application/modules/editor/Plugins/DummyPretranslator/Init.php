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

use MittagQI\Translate5\Tools\FakeTranslator;
use MittagQI\Translate5\Segment\TagRepair\Tags;

/**
 * Dummy Pretranslator which just takes the whole source content, creates random content and fills the target with that.
 * Needed for Testing
 */
class editor_Plugins_DummyPretranslator_Init extends ZfExtended_Plugin_Abstract {

    protected static string $description = 'Provides a dummy pretranslator - for testing';

    public function init() {
        $this->eventManager->attach('editor_Models_Import', 'afterImport', array($this, 'handleAfterImport'));
    }
    
    public function handleAfterImport(Zend_EventManager_Event $event) {

        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */

        if(!$task->isTranslation()) {
            error_log("DummyPretranslator disabled due existing targets for Task ".$task->getTaskGuid().' - '.$task->getTaskName());
            return;
        }

        error_log("DummyPretranslator will now translate task ".$task->getTaskGuid().' - '.$task->getTaskName());
        
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */
            //due of other pretranslations we have to check targetEdit content here too:
            $targetEdit = $segment->getTargetEdit();
            if(!empty($targetEdit)) {
                continue;
            }
            $target = $segment->getSource();
            
            //comment the following line to fill the target with the source!
            $target = $this->getDummyContent($target);
            
            $segment->setTargetEdit($target);
            $segment->save();
        }
    }
    
    protected function getDummyContent(string $source) : string {
        try {
            $tags = new Tags($source, true);
            $request = $tags->getRequestHtml();
            return $tags->recreateTags(FakeTranslator::translateMarkup($request));

        } catch (Exception $e) {

            return $source;
        }
    }
}