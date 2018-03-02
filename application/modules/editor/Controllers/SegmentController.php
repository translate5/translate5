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

class Editor_SegmentController extends editor_Controllers_EditorrestController {
    use editor_Models_Import_FileParser_TagTrait {
        protectWhitespace as protected traitProtectWhitespace;
    }

    protected $entityClass = 'editor_Models_Segment';

    /**
     * overriding filter class to ensure lower case filtering for segment content fields
     * @var string
     */
    protected $filterClass = 'editor_Models_Filter_SegmentSpecific';
    
    
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
    
    
    public function init() {
      parent::init();
      $this->entity->setEnableWatchlistJoin();
      $this->entity->getFilter()->setSegmentFields(array_keys($this->_sortColMap));
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
        $taskGuid = $this->session->taskGuid;
        $this->view->rows = $this->entity->loadByTaskGuid($taskGuid);
        $this->view->total = $this->entity->totalCountByTaskGuid($taskGuid);
        
        $this->addIsWatchedFlag();
        $this->addFirstEditable();
        $this->addIsFirstFileInfo($taskGuid);
    }
    
    public function nextsegmentsAction() {
        $segmentId = (int) $this->_getParam('segmentId');
        if($this->_getParam('nextFiltered', false) || $this->_getParam('prevFiltered', false)){
            $autoStates = $this->getUsersAutoStateIds();
        }
        $this->entity->load($segmentId);
        $this->checkTaskGuidAndEditable();
        $result = array();
        
        //load only the requested editable segment
        if($this->_getParam('next', false)) {
            $result['next'] = $this->entity->findSurroundingEditables(true);
        }
        if($this->_getParam('prev', false)) {
            $result['prev'] = $this->entity->findSurroundingEditables(false);
        }
        if($this->_getParam('nextFiltered', false)) {
            $result['nextFiltered'] = $this->entity->findSurroundingEditables(true, $autoStates);
        }
        if($this->_getParam('prevFiltered', false)) {
            $result['prevFiltered'] = $this->entity->findSurroundingEditables(false, $autoStates);
        }
        echo Zend_Json::encode((object)$result, Zend_Json::TYPE_OBJECT);
    }
    
    /**
     * returns the index (position) of the requested segment (by segmentId) in the filtered segment list (as it would be given by indexAction)
     * if index is null, that means the segment is not given in the filtered list
     * FIXME: this function uses the segmentNrInTask and NOT the segmentId as normal. How to solve this???
     * Background: in the frontend (visualReview) we dont have teh segmentId, we only have the segmentNrInTask
     */
    public function positionAction() {
        $segmentNrInTask = (int) $this->_getParam('segmentNrInTask');
        $session = new Zend_Session_Namespace();
        $this->entity->loadBySegmentNrInTask($segmentNrInTask, $session->taskGuid);
        //$this->checkTaskGuidAndEditable();
        $index = $this->entity->getIndex();
        if($index === null) {
            throw new ZfExtended_NotFoundException();
        }
        $this->view->segmentNrInTask= $segmentNrInTask;
        
        $this->view->index = $index;
    }
    
    /**
     * returns a list of autoStateIds, belonging to the users role in the currently loaded task
     * is neede for the autostate filter in the frontend 
     */
    protected function getUsersAutoStateIds() {
        $sessionUser = new Zend_Session_Namespace('user');
        $taskUserAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $taskUserAssoc editor_Models_TaskUserAssoc */
        $taskUserAssoc->loadByParams($sessionUser->data->userGuid, $this->session->taskGuid);
        if($taskUserAssoc->getIsPmOverride()) {
            $userRole = 'pm';
        }
        else {
            $userRole = $taskUserAssoc->getRole();
        }
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        $autoStateMap = $states->getRoleToStateMap();
        if(empty($userRole) || empty($autoStateMap[$userRole])) {
            return null;
        }
        return $autoStateMap[$userRole];
    }
    
    /**
     * adds the optional is first of file info to the affected segments
     * @param string $taskGuid
     */
    protected function addIsFirstFileInfo(string $taskGuid) {
        $filemap = $this->entity->getFileMap($taskGuid);
        foreach($filemap as $rowIndex) {
            //omit first file
            if($rowIndex === 0) {
                continue;
            }
            $idx = $rowIndex - $this->offset;
            if($idx < 0 || empty($this->view->rows[$idx])) {
                continue;
            }
            $this->view->rows[$idx]['isFirstofFile'] = true;
        }
    }
    
