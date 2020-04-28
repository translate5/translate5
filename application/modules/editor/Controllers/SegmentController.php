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
    protected $_filterTypeMap = [
        'qmId' => ['list' => 'listAsString']
    ];
    
    /***
     * Number to divide the segment duration
     * 
     * @var integer
     */
    protected $durationsDivisor=1;
    
    public function preDispatch() {
        parent::preDispatch();
        $sfm = $this->initSegmentFieldManager($this->session->taskGuid);
        //overwrite sortColMap
        $this->_sortColMap = $sfm->getSortColMap();
        $this->entity->setEnableWatchlistJoin();
        $filter = $this->entity->getFilter();
        /* @var $filter editor_Models_Filter_SegmentSpecific */
        //update sortColMap and filterTypeMap in filter instance
        $filter->setMappings($this->_sortColMap, $this->_filterTypeMap);
        $filter->setSegmentFields(array_keys($this->_sortColMap));
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
        
        $rows = $this->entity->loadByTaskGuid($taskGuid);
        $this->view->rows = $rows;
        $this->view->total = $this->entity->totalCountByTaskGuid($taskGuid);
        
        $this->addIsWatchedFlag();
        $this->addFirstEditable();
        $this->addIsFirstFileInfo($taskGuid);
        
        // anonymize users for view? (e.g. comments etc in segment-grid-mouseovers)
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        if ($task->anonymizeUsers()) {
            $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            /* @var $workflowAnonymize editor_Workflow_Anonymize */
            foreach ($this->view->rows as &$row) {
                $row = $workflowAnonymize->anonymizeUserdata($taskGuid, $row['userGuid'], $row);
            }
        }
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
            $e = new ZfExtended_NotFoundException("Segment is not contained in the segment filter");
            $e->setLogging(false); //a wanted exception, disable logging for that
            throw $e;
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
        
        $taskUserAssoc=editor_Models_Loaders_Taskuserassoc::loadByTaskGuid($sessionUser->data->userGuid,$this->session->taskGuid);
        
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

        //check if update is allowed
        $this->checkTaskGuidAndEditable();
        $task = $this->checkTaskState();
        /* @var $task editor_Models_Task */
        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable($this->entity->getTaskGuid(), $sessionUser->data->userGuid);

        //the history entry must be created before the original entity is modified
        $history = $this->entity->getNewHistoryEntity();
        //update the segment
        $updater = ZfExtended_Factory::get('editor_Models_Segment_Updater', [$task]);

        $this->decodePutData();
        
        //set the editing durations for time tracking into the segment object
        settype($this->data->durations, 'object');
        $this->entity->setTimeTrackData($this->data->durations,$this->durationsDivisor);
        $this->convertQmId();

        $allowedToChange = array('qmId', 'stateId', 'autoStateId', 'matchRate', 'matchRateType');
        
        $allowedAlternatesToChange = $this->entity->getEditableDataIndexList();

        $this->checkPlausibilityOfPut($allowedAlternatesToChange);
        $this->sanitizeEditedContent($updater, $allowedAlternatesToChange);

        $this->setDataInEntity(array_merge($allowedToChange, $allowedAlternatesToChange), self::SET_DATA_WHITELIST);
        $this->entity->setUserGuid($sessionUser->data->userGuid);
        $this->entity->setUserName($sessionUser->data->userName);
        
        /* @var $updater editor_Models_Segment_Updater */
        $updater->update($this->entity, $history);
        
        $this->view->rows = $this->entity->getDataObject();
        
        // anonymize users for view? (e.g. comments etc in segment-grid-mouseovers)
        if ($task->anonymizeUsers()) {
            $workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
            /* @var $workflowAnonymize editor_Workflow_Anonymize */
            $row = json_decode(json_encode($this->view->rows), true); // = for anonymizeUserdata(): argument 3 must be of the type array
            $this->view->rows = $workflowAnonymize->anonymizeUserdata($this->entity->getTaskGuid(), $row['userGuid'], $row);
        }
        
        //reload the task so the segment finish count is updated
        $task->load($task->getId());
        
        //set the segmentFinishCount so the frontend viewmodel is updated
        //TODO: this should be updated from the websockets
        $this->view->segmentFinishCount=$task->getSegmentFinishCount();
    }
    
    /***
     * Search segment action.
     */
    public function searchAction(){
        $parameters=$this->getAllParams();
        
        //set the default search parameters if no values are given
        $parameters=$this->entity->setDefaultSearchParameters($parameters);
        
        //check if the required search parameters are in the request
        $this->checkRequiredSearchParameters($parameters);
        $parameters['searchField'] =  htmlentities($parameters['searchField'], ENT_XML1);
        
        //check character number limit
        if(!$this->checkSearchStringLength($parameters['searchField'])){
            return;
        }
        
        //find all segments for the search parameters
        $result=$this->entity->search($parameters);
        
        if(!$result|| empty($result)){
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $t ZfExtended_Zendoverwrites_Translate */;
            $this->view->message= $t->_('Keine Ergebnisse für die aktuelle Suche!');
            return;
        }
        
        $this->view->rows = $result;
        $this->view->total=count($result);
        $this->view->hasMqm=$this->isMqmTask($parameters['taskGuid']);
    }
    
    /***
     * Replace all search matches and save the new segment content to the database.
     * Return the modified segments
     */
    public function replaceallAction(){
        $parameters=$this->getAllParams();

        $task=ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($parameters['taskGuid']);
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();
        /* @var $t ZfExtended_Zendoverwrites_Translate */
        if($task->getUsageMode()==$task::USAGE_MODE_SIMULTANEOUS){
            throw new editor_Models_SearchAndReplace_Exception('E1192',['task'=>$task]);
        }
        
        //check if the required search parameters are in the request
        $this->checkRequiredSearchParameters($parameters);
        $parameters['searchField'] =  htmlentities($parameters['searchField'], ENT_XML1);
        $parameters['replaceField'] =  htmlentities($parameters['replaceField'], ENT_XML1);
        
        //check if the task has mqm tags
        //replace all is not supported for tasks with mqm
        if($this->isMqmTask($parameters['taskGuid'])){
            $this->view->message= $t->_('Alle ersetzen wird für Aufgaben mit Segmenten mit MQM-Tags nicht unterstützt');
            $this->view->hasMqm=true;
            return;
        }
        
        //check character number limit
        if(!$this->checkSearchStringLength($parameters['searchField'])){
            return;
        }
        
        //find all segments for the search parameters
        $results=$this->entity->search($parameters);
        
        $searchInField=$parameters['searchInField'];
        $searchType = $parameters['searchType'] ?? $this->entity::DEFAULT_SEARCH_TYPE;
        $matchCase = isset($parameters['matchCase']) ? (strtolower($parameters['matchCase'])=='true') : false;
        
        if(!$results || empty($results)){
            $this->view->message= $t->_('Keine Ergebnisse für die aktuelle Suche!');
            return;
        }
        $resultsCount=count($results);
        foreach ($results as $idx => $result){
            $replace=ZfExtended_Factory::get('editor_Models_SearchAndReplace_ReplaceMatchesSegment',[
                    $result[$searchInField],//text to be replaced
                    $searchInField,//replace target field
                    $result['id']//segment id
            ]);
            /* @var $replace editor_Models_SearchAndReplace_ReplaceMatchesSegment */
            
            //if the trackchanges are active, setup some trackchanges parameters
            if(isset($parameters['isActiveTrackChanges']) && $parameters['isActiveTrackChanges']){
                $replace->trackChangeTag->attributeWorkflowstep=$parameters['attributeWorkflowstep'];
                $replace->trackChangeTag->userColorNr=$parameters['userColorNr'];
                $replace->trackChangeTag->userTrackingId=$parameters['userTrackingId'];
                $replace->isActiveTrackChanges=$parameters['isActiveTrackChanges'];
            }
            
            //find matches in the html text and replace them
            $replace->replaceText($parameters['searchField'], $parameters['replaceField'],$searchType,$matchCase);
            
            //init the entity
            $this->entity = ZfExtended_Factory::get($this->entityClass);
            
            //set the segment id
            $this->getRequest()->setParam('id', $result['id']);
            
            //create the object for the data parameters
            $ob=new stdClass();
            $ob->$searchInField=$replace->segmentText;
            $ob->autoStateId=999;
            
            //create duration for modefied field
            $duration=new stdClass();
            $duration->$searchInField=$parameters['durations'];
            $ob->durations=$duration;
            
            //set the duration devisor to the number of the results so the duration is splitted equally for each replaced result
            $this->durationsDivisor=$resultsCount;
            
            $this->getRequest()->setParam('data',null);
            $this->getRequest()->setParam('data',json_encode((array)$ob));
            
            //trigger the before put action
            $this->beforeActionEvent('put');
            
            try {
                // call the put action so the segment is modefied and saved
                $this->putAction();
                //trigger the after put action
                $this->afterActionEvent('put');
            }
            catch (Exception $e) {
                /**
                 * Any exception on saving a segment in replace all should not break the whole loop.
                 * But the problem should be logged, and also the user should be informed in the GUI
                 */
                unset($results[$idx]); //remove the unchanged segment from result list, so that GUI knows there was going something wrong
                $task = ZfExtended_Factory::get('editor_Models_Task');
                /* @var $task editor_Models_Task */
                $task->loadByTaskGuid($this->entity->getTaskGuid());
                $this->log->exception($e, [
                    'level' => $this->log::LEVEL_WARN,
                    'task' => $task,
                    'loadedSegment' => $this->entity->getDataObject(),
                ]);
            }
            
            //do not return the segment text, it will be loaded by the segments store
            $result[$searchInField]='';
        }
        
        //return the modefied segments
        $this->view->rows = $results;
        
        //TODO: this should be implemented via websokets
        //reload the task and get the lates segmentFinishCount
        $task->loadByTaskGuid($this->entity->getTaskGuid());
        $this->view->segmentFinishCount=$task->getSegmentFinishCount();
        
        $this->view->total=count($results);
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
        
        $e = new ZfExtended_Exception();
        $e->setMessage('Aufgrund der langsamen Verarbeitung von Javascript im Internet Explorer konnte das Segment nicht korrekt gespeichert werden. Bitte öffnen Sie das Segment nochmals und speichern Sie es erneut. Sollte das Problem bestehen bleiben, drücken Sie bitte F5 und bearbeiten dann das Segment erneut. Vielen Dank!',true);
        throw $e;
    }
   
    /**
     * Applies the import whitespace replacing to the edited user by the content
     * @param array $fieldnames
     */
    protected function sanitizeEditedContent(editor_Models_Segment_Updater $updater, array $fieldnames): void {
        $sanitized = false;
        foreach($this->data as $key => $data) {
            //consider only changeable datafields:
            if(! in_array($key, $fieldnames)) {
                continue;
            }
            $sanitized = $updater->sanitizeEditedContent($data) || $sanitized;
            $this->data->{$key} = $data;
        }
        if($sanitized) {
            $this->restMessages->addWarning('Aus dem Segment wurden nicht darstellbare Zeichen entfernt (mehrere Leerzeichen, Tabulatoren, Zeilenumbrüche etc.)!');
        }
    }
    
    /**
     * checks if current session taskguid matches to loaded segment taskguid
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @return editor_Models_Task
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
     * checks if current task state allows editing
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @return editor_Models_Task
     */
    protected function checkTaskState() {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($this->entity->getTaskGuid());
        if ($task->getState() === $task::STATE_UNCONFIRMED) {
            //nach außen so tun als ob das gewünschte Entity nicht gefunden wurde
            throw new ZfExtended_Models_Entity_NoAccessException('Task is not confirmed so no segment can be edited! Task: '.$task->getTaskGuid());
        }
        return $task;
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
        $this->view->termStatMap = editor_Models_Term::getTermStatusMap();
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
    private function checkSearchStringLength($searchField){
        
        $isValid=true;
        if(empty($searchField) && strlen($searchField===0)){
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $t ZfExtended_Zendoverwrites_Translate */
            
            $errors = array('searchField' => $t->_('Das Suchfeld ist leer.'));
            $e = new ZfExtended_ValidateException();
            $e->setErrors($errors);
            $this->handleValidateException($e);
            $isValid=false;
        }
        
        $length=strlen(utf8_decode($searchField));
        if($length>1024){
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $t ZfExtended_Zendoverwrites_Translate */
            
            $errors = array('searchField' => $t->_('Der Suchbegriff ist zu groß.'));
            $e = new ZfExtended_ValidateException();
            $e->setErrors($errors);
            $this->handleValidateException($e);
            $isValid=false;
        }
        
        return $isValid;
    }
    
    /**
     * Check if the required search parameters are provided
     * 
     * @param array $parameters
     * @throws ZfExtended_ValidateException
     */
    private function checkRequiredSearchParameters(array $parameters){
        if(empty($parameters['searchInField']) || (empty($parameters['searchField']) && strlen($parameters['searchField'])===0) || empty($parameters['searchType'])){
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            /* @var $t ZfExtended_Zendoverwrites_Translate */
            $e = new ZfExtended_ValidateException();
            $e->setMessage($t->_('Missing search parameter. Required parameters: searchInField, searchField, searchType. Given was: ').print_r($parameters,1));
            throw $e;
        }
    }
    
    /***
     * Check if the task contains mqm tags for some of the segments
     * @param string $taskGuid
     * @return boolean
     */
    private function isMqmTask($taskGuid){
        $qms=ZfExtended_Factory::get('editor_Models_Qmsubsegments');
        /* @var $qms  editor_Models_Qmsubsegments */
        return $qms->hasTaskMqm($taskGuid);
    }
}