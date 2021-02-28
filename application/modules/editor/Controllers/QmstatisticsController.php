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
 * TODO AUTOQA Move to Quality Model
 * Controler providing view for MQM Statistics
 */
class Editor_QmstatisticsController extends ZfExtended_RestController {
    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_Qmsubsegments';

    /**
     * @var editor_Models_Qmsubsegments
     */
    protected $entity;

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $taskGuid = $this->getRequest()->getParam('taskGuid');//for possiblity to download task outside of editor
        if(!is_null($taskGuid)){
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
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
    }

    /**
     * returns the desired field to get the statistics for (source or target),
     * given by user through parameter "type"
     * if nothing is given or value is invalid returns "target"
     * @return string
     */
    protected function getFieldType($taskGuid) {
        $type = $this->getRequest()->getParam('type');
        $sfm = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $sfm editor_Models_SegmentFieldManager */
        $sfm->initFields($taskGuid);
        if($sfm->getByName($type) === false) {
            return $sfm->getFirstTargetName();
        }
        return $type;
    }

    public function getAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->get');
    }

    public function putAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function deleteAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }

    public function postAction()
    {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}