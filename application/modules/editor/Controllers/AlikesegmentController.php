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
        /* @var $wfh Editor_Controller_Helper_Workflow */
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
        
        $this->fieldLoop(function($field) use ($duration){
            $editField = $field.editor_Models_SegmentFieldManager::_EDIT_PREFIX;
            $duration->$editField = (int)$this->_getParam('duration');
        });
        
        $this->entity->load($editedSegmentId);
        
        $ids = (array) Zend_Json::decode($this->_getParam('alikes', "[]"));
        /* @var $entity editor_Models_Segment */
        $result = array();
        
        $states = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $states editor_Models_Segment_AutoStates */
        
        $userGuid = (new Zend_Session_Namespace('user'))->data->userGuid;
        
        $tua = editor_Models_Loaders_Taskuserassoc::loadByTask($userGuid, $task);
        
        $repetitionUpdater = ZfExtended_Factory::get('editor_Models_Segment_RepetitionUpdater', [ $this->entity, $task->getConfig() ]);
        /* @var $repetitionUpdater editor_Models_Segment_RepetitionUpdater */
        
        $alikeQualities = new editor_Segment_Alike_Qualities($this->entity->getId());
        
        $alikeCount = count($ids);
        foreach($ids as $id) {
            $id = (int) $id;
            try {
                //must be a new instance, otherwise getModifiedData is stored somewhere internally in the entity
                $entity = ZfExtended_Factory::get($this->entityClass);
                //Load alike segment, create a history entry, and overwrite with the data of the target segment
                $entity->load($id);
                $oldHash = $entity->getTargetMd5();
                
                //if neither source nor target hashes are matching,
                // then the segment is no alike of the edited segment => we ignore and log it
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

                $repetitionUpdater->setRepetition($entity);
                //if source editing = true, then fieldLoop loops also over the source field
                //replace the masters tags with the original repetition ones
                //if there was an error in taking over the segment content into the repetition, we return false, so the segment is inored later on
                $fieldLoopResult = $this->fieldLoop([$repetitionUpdater, 'updateSegmentContent']);
                if($fieldLoopResult['target'] === false || $this->isSourceEditable && $fieldLoopResult['source'] === false ) {
                    //the segment has to be ignored!
                    continue;
                }
                // process all quality related stuff
                editor_Segment_Quality_Manager::instance()->processAlikeSegment($entity, $task, $alikeQualities);
  
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
                $entity->updateIsTargetRepeated($entity->getTargetMd5(), $oldHash);

            }
            catch (Exception $e) {
                /**
                 * Jeglicher Fehler im Zusammenhang mit dem Speichervorgang kann applikationsseitig ignoriert werden,
                 * das Segment darf lediglich nicht in der Rückgabe an den Browser mit auftauchen. Somit erscheint das
                 * Segment dem Benutzer als unlektoriert und kann es dann bei Bedarf von Hand lektorieren.
                 * Fürs Debugging wirds geloggt. (if debugs are active)
                 */
                $logger = Zend_Registry::get('logger')->cloneMe('editor.segment.repetition');
                /* @var $logger ZfExtended_Logger */
                $data = [
                    'level' => $logger::LEVEL_DEBUG,
                    'loadedSegmentMaster' => $this->entity->getDataObject(),
                ];
                if(!empty($entity)) {
                    $data['preparedRepetition'] = $entity->getDataObject();
                }
                if(!empty($entity)) {
                    $data['preparedRepetitionHistory'] = $history->getDataObject();
                }
                $logger->exception($e, $data);
                continue;
            }
            //Mit ID als Index um Uniqness sicherzustellen (
            $result[$entity->getId()] = $entity->getDataObject();
        }
        
        //numerisches Array für korrekten JSON Export
        $this->view->rows = array_values($result);

        //TODO: change to websocket
        //the alike segment save does not use the segment saver
        //the segment finish count needs to be updated after the allike segments save
        $task->updateSegmentFinishCount();
        //reload the task
        $task->load($task->getId());
        $this->view->segmentFinishCount=$task->getSegmentFinishCount();
        
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
            //FIXME: it is currently in discussion with the community if the setTargetMd5 is done always on segment save!
            $segment->setTargetMd5($hasher->hashTarget($segment->getTargetEdit(), $segment->getSource()));
        }
    }
    
    /**
     * checks if the chosen segment may be modified
     * if targetMd5 hashes are recalculated on editing, we have to consider also the hashes in the histor of the master segment.
     * See TRANSLATE-885 for details!
     *
     * @param editor_Models_Segment $entity
     * @param int $editedSegmentId
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
        
        //remove the empty segment hashes from the valid list, since empty targets are no repetition
        $validTargetMd5 = array_diff(array_unique($validTargetMd5), [$this->entity::EMPTY_STRING_HASH]);
        
        //the source hash must be just equal
        $sourceMatch = $this->entity->getSourceMd5() === $entity->getSourceMd5();

        //the target hash must be one of the previous hashes or the current one:
        $targetMatch = in_array($entity->getTargetMd5(), $validTargetMd5);

        return $sourceMatch || $targetMatch;
    }
    
    /**
     * Applies the given Closure for each editable segment field
     * (currently only source and target! Since ChangeAlikes are deactivated for alternatives)
     * Closure Parameters: $field → 'target' or 'source'
     *
     * @param Callable $callback
     * @return array
     */
    protected function fieldLoop(Callable $callback) {
        $result = array();
        if($this->isSourceEditable) {
            $result['source'] = $callback('source');
        }
        $result['target'] = $callback('target');
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
