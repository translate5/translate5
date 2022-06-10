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

use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;

/**
 */
class editor_LanguageresourcetaskpivotassocController extends ZfExtended_RestController {

    protected $entityClass = 'MittagQI\Translate5\LanguageResource\TaskPivotAssociation';

    /**
     * @var MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
     */
    protected $entity;
    
    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];
    
    public function init() {
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1050' => 'Referenced language resource not found.',
            'E1051' => 'Cannot remove language resource from task since task is used at the moment.',
        ], 'editor.languageresource.taskassoc');
        parent::init();
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        $taskGuid = $this->getParam('taskGuid');

        if(empty($taskGuid)){
            // TODO: error code for missing param
            throw new ZfExtended_ErrorCodeException();
        }
        $this->view->rows = $this->entity->loadAllForTask($taskGuid);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::postAction()
     */
    public function postAction(){
        try {
            parent::postAction();
        }
        catch(Zend_Db_Statement_Exception $e){
            $m = $e->getMessage();
            //duplicate entries are OK, since the user tried to create it
            if(strpos($m,'SQLSTATE') !== 0 || stripos($m,'Duplicate entry') === false) {
                throw $e;
            }
            //but we have to load and return the already existing duplicate 
            $this->entity->loadByTaskGuidAndTm($this->data->taskGuid, $this->data->languageResourceId);
            $this->view->rows = $this->entity->getDataObject();
        }
    }
    
    public function deleteAction(){
        try {
            $this->entityLoad();
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            if($task->isUsed($this->entity->getTaskGuid())) {
                throw ZfExtended_Models_Entity_Conflict::createResponse('E1050',[
                    'Die Aufgabe wird bearbeitet, die Sprachressource kann daher im Moment nicht von der Aufgabe entfernt werden!'
                ]);
            }
            $clone=clone $this->entity;
            $this->entity->delete();
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing since it was already deleted, and thats ok since user tried to delete it
        }
    }
}
