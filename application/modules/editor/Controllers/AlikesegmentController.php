<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
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
    protected $_filterTypeMap = array(
                    array('qmId'=>array('list'=>'listAsString'))
    );
    
    
    public function init() {
      parent::init();
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
        
        $sourceMeta = $sfm->getByName(editor_Models_SegmentField::TYPE_SOURCE);
        $this->isSourceEditable = ($sourceMeta !== false && $sourceMeta->editable == 1);

        $duration = new stdClass();
        
        $this->fieldLoop(function($field, $editField, $getter, $setter) use ($duration){
            $duration->$editField = (int)$this->_getParam('duration');
        });
        
        $this->entity->load($editedSegmentId);
        
        $ids = (array) $this->_getParam('alikes', array());
        $entity = ZfExtended_Factory::get($this->entityClass);
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
        
        $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        /* @var $states editor_Models_SegmentAutoStates */
        
        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $userGuid = (new Zend_Session_Namespace('user'))->data->userGuid;
        $tua->loadByParams($userGuid, $session->taskGuid);
        
        $alikeCount = count($ids);
        foreach($ids as $id) {
            $id = (int) $id;
            try {
                //Alike Segment laden, einen History Eintrag anlegen und mit den Daten des Zielsegments überschreiben
                $entity->load($id);

                //wenn weder die source noch die target hashes übereinstimmen,
                //dann ist das Segment kein Alike zum editierten Segment => überspringen
                $sourceDiffer = $this->entity->getSourceMd5() !== $entity->getSourceMd5();
                //entweder die targets sind unterschiedlich, oder im anderen Fall sind beide Targets ein Leerstring => auch der Leerstring Fall ist keine Übereinstimmung
                $targetDiffer = ($this->entity->getTargetMd5() !== $entity->getTargetMd5()) || (strlen(trim($entity->getTarget())) == 0);
                if($sourceDiffer && $targetDiffer) {
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
                $this->fieldLoop(function($field, $editField, $getter, $setter) use ($id, $entity, $config, $qmSubsegmentAlikes){
                    //Entity befüllen:
                    if($config->runtimeOptions->editor->enableQmSubSegments) {
                        $entity->{$setter}($qmSubsegmentAlikes[$field]->cloneAndUpdate($id, $field));
                    }
                    else {
                        $entity->{$setter}($this->entity->{$getter}());
                    }
                    $entity->updateToSort($editField);
                });
                
                $entity->setQmId((string) $this->entity->getQmId());
                if(!is_null($this->entity->getStateId())) {
                    $entity->setStateId($this->entity->getStateId());
                }
                $entity->setUserName($this->entity->getUserName());
                $entity->setUserGuid($this->entity->getUserGuid());
                $entity->setWorkflowStep($this->entity->getWorkflowStep());
                $entity->setWorkflowStepNr($this->entity->getWorkflowStepNr());
                $entity->setAutoStateId($states->calculateAlikeState($entity, $tua));
                
                //is called before save the alike to the DB, after doing all alike data handling (include recalc of the autostate)
                $this->events->trigger('beforeSaveAlike', $this, array(
                        'masterSegment' => $this->entity,
                        'alikeSegment' => $entity,
                        'isSourceEditable' => $this->isSourceEditable,
                ));
                
                $entity->validate();
                $history->save();
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