    /**
     * Adds the first editable segments rowindex for f2 usage in the frontend
     */
    protected function addFirstEditable() {
        //needed only on first page and if we have rows 
        if($this->offset > 0 || empty($this->view->rows)) {
            return;
        }
        //since we dont use metaData otherwise, we can overwrite it completly:
        $this->view->metaData = new stdClass();
        
        //loop over the loaded segments, if there is an editable use that
        foreach($this->view->rows as $idx => $segment) {
            if($segment['editable']) {
                $this->view->metaData->firstEditable = $idx;
                return;
            }
        }
        $this->entity->init($segment);
        $this->view->metaData->firstEditable = $this->entity->findSurroundingEditables(true);
    }
    
    /**
     * For performance Reasons we are calculating the isWatched info this way.
     * A table join is only done if we are filtering for isWatched, 
     * since the this join is very expensive on large data tasks
     * 
     * Since the segment_user_assoc contains currently only the isWatched info,
     * we merge only the data if isWatched is true.
     */
    protected function addIsWatchedFlag() {
        if($this->entity->getEnableWatchlistJoin()) {
            return;
        }
        //get all segment IDs to be returned
        $ids = array_map(function($seg){
            return $seg['id'];
        }, $this->view->rows);
        
        $assoc = ZfExtended_Factory::get('editor_Models_SegmentUserAssoc');
        /* @var $assoc editor_Models_SegmentUserAssoc */
        
        $sessionUser = new Zend_Session_Namespace('user');
        $watched = $assoc->loadIsWatched($ids, $sessionUser->data->userGuid);
        $watchedById = array();
        array_map(function($assoc) use (&$watchedById){
            $watchedById[$assoc['segmentId']] = $assoc['id'];
        }, $watched);
        
        foreach($this->view->rows as &$row) {
            $row['isWatched'] = !empty($watchedById[$row['id']]);
            if($row['isWatched']) {
                $row['segmentUserAssocId'] = $watchedById[$row['id']];
            }
        }
    }

    public function putAction() {
        $sessionUser = new Zend_Session_Namespace('user');
        $this->entity->load((int) $this->_getParam('id'));

        $this->checkTaskGuidAndEditable();

        $history = $this->entity->getNewHistoryEntity();

        $this->decodePutData();
        //set the editing durations for time tracking into the segment object
        settype($this->data->durations, 'object');
        $this->entity->setTimeTrackData($this->data->durations);
        $this->convertQmId();

        $allowedToChange = array('qmId', 'stateId', 'autoStateId', 'matchRate', 'matchRateType');
        
        $allowedAlternatesToChange = $this->entity->getEditableDataIndexList();
        $updateSearchAndSort = array_intersect(array_keys((array)$this->data), $allowedAlternatesToChange);
        $this->checkPlausibilityOfPut($allowedAlternatesToChange);
        $this->sanitizeEditedContent($allowedAlternatesToChange);
        $this->setDataInEntity(array_merge($allowedToChange, $allowedAlternatesToChange), self::SET_DATA_WHITELIST);
        foreach($updateSearchAndSort as $field) {
            $this->entity->updateToSort($field);
        }

        
        $this->entity->setUserGuid($sessionUser->data->userGuid);
        $this->entity->setUserName($sessionUser->data->userName);
        $this->entity->restoreNotModfied();
        
        //@todo do this with events
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $wfm->getActive($this->entity->getTaskGuid())->beforeSegmentSave($this->entity);
        
        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $sessionUser->data->userGuid);
        
        $this->entity->validate();
        
        //FIXME: Introduced with TRANSLATE-885, but is more a hack as a solution. See Issue comments for more information!
        $this->updateTargetHashAndOriginal();

        foreach($allowedAlternatesToChange as $field) {
            if($this->entity->isModified($field)) {
                $this->entity->updateQmSubSegments($field);
            }
        }
        //FIXME check who use this event so we klnow do we trigger this on replace all or not
        $this->events->trigger("beforePutSave", $this, array(
                'entity' => $this->entity,
                'model' => $this->entity, //FIXME model usage is deprecated and should be removed in future (today 2016-08-10) 
                'history' => $history
        ));
        
