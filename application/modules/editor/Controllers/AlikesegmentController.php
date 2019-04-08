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
 * Editor_AlikeSegmentController 
 * Stellt PUT und GET Methoden zur Verarbeitung der Alike Segmente bereit.
 * Ist nicht zu 100% REST konform:
 *  - ein GET auf die Ressource liefert eine Liste mit den Daten für die Anzeige im Alike Editor zurück.
 *  - ein PUT muss eine Liste mit IDs beinhalten, diese IDs werden dann bearbeitet. 
 *  - Der PUT liefert eine Liste "rows" mit bearbeiteten, kompletten Segment Daten zu den gegebenen IDs zurück.
 *  - Eine Verortung unter der URL /segment/ID/alikes anstatt alikesegment/ID/ wäre imho sauberer, aber mit Zend REST nicht machbar    
 */
class Editor_AlikesegmentController extends editor_Controllers_EditorrestController {

    protected $entityClass = 'editor_Models_Segment';

    /**
     * @var boolean
     */
    protected $isSourceEditable = false;
    
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
    
    
    public function preDispatch() {
        parent::preDispatch();
        $this->entity->setEnableWatchlistJoin();
    }
    
    /**
     * lädt das Zielsegment, und übergibt die Alikes zu diesem Segment an die View zur JSON Rückgabe
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction()
    {
        $this->entity->load((int)$this->_getParam('id'));

        $session = new Zend_Session_Namespace();
        $this->view->rows = $this->entity->getAlikes($session->taskGuid);
        $this->view->total = count($this->view->rows);
    }

    /**
     * Speichert die Daten des Zielsegments (ID in der URL) in die AlikeSegmente. Die IDs der zu bearbeitenden Alike Segmente werden als Array per PUT übergeben.
     * Die Daten der erfolgreich bearbeiteten Segmente werden vollständig gesammelt und als Array an die View übergeben.    
     * @see ZfExtended_RestController::putAction()
     */
    public function putAction() {
        $session = new Zend_Session_Namespace();
        $editedSegmentId = (int)$this->_getParam('id');

        $wfh = $this->_helper->workflow;
        /* @var $wfh ZfExtended_Controller_Helper_Workflow */
        $wfh->checkWorkflowWriteable();

        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($session->taskGuid);
        //Only default Layout and therefore no relais can be processed:
        if(!$sfm->isDefaultLayout()) {
            return;
        }
        
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($session->taskGuid);
        $hasher = $this->getHasher($task);
        
        $sourceMeta = $sfm->getByName(editor_Models_SegmentField::TYPE_SOURCE);
        $this->isSourceEditable = ($sourceMeta !== false && $sourceMeta->editable == 1);

        $duration = new stdClass();
        
        $this->fieldLoop(function($field, $editField, $getter, $setter) use ($duration){
            $duration->$editField = (int)$this->_getParam('duration');
        });
        
        $this->entity->load($editedSegmentId);
        
        $ids = (array) Zend_Json::decode($this->_getParam('alikes', "[]"));
        /* @var $entity editor_Models_Segment */
        $result = array();
        
        $config = Zend_Registry::get('config');
        $qmSubsegmentAlikes = array();
        if($config->runtimeOptions->editor->enableQmSubSegments) {
            $qmSubsegmentAlikes = $this->fieldLoop(function($field, $editField, $getter, $setter) use ($editedSegmentId){
                $qmSubsegmentAlikes = ZfExtended_Factory::get('editor_Models_QmsubsegmentAlikes');
                /* @var $qmSubsegmentAlikesSource editor_Models_QmsubsegmentAlikes */
                $qmSubsegmentAlikes->parseSegment($this->entity->{$getter}(), $editedSegmentId);
                return $qmSubsegmentAlikes;
            });
        }
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $userGuid = (new Zend_Session_Namespace('user'))->data->userGuid;
        $tua->loadByParams($userGuid, $session->taskGuid);
        
        $tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        /* @var $tagHelper editor_Models_Segment_InternalTag */
        
        $trackChangesTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        /* @var $trackChangesTagHelper editor_Models_Segment_TrackChangeTag */
        
        $alikeCount = count($ids);
        foreach($ids as $id) {
            $id = (int) $id;
            try {
                //must be a new instance, otherwise getModifiedData is stored somewhere internally in the entity
                $entity = ZfExtended_Factory::get($this->entityClass);
                //Load alike segment, create a history entry, and overwrite with the data of the target segment
                $entity->load($id);
                
                if(! $this->isValidSegment($entity, $editedSegmentId, $hasher)) {
                    error_log('Falsche Segmente per WDHE bearbeitet: MasterSegment:'.$editedSegmentId.' per PUT übergebene Ids:'.print_r($ids, 1).' IP:'.$_SERVER['REMOTE_ADDR']);
                    continue;
                }

                $history = $entity->getNewHistoryEntity();
                $entity->setTimeTrackData($duration, $alikeCount);
                
                //Entity auf Editierbarkeit überprüfen
                if($entity->getTaskGuid() != $session->taskGuid || ! $entity->isEditable() || $editedSegmentId === $id) {
                    continue;
                }

                //if source editing = true, then fieldLoop loops also over the source field
                $fieldLoopResult = $this->fieldLoop(function($field, $editField, $getter, $setter) use ($id, $entity, $config, $qmSubsegmentAlikes, $tagHelper, $trackChangesTagHelper){
                    $getOriginal = 'get'.ucfirst($field);
                    //Entity befüllen:
                    if($config->runtimeOptions->editor->enableQmSubSegments) {
                        $segmentContent = $qmSubsegmentAlikes[$field]->cloneAndUpdate($id, $field);
                    }
                    else {
                        $segmentContent = $this->entity->{$getter}();
                    }
                    //replace the masters tags with the original repetition ones
                    $originalContent = $entity->{$getOriginal}();
                    $useSourceTags = empty($originalContent);
                    if($useSourceTags) {
                        //if the original had no content, we have to load the source tags. 
                        $originalContent = $entity->getSource();
                    }
                    $originalTags = $tagHelper->get($originalContent);
                    if(empty($originalTags)) {
                        //if there are no original tags we have to init $i with the realTagCount in the targetEdit for below check
                        $i = $tagHelper->count($trackChangesTagHelper->protect($segmentContent));
                    }
                    else {
                        $i = 0;
                        $segmentContent = $trackChangesTagHelper->protect($segmentContent);
                        $segmentContent = $tagHelper->replace($segmentContent, function($foo) use (&$i, $originalTags){
                            return $originalTags[$i++];
                        });
                        //if we use the source tags, the count of tags in source and target content to be used must be equal! 
                        // If this is not the case, the segment can not be processed as repetition! 
                        $segmentContent = $trackChangesTagHelper->unprotect($segmentContent);
                    }
                    //if we use the tags from the source, and their count is different to the tag count in the targetEdit to be used, 
                      // then return false to prevent the saving of that repetition to prevent wrong tags to be used 
                    if($useSourceTags && count($originalTags) !== $i) {
                        return false;
                    }
                    $entity->{$setter}($segmentContent);
                    $entity->updateToSort($editField);
                });
                if($fieldLoopResult['target'] === false || $this->isSourceEditable && $fieldLoopResult['source'] === false ) {
                    //the segment has to be ignored!
                    continue;
                }
                
                $entity->setQmId((string) $this->entity->getQmId());
                if(!is_null($this->entity->getStateId())) {
                    $entity->setStateId($this->entity->getStateId());
                }
                $entity->setUserName($this->entity->getUserName());
                $entity->setUserGuid($this->entity->getUserGuid());
                $entity->setWorkflowStep($this->entity->getWorkflowStep());
                $entity->setWorkflowStepNr($this->entity->getWorkflowStepNr());
                
                $entity->setMatchRate($this->entity->getMatchRate());
                $entity->setMatchRateType($this->entity->getMatchRateType());
                
                $entity->setAutoStateId($states->calculateAlikeState($entity, $tua));
                
                
                $matchRateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
                /* @var $matchRateType editor_Models_Segment_MatchRateType */
                $matchRateType->init($entity->getMatchRateType());
                
                if($matchRateType->isEdited()) {
                    $matchRateType->add($matchRateType::TYPE_AUTO_PROPAGATED);
                    $entity->setMatchRateType((string) $matchRateType);
                }
                
                //is called before save the alike to the DB, after doing all alike data handling (include recalc of the autostate)
                $this->events->trigger('beforeSaveAlike', $this, array(
                        'masterSegment' => $this->entity,
                        'alikeSegment' => $entity,
                        'isSourceEditable' => $this->isSourceEditable,
                ));
                
                $entity->validate();

                //must be called after validation, since validation does not allow original and originalMd5 updates
                $this->updateTargetHashAndOriginal($entity, $hasher);
                
                $history->save();
                $entity->setTimestamp(NOW_ISO); //see TRANSLATE-922
                $entity->save();
            }
            catch (Exception $e) {
                /**
                 * Jeglicher Fehler im Zusammenhang mit dem Speichervorgang kann applikationsseitig ignoriert werden, 
                 * das Segment darf lediglich nicht in der Rückgabe an den Browser mit auftauchen. Somit erscheint das 
                 * Segment dem Benutzer als unlektoriert und kann es dann bei Bedarf von Hand lektorieren.
                 * Fürs Debugging wirds geloggt.
                 */  
                $log = new ZfExtended_Log(false);
                $log->logException($e);
                $msg  = 'Loaded Segment Master '.print_r($this->entity->getDataObject(),1)."\n";
                empty($entity) || $msg .= 'Prepared Segment Alike  '.print_r($entity->getDataObject(),1);
                empty($history) || $msg .= 'Prepared Segment History  '.print_r($history->getDataObject(),1);
                $log->log('Additional log info to previous '.get_class($e).' exception', $msg);
                continue;
            }
            //Mit ID als Index um Uniqness sicherzustellen (
            $result[$entity->getId()] = $entity->getDataObject();
        }
        
        //numerisches Array für korrekten JSON Export
        $this->view->rows = array_values($result); 
        $this->view->total = count($result);
    }
    
    /**
     * @param editor_Models_Task $task
     * @return editor_Models_Segment_RepetitionHash
     */
    protected function getHasher(editor_Models_Task $task) {
        //TODO: also a check is missing, if task has alternate targets or not.
        // With alternates no recalc is needed at all, since no repetition editor can be used 
        if($task->getWorkflowStep() == 1 && (bool) $task->getEmptyTargets()){
            return ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$task]);
        }
        return null;
    }
    
    /**
     * Updates the target hash and targetOriginal value of the repetition, if a hasher instance is given.
     * @param editor_Models_Segment $segment
     * @param editor_Models_Segment_RepetitionHash $hasher
     */
    protected function updateTargetHashAndOriginal(editor_Models_Segment $segment, editor_Models_Segment_RepetitionHash $hasher = null) {
        if($hasher) {
            $segment->setTargetMd5($hasher->hashTarget($segment->getTargetEdit(), $segment->getSource()));
            $segment->setTarget($segment->getTargetEdit());
        }
    }
    
    /**
     * checks if the chosen segment may be modified 
     * if targetMd5 hashes are recalculated on editing, we have to consider also the hashes in the histor of the master segment. 
     * See TRANSLATE-885 for details!
     * 
     * @param editor_Models_Segment $entity
     * @param integer $editedSegmentId
     * @param editor_Models_Segment_RepetitionHash $hasher
     * @return boolean
     */
    protected function isValidSegment(editor_Models_Segment $entity, $editedSegmentId, editor_Models_Segment_RepetitionHash $hasher = null) {
        //without a hasher instance no hashes changes, so we don't have to load the history
        if(empty($hasher)) {
            $validTargetMd5 = [];
        }
        else {
            $historyData = ZfExtended_Factory::get('editor_Models_SegmentHistoryData');
            /* @var $historyData editor_Models_SegmentHistoryData */
            //load first target hardcoded only, since repetitions may not work with multiple alternatives
            $historyEntries = $historyData->loadBySegmentId($editedSegmentId, editor_Models_SegmentField::TYPE_TARGET, 3);
            $validTargetMd5 = array_column($historyEntries, 'originalMd5');
        }
        
        //the current targetMd5 hash is valid in any case
        $validTargetMd5[] = $this->entity->getTargetMd5();
        
        //if neither source nor target hashes are matching,
        // then the segment is no alike of the edited segment => we ignore and log it
        $sourceMatch = $this->entity->getSourceMd5() === $entity->getSourceMd5();
        //either the targets are different, or both targets are empty => the empty target case is also no alike match!
        $targetMatch = (in_array($entity->getTargetMd5(), $validTargetMd5)) && (strlen(trim($entity->getTarget())) > 0);
        
        return $sourceMatch || $targetMatch;
    }
    
    /**
     * Applies the given Closure for each editable segment field 
     * (currently only source and target! Since ChangeAlikes are deactivated for alternatives)
     * Closure Parameters: $field, $editField, $getter, $setter → 'target', 'targetEdit', 'getTargetEdit', 'setTargetEdit'
     * 
     * @param Closure $callback
     * @return array
     */
    protected function fieldLoop(Closure $callback) {
        $result = array();
        if($this->isSourceEditable) {
            $result['source'] = $callback('source', 'sourceEdit', 'getSourceEdit', 'setSourceEdit');
        }
        $result['target'] = $callback('target', 'targetEdit', 'getTargetEdit', 'setTargetEdit');
        return $result;
    }

    public function indexAction(){
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}
