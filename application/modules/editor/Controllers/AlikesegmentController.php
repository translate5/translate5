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
        $fieldToProcess = (string)$this->_getParam('process');

        $sfm = editor_Models_SegmentFieldManager::getForTaskGuid($session->taskGuid);
        $fieldMeta = $sfm->getByName($fieldToProcess);
        $isRelais = ($fieldMeta !== false && $fieldMeta->type == editor_Models_SegmentField::TYPE_RELAIS);
        //Only default Layout and therefore no relais can be processed:
        if(!$sfm->isDefaultLayout() || $isRelais) {
            return;
        }
        
        $editField = $fieldToProcess.'Edit';
        $getter = 'get'.$editField;
        $setter = 'set'.$editField;
        
        $this->entity->load($editedSegmentId);
        
        $ids = (array) $this->_getParam('alikes', array());
        $entity = ZfExtended_Factory::get($this->entityClass);
        /* @var $entity editor_Models_Segment */
        $result = array();
        
        $config = Zend_Registry::get('config');
        if($config->runtimeOptions->editor->enableQmSubSegments) {
            $qmSubsegmentAlikes = ZfExtended_Factory::get('editor_Models_QmsubsegmentAlikes');
            /* @var $qmSubsegmentAlikes editor_Models_QmsubsegmentAlikes */
            $qmSubsegmentAlikes->parseSegment($this->entity->{$getter}(), $editedSegmentId);
        }
        
        $states = ZfExtended_Factory::get('editor_Models_SegmentAutoStates');
        /* @var $states editor_Models_SegmentAutoStates */
        
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
                //Entity auf Editierbarkeit überprüfen
                if($entity->getTaskGuid() != $session->taskGuid || ! $entity->isEditable() || $editedSegmentId === $id) {
                    continue;
                }

                //Entity befüllen:
                if($config->runtimeOptions->editor->enableQmSubSegments) {
                    $entity->{$setter}($qmSubsegmentAlikes->cloneAndUpdate($id, $fieldToProcess));
                }
                else {
                    $entity->{$setter}($this->entity->{$getter}());
                }
                $entity->updateToSort($editField);
                
                $entity->setQmId((string) $this->entity->getQmId());
                if(!is_null($this->entity->getStateId())) {
                    $entity->setStateId($this->entity->getStateId());
                }
                $entity->setUserName($this->entity->getUserName());
                $entity->setUserGuid($this->entity->getUserGuid());
                $entity->setWorkflowStep($this->entity->getWorkflowStep());
                $entity->setWorkflowStepNr($this->entity->getWorkflowStepNr());
                $entity->setAutoStateId($states->calculateAlikeState($this->entity->getAutoStateId()));
                
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