        //saving history directly before normal saving, 
        // so no exception between can lead to history entries without changing the master segment
        $history->save();
        $this->entity->setTimestamp(null); //see TRANSLATE-922
        $this->entity->save();
        $this->view->rows = $this->entity->getDataObject();
    }
    
    /***
     * Search segment action.
     */
    public function searchAction(){
        
        //check here also if the max allowed character number it is in his limit
        $this->checkSearchStringLength();        
        
        //find all segments for the search parametars
        $result=$this->entity->search($this->getAllParams());
        
        //return the results
        $this->setupResponse([
                'success'=>true,
                'rows'=>$result,
                'hasMqm'=>$this->isMqmTask($this->getParam('taskGuid'))
        ]);
    }
    
    /***
     * Replace all search matches and save the new segment content to the database.
     * Retrun the modefied segments
     */
    public function replaceallAction(){
        $parametars=$this->getAllParams();
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */;
        
        //check if the task has mqm tags
        //replace all is not supported for tasks with mqm
        if($this->isMqmTask($parametars['taskGuid'])){
            $msg =$t->_('Alle ersetzen wird für Aufgaben mit Segmenten mit MQM-Tags nicht unterstützt');
            $this->setupResponse([
                    'success'=>true,
                    'hasMqm'=>true,
                    'message'=>$msg
            ]);
        }
        
        //check here also if the max allowed character number it is in his limit
        $this->checkSearchStringLength();
        
        //find all segments for the search parametars
        $results=$this->entity->search($parametars);
        
        $searchInField=$parametars['searchInField'];
        $matchCase=isset($parametars['matchCase']) ? $parametars['matchCase'] : false;
        
        if(!$results || empty($results)){
            $msg =$t->_('Keine Ergebnisse für die aktuelle Suche!');
            $this->setupResponse([
                    'success'=>true,
                    'message'=>$msg
            ]);
            return;
        }
        
        foreach ($results as $result){
            $replace=ZfExtended_Factory::get('editor_Models_SearchAndReplace_ReplaceMatchesSegment',[
                    $result[$searchInField],//text to be replaced
                    $searchInField,//replace target field
                    $result['id']//segment id
            ]);
            /* @var $replace editor_Models_SearchAndReplace_ReplaceMatchesSegment */
            
            //if the trackchanges are active, setup some trackchanges parametars
            if(isset($parametars['isActiveTrackChanges']) && $parametars['isActiveTrackChanges']){
                $replace->attributeWorkflowstep=$parametars['attributeWorkflowstep'];
                $replace->userColorNr=$parametars['userColorNr'];
                $replace->isActiveTrackChanges=$parametars['isActiveTrackChanges'];
            }
            
            //find matches in the html text and replace them
            $replace->replaceText($parametars['searchField'], $parametars['replaceFieldValue'],$parametars['searchType'],$matchCase);
            
            //init the entity
            $this->entity = ZfExtended_Factory::get($this->entityClass);
            
            //set the segment id
            $this->getRequest()->setParam('id', $result['id']);
            
            //create the object for the data parametars
            $ob=new stdClass();
            $ob->$searchInField=$replace->segmentText;
            $ob->autoStateId=999;
            $ob->durations=$parametars['durations'];
            
            $this->getRequest()->setParam('data',null);
            $this->getRequest()->setParam('data',json_encode((array)$ob));
            
            //trigger the before put action
            $this->beforeActionEvent('put');
            
            //call the put action so the segment is modefied and saved
            $this->putAction();
            
            //trigger the after put action
            $this->afterActionEvent('put');
        }
        
        //return the modefied segments
        $this->setupResponse([
                'success'=>true,
                'rows'=>$results
        ]);
    }
    
    /**
     * Updates the target original and targetMd5 hash for repetition calculation
     * Can be done only in Workflow Step 1 and if all targets were empty on import
     * This is more a hack as a right solution. See TRANSLATE-885 comments for more information!
     * See also in AlikesegmenController!
     */
    protected function updateTargetHashAndOriginal() {
        //TODO: also a check is missing, if task has alternate targets or not.
        // With alternates no recalc is needed at all, since no repetition editor can be used 
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->entity->getTaskGuid());
        if($task->getWorkflowStep() == 1 && (bool) $task->getEmptyTargets()){
            $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$task]);
            /* @var $hasher editor_Models_Segment_RepetitionHash */
            $this->entity->setTargetMd5($hasher->hashTarget($this->entity->getTargetEdit(), $this->entity->getSource()));
            $this->entity->setTarget($this->entity->getTargetEdit());
        }
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
     * Applies the import whitespace replacing to the edited user by the content
     * @param array $fieldnames
     */
    protected function sanitizeEditedContent(array $fieldnames) {
        $nbsp = json_decode('"\u00a0"');
        $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $internalTag editor_Models_Segment_InternalTag */
        foreach($this->data as $key => $data) {
            //consider only changeable datafields:
            if(! in_array($key, $fieldnames)) {
                continue;
            }
            
            //some browsers create nbsp instead of normal whitespaces, since nbsp are removed by the protectWhitespace code below
            // we convert it to usual whitespaces. If there are multiple ones, they are reduced to one then.
            // This is so far the desired behavior. No characters escaped as tag by the import should be addable through the editor.
            // Empty spaces at the very beginning/end are only allowed during editing and now removed for saving.
            $this->data->{$key} = $data = trim(str_replace($nbsp, ' ', $this->data->{$key}));
            
            //since our internal tags are a div span construct with plain content in between, we have to replace them first
            $data = $internalTag->protect($data);

            //this method splits the content at tag boundaries, and sanitizes the textNodes only
            $data = $this->parseSegmentProtectWhitespace($data);

            //revoke the internaltag replacement
            $data = $internalTag->unprotect($data);
            
            //if nothing was changed, everything was OK already
            if($this->entityCleanup($data) === $this->entityCleanup($this->data->{$key})) {
                return;
            }
            $this->restMessages->addWarning('Aus dem Segment wurden nicht darstellbare Zeichen entfernt (mehrere Leerzeichen, Tabulatoren, Zeilenumbrüche etc.)!');
            $this->data->{$key} = $data;
        }
    }
    
    /**
     * This method removes the protected characters instead creating internal tags
     * The user is not allowed to add new internal tags by adding special characters
     * @param string $textNode
     * @param string $xmlBased
     * @return string
     */
    protected function protectWhitespace($textNode, $xmlBased = true) {
        $protected = $this->traitProtectWhitespace($textNode, $xmlBased);
        return strip_tags($protected);
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
    
    protected function isEditable(){
        return empty($this->entity->getEditable());
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
        $this->getResponse()->setHeader('Content-Type', 'text/html', TRUE);
        $this->_helper->viewRenderer->setNoRender(false);

        //Erstellung und Setzen der Nutzdaten:
        $session = new Zend_Session_Namespace();
        $terms = ZfExtended_Factory::get('editor_Models_Term');
        /* @var $terms editor_Models_Term */
        $this->view->publicModulePath = APPLICATION_RUNDIR . '/modules/' . Zend_Registry::get('module');
        $this->view->termGroups = $terms->getByTaskGuidAndSegment($session->taskGuid, (int) $this->_getParam('id'));
        $this->view->translate = ZfExtended_Zendoverwrites_Translate::getInstance();
    }
    
    /**
     * generates a list of available matchratetypes in this task. Mainly for frontend filtering. 
     */
    public function matchratetypesAction() {
        $sfm = $this->initSegmentFieldManager($this->session->taskGuid);
        $mv = $sfm->getView();
        /* @var $mv editor_Models_Segment_MaterializedView */
        $db = ZfExtended_Factory::get(get_class($this->entity->db), array(array(), $mv->getName()));
        $sql = $db->select()->from($db, 'matchrateType')->distinct();
        
        echo Zend_Json::encode($db->fetchAll($sql)->toArray(), Zend_Json::TYPE_ARRAY);
    }
    
    /***
     * Check if the search string length is in between 0 and 1024 characters long
     */
    private function checkSearchStringLength(){
        
        $searchField=$this->getParam('searchField');
        if(!$searchField){
            $e = new ZfExtended_ValidateException();
            $msg=array();
            $msg['searchField']='Das Suchfeld ist leer.';
            $e->setMessage(Zend_Json::encode((object)$msg, Zend_Json::TYPE_OBJECT));
            throw $e;
        }
        
        $length=strlen(utf8_decode($searchField));
        if($length>1024){
            $e = new ZfExtended_ValidateException();
            $msg=array();
            $msg['searchField']='Der Suchbegriff ist zu groß.';
            $e->setMessage(Zend_Json::encode((object)$msg, Zend_Json::TYPE_OBJECT));
            throw $e;
        }
    }
    
    /***
     * Setup the response message,success and rows
     * Expected parametars:
     * 
     *  success => boolean (was the request successful)
     *  message => string  (info message for the response)
     *  rows    => array   (result array)
     *  hasMqm  => boolean (the current task has mqms)
     * 
     * @param array $parametars
     */
    private function setupResponse($parametars){
        echo Zend_Json::encode((object)$parametars, Zend_Json::TYPE_OBJECT);
        exit();
    }
    
    /***
     * Check if the task contains mqm tags for some of the segments
     * @param taskGuid $taskGuid
     * @return boolean
     */
    private function isMqmTask($taskGuid){
        $qms=ZfExtended_Factory::get('editor_Models_Qmsubsegments');
        /* @var $qms  editor_Models_Qmsubsegments */
        return $qms->hasTaskMqm($taskGuid);
    }
}