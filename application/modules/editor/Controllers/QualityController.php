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
 * TODO AUTOQA ANNOTATE
 *
 *
 */
class Editor_QualityController extends ZfExtended_RestController {
    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_SegmentQuality';

    /**
     * @var editor_Models_SegmentQuality
     */
    protected $entity;

    /**
     * Retrieves all Qualities for the current task. It is impelmented just as a temporary datamodel for the MQM statistics panel
     * TODO AUTOQA REMOVE
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction(){
        /*
        $taskGuid = $this->getRequest()->getParam('taskGuid');
        if(!is_null($taskGuid)){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            $task->loadByTaskGuid($taskGuid);
            $taskname = $task->getTasknameForDownload(' - '.$this->getFieldType($taskGuid).'.xml');
            
            header('Content-disposition: attachment; filename="'.$taskname.'"');
            header('Content-type: "text/xml"; charset="utf8"', TRUE);
        }
        else{
            $session = new Zend_Session_Namespace();
            $taskGuid = $session->taskGuid;
        }
        if(empty($taskGuid)){
            throw new ZfExtended_NotAuthenticatedException();
        }
        $this->view->text = '.';
        $this->view->children = $this->entity->getQmStatTreeByTaskGuid($taskGuid, $this->getFieldType($taskGuid));
        */
        $this->view->text = 'TEST';
    }
    /**
     * Retrieves the data for the quality filter-panel in the segment grid
     */
    public function filterAction(){
        $this->view->text = 'TEST';
        // TODO IMPLEMENT
    }
    /**
     * Retrieves the data for the segment's qualities-panel in the segment grid
     */
    public function segmentAction(){
        $this->view->text = 'TEST';
        // TODO IMPLEMENT
    }
    /**
     * Retrieves the data for the qualities-overview of a task in the task info panel
     */
    public function taskAction(){
        $this->view->text = 'TEST';
        // TODO IMPLEMENT
    }
    /**
     * Retrieves a single quality
     * {@inheritDoc}
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction(){
        $this->view->text = 'TEST';
        // TODO IMPLEMENT
    }
    /**
     * Sets the false-positive values for a quality
     * This is somehow a misuse of PUT
     * {@inheritDoc}
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction(){
        $this->view->text = 'TEST';
        // TODO IMPLEMENT
    }

    public function deleteAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }

    public function postAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}