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
 * Plugin Bootstrap for ManualStatusCheck Plugin
 * This Plugin checks if all segments has a manual status set. If not, the task can not be finished.
 */
class editor_Plugins_ManualStatusCheck_Bootstrap extends ZfExtended_Plugin_Abstract {
    protected static string $description = 'This Plugin checks if all segments has a manual status set. If not, the task can not be finished.';
    
    public function init() {
        $this->eventManager->attach('editor_Workflow_Default', 'beforeFinish', array($this, 'handleBeforeFinish'));
    }
    
    /**
     * handler for event: editor_Workflow_Default#handleBeforeFinish
     * @param $event Zend_EventManager_Event
     */
    public function handleBeforeFinish(Zend_EventManager_Event $event) {
        $tua = $event->getParam('oldTua');
        /* @var $tua editor_Models_TaskUserAssoc */
        
        $segment = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $segment editor_Models_Db_Segments */
        
        $s = $segment->select()
            ->from($segment, array('unsetStatusCount' => 'COUNT(id)'))
            ->where('stateId = 0')
            ->where('editable = 1')
            ->where('taskGuid = ?', $tua->getTaskGuid());
        
        $row = $segment->fetchRow($s);
        if($row->unsetStatusCount > 0) {
            ZfExtended_Models_Entity_Conflict::addCodes([
                'E1045' => 'The Task can not be set to finished, since not all segments have a set status.',
            ]);
            throw ZfExtended_Models_Entity_Conflict::createResponse('E1045', [
                'Der Task kann nicht abgeschlossen werden, da nicht alle Segmente einen Status gesetzt haben. Bitte verwenden Sie die Filterfunktion um die betroffenen Segmente zu finden.'
            ], ['task' => $event->getParam('task')]);
        }
    }
}