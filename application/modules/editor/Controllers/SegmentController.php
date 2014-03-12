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

class Editor_SegmentController extends editor_Controllers_EditorrestController {

    protected $entityClass = 'editor_Models_Segment';

    /**
     * @var editor_Models_Segment
     */
    protected $entity;

    /**
     * mappt einen eingehenden Filtertyp auf einen anderen Filtertyp für ein bestimmtes
     * Feld.
     * @var array array($field => array(origType => newType),...)
     */
    protected $_filterTypeMap = array(
        array('qmId' => array('list' => 'listAsString'))
    );

    protected function afterTaskGuidCheck() {
        $sfm = $this->initSegmentFieldManager($this->session->taskGuid);
        $this->_sortColMap = $sfm->getSortColMap();
        parent::afterTaskGuidCheck();
    }
    
    /**
     * initiates the internal SegmentFieldManager
     * @param string $taskGuid
     * @return editor_Models_SegmentFieldManager
     */
    protected function initSegmentFieldManager($taskGuid) {
        return editor_Models_SegmentFieldManager::getForTaskGuid($taskGuid);
    }
    
    public function indexAction() {
        $session = new Zend_Session_Namespace();
        $this->view->rows = $this->entity->loadByTaskGuid($session->taskGuid);
        $this->view->total = $this->entity->getTotalCountByTaskGuid($session->taskGuid);
    }

    public function putAction() {
        $sessionUser = new Zend_Session_Namespace('user');
        $this->entity->load((int) $this->_getParam('id'));

        $this->checkTaskGuidAndEditable();

        $history = $this->entity->getNewHistoryEntity();

        $this->decodePutData();
        $this->convertQmId();

        $allowedToChange = array('qmId', 'stateId', 'autoStateId');
        $allowedAlternatesToChange = $this->entity->getEditableDataIndexList();
        $this->setDataInEntity(array_merge($allowedToChange, $allowedAlternatesToChange), self::SET_DATA_WHITELIST);

        $this->entity->setUserGuid($sessionUser->data->userGuid);
        $this->entity->setUserName($sessionUser->data->userName);
        
        //@todo do this with events
        $workflow = ZfExtended_Factory::get('editor_Workflow_Default');
        /* @var $workflow editor_Workflow_Default */
        $workflow->beforeSegmentSave($this->entity);
        
        $this->entity->validate();
        
        $this->checkPlausibilityOfPut();
        $this->rememberSegmentInSession();

        $history->save();

        foreach($allowedAlternatesToChange as $field) {
            if($this->entity->isModified($field)) {
                $this->entity->updateQmSubSegments($field);
                $this->entity->recreateTermTags($field, strpos($field, editor_Models_SegmentField::TYPE_SOURCE) === 0);
            }
        }

        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
    }
    
    /**
     * checks if current put makes sense to save
     * FIXME adapt me for alternates
     * @return boolean
     */
    protected function checkPlausibilityOfPut() {
        $session = new Zend_Session_Namespace();
        if (
                isset($session->lastSegment)&&
                $session->lastSegment->data->id !== $this->data->id &&
                isset($session->lastSegment->data->edited) &&
                isset($this->data->edited) &&
                $session->lastSegment->data->edited === $this->data->edited &&
                (time()-19 < $session->lastSegment->timestamp || (isset($session->lastCallPlausibiltyError) && $session->lastCallPlausibiltyError ))
                ){
            $alikes = $this->entity->getAlikes($session->taskGuid);
            foreach ($alikes as $alike) {
                if($session->lastSegment->data->id == $alike['id']){
                    return;
                }
            }
            $session->lastCallPlausibiltyError = true;
            ob_start();
            var_dump($this->data);
            $putData = ob_get_clean();
            ob_start();
            var_dump($session->lastSegment->data);
            $prevPutData = ob_get_clean();
            $timespan = time()-$session->lastSegment->timestamp;
            
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            
            $logText = 'The plausibility of the put of the segment with the following data is not given.'; 
            $logText .= 'It has the same edited value as the previously edited segment which has';
            $logText .= ' been edited not more than 19 sec ago but it has not the same segmentId and is no repetition of the previous segment.';
            $logText .= ' Therefore it has not been saved.'."\n";
            $logText .= 'Actually saved Segment PUT data and data to be saved in DB:'."\n";
            $logText .= $putData."\n".print_r($this->entity->getDataObject(),1)."\n\n";
            $logText .= 'Previous saved Segment PUT data and data saved in DB:'."\n";
            $logText .= $prevPutData."\n".print_r($session->lastSegment->entityData,1);
            $logText .= 'Timespan between the 2 puts had been: '.$timespan."\n";
            $logText .= 'Content of $_SERVER had been: '.  print_r($_SERVER,true);
            
            $log->logError('Possible Error on saving a segment!', $logText);
        }
        $session->lastCallPlausibiltyError = false;
    }
   
    /**
     * sets $session->lastSegment with the value of the current segment
     */
    protected function rememberSegmentInSession() {
        $session = new Zend_Session_Namespace();
        $session->lastSegment = new stdClass();
        $session->lastSegment->timestamp = time();
        $session->lastSegment->data = $this->data;
        $session->lastSegment->entityData = $this->entity->getDataObject();
    }
   
    /**
     * checks if current session taskguid matches to loaded segment taskguid
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkTaskGuidAndEditable() {
        $session = new Zend_Session_Namespace();
        $editable = $this->entity->getEditable();
        if (empty($editable) || $session->taskGuid !== $this->entity->getTaskGuid()) {
            //nach außen so tun als ob das gewünschte Entity nicht gefunden wurde
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
    }

    /**
     * Die QM Id wird serverseitig als String und Clientseitig als Array gehandhabt
     * Wenn ein QM Id Array reinkommt, wird es in einen String konvertiert.
     */
    protected function convertQmId() {
        if (isset($this->data->qmId) && is_array($this->data->qmId)) {
            $this->data->qmId = ';' . join(';', $this->data->qmId) . ';';
        }
    }
    
    public function getAction() {
        $this->entity->load($this->_getParam('id'));
        // the following editable value is not intended to be saved, 
        // its only to reuse the taskcheck of checkTaskGuidAndEditable regardless of the editable state
        $this->entity->setEditable(true); 
        $this->checkTaskGuidAndEditable();
        $this->view->rows = $this->entity->getDataObject();
    }

    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->put');
    }

    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__ . '->post');
    }

    /**
     * returns the mapping between fileIds and segment row indizes
     * @return array
     */
    public function filemapAction() {
        $result = new stdClass();
        $session = new Zend_Session_Namespace();
        $result->rows = $this->entity->getFileMap($session->taskGuid);
        $result->total = count($result->rows);
        echo Zend_Json::encode($result, Zend_Json::TYPE_OBJECT);
        exit;
    }

    public function termsAction() {
        //REST Default Controller Settings umgehen um wieder View Scripte zu verwenden:
        $this->getResponse()->setHeader('Content-Type', 'text/html');
        $this->_helper->viewRenderer->setNoRender(false);

        //Erstellung und Setzen der Nutzdaten:
        $session = new Zend_Session_Namespace();
        $terms = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $terms editor_Models_Term */
        $this->view->publicModulePath = APPLICATION_RUNDIR . '/modules/' . Zend_Registry::get('module');
        $this->view->termGroups = $terms->getByTaskGuidAndSegment($session->taskGuid, (int) $this->_getParam('id'));
        $this->view->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }
}