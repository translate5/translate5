<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Plugin Bootstrap for Segment Statistics Plugin
 */
class editor_Plugins_ArchiveTaskBeforeDelete_Bootstrap extends ZfExtended_Plugin_Abstract {
    public function init() {
        $this->eventManager->attach('editor_TaskController', 'beforeDeleteAction', array($this, 'handleBeforeDeleteAction'));
    }
    
    public function handleBeforeDeleteAction(Zend_EventManager_Event $event) {
        $params = $event->getParam('params');
        if(empty($params['id'])) {
            return;//bound to wrong action? id should exist
        }
        $task = $event->getParam('entity');
        $task->load($params['id']);
        $taskGuid = $task->getTaskGuid();
        
        $archive = ZfExtended_Factory::get('editor_Plugins_ArchiveTaskBeforeDelete_Archive');
        /* @var $archive editor_Plugins_ArchiveTaskBeforeDelete_Archive */
        if(!$archive->createFor($taskGuid)){
            throw new ZfExtended_Models_Entity_Conflict('Task could not be archived, stopping therefore the delete call');
        }
    }
}