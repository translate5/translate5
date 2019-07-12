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
 *
 */
class editor_TaskusertrackingController extends ZfExtended_RestController {

    protected $entityClass = 'editor_Models_TaskUserTracking';
    
    /**
     * @var editor_Models_TaskUserTracking
     */
    protected $entity;
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $rows = $this->entity->loadAll();
        $this->view->rows = $rows;
        
        // anonymize userinfo for view?
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        /* @var $workflowAnonymize editor_Workflow_Anonymize */
        foreach ($this->view->rows as &$row) {
            $task->loadByTaskGuid($row['taskGuid']);
            if ($task->anonymizeUsers()) {
                $row = $workflowAnonymize->anonymizeUserdata($row['taskGuid'], $row['userGuid'], $row);
            }
        }
    }
}
