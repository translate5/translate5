<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
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
            $taskname = $task->getTasknameForDownload(' - '.$this->getFieldType().'.xml');
            
            header('Content-disposition: attachment; filename="'.$taskname.'"');
            header('Content-type: "text/xml"; charset="utf8"');
        }
        else{
            $session = new Zend_Session_Namespace();
            $taskGuid = $session->taskGuid;
        }
        if(empty($taskGuid)){
            throw new ZfExtended_NotAuthenticatedException();
        }
        $this->view->text = '.';
        $this->view->children = $this->entity->getQmStatTreeByTaskGuid($taskGuid, $this->getFieldType());
    }

    /**
     * returns the desired field to get the statistics for (source or target),
     * given by user through parameter "type"
     * if nothing is given or value is invalid returns "target"
     * @return string
     */
    protected function getFieldType() {
        $type = $this->getRequest()->getParam('type');
        $e = $this->entity;
        switch ($type) {
            case $e::TYPE_SOURCE:
            case $e::TYPE_TARGET:
                return $type;
        }
        return $e::TYPE_TARGET;
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