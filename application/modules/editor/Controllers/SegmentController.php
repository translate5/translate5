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
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    
    /**
     * Initialize event-trigger.
     * 
     * For more Information see definition of parent-class
     * 
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response);
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array(get_class($this)));
    }
    
    
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
        //set the editing durations for time tracking into the segment object
        $this->entity->setTimeTrackData($this->data->durations);
        $this->convertQmId();

        $allowedToChange = array('qmId', 'stateId', 'autoStateId');
        $allowedAlternatesToChange = $this->entity->getEditableDataIndexList();
        $updateToSort = array_intersect(array_keys((array)$this->data), $allowedAlternatesToChange);
        $this->checkPlausibilityOfPut($allowedAlternatesToChange);
        $this->setDataInEntity(array_merge($allowedToChange, $allowedAlternatesToChange), self::SET_DATA_WHITELIST);
        foreach($updateToSort as $toSort) {
            $this->entity->updateToSort($toSort);
        }

        $this->entity->setUserGuid($sessionUser->data->userGuid);
        $this->entity->setUserName($sessionUser->data->userName);
        
        //@todo do this with events
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $wfm->getActive()->beforeSegmentSave($this->entity);
        
        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $sessionUser->data->userGuid);
        
        $this->entity->validate();
        
        $history->save();

        foreach($allowedAlternatesToChange as $field) {
            if($this->entity->isModified($field)) {
                $this->entity->updateQmSubSegments($field);
            }
        }
        
        $this->events->trigger("beforePutSave", $this, array('model' => $this->entity));
        
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
    }
    
    /**
     * checks if current put makes sense to save
     * @param array $fieldnames allowed fieldnames to be saved
     * @return boolean
     */
    protected function checkPlausibilityOfPut($fieldnames) {
        $error = array();
        foreach($this->data as $key => $value) {
            //consider only changeable datafields:
            if(! in_array($key, $fieldnames)) {
                continue;
            }
            //search for the img tag, get the data and remove it
            $regex = '#<img[^>]+class="duplicatesavecheck"[^>]+data-segmentid="([0-9]+)" data-fieldname="([^"]+)"[^>]*>#';
            if(! preg_match($regex, $value, $match)) {
                continue;
            }
            $this->data->{$key} = str_replace($match[0], '', $value);
            //if segmentId and fieldname from content differ to the segment to be saved, throw the error!
            if($match[2] != $key || $match[1] != $this->entity->getId()) {
                $error['real fieldname: '.$key] = array('segmentId' => $match[1], 'fieldName' => $match[2]);
            }
        }
        if(empty($error)) {
            return;
        }
        
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        /* @var $log ZfExtended_Log */
        
        $logText = 'Error on saving a segment!!! Parts of the content in the PUT request ';
        $logText .= 'delivered the following segmentId(s) and fieldName(s):'."\n"; 
        $logText .= print_r($error, 1)."\n";
        $logText .= 'but the request was for segmentId '.$this->entity->getId(); 
        $logText .= ' (compare also the above fieldnames!).'."\n";
        $logText .= 'Therefore the segment has not been saved!'."\n";
        $logText .= 'Actually saved Segment PUT data and data to be saved in DB:'."\n";
        $logText .= print_r($this->data,1)."\n".print_r($this->entity->getDataObject(),1)."\n\n";
        $logText .= 'Content of $_SERVER had been: '.  print_r($_SERVER,true);
        
        $log->logError('Possible Error on saving a segment!', $logText);
        
        $e = new ZfExtended_Models_Entity_NotAcceptableException();
        $e->setMessage('Aufgrund der langsamen Verarbeitung von Javascript im Internet Explorer konnte das Segment nicht korrekt gespeichert werden. Bitte öffnen Sie das Segment nochmals und speichern Sie es erneut. Sollte das Problem bestehen bleiben, drücken Sie bitte F5 und bearbeiten dann das Segment erneut. Vielen Dank!',true);
        throw $e;
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