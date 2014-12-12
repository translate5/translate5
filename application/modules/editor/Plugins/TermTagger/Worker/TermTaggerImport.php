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
 * editor_Plugins_TermTagger_Worker_TermTaggerImport Class
 */
class editor_Plugins_TermTagger_Worker_TermTaggerImport extends editor_Plugins_TermTagger_Worker_Abstract {
    /**
     * 
     * @var editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private $serverCommunication = NULL;
    
    /**
     * Fieldname of the source-field of this task
     * @var string
     */
    private $sourceFieldName = '';
    
    
    
    /**
     * Special Paramters:
     * 
     * $parameters['resourcePool']
     * sets the resourcePool for slot-calculation depending on the context.
     * Possible values are all values out of $this->allowedResourcePool
     * 
     * 
     * On very first init:
     * seperate data from parameters which are needed while processing queued-worker.
     * All informations which are only relevant in 'normal processing (not queued)'
     * are not needed to be saved in DB worker-table (aka not send to parent::init as $parameters)
     * 
     * ATTENTION:
     * for queued-operating $parameters saved in parent::init MUST have all necessary paramters
     * to call this init function again on instanceByModel
     * 
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::init()
     */
    public function init($taskGuid = NULL, $parameters = array()) {
        $parametersToSave = array();
        
        if (isset($parameters['resourcePool'])) {
            if (in_array($parameters['resourcePool'], self::$allowedResourcePools)) {
                $this->resourcePool = $parameters['resourcePool'];
                $parametersToSave['resourcePool'] = $this->resourcePool;
            }
        }
        
        return parent::init($taskGuid, $parametersToSave);
    }
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return true;
    } 
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::run()
     */
    public function run() {
        return parent::run();
    }
    
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $taskGuid = $this->workerModel->getTaskGuid();
        $segmentIds = $this->loadUntaggedSegmentIds($taskGuid);

        $state = self::SEGMENT_STATE_RETAG;
        if (empty($segmentIds)) {
            $segmentIds = $this->loadNextRetagSegmentId($taskGuid);
            $state = self::SEGMENT_STATE_DEFECT;
            if(empty($segmentIds)) {
                return false;
            }
        }

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        $serverCommunication = $this->fillServerCommunication($task, $segmentIds);
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */

        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service');
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        if (!$this->checkTermTaggerTbx($this->workerModel->getSlot(), $serverCommunication->tbxFile)) {
            return false;
        }

        $result = $termTagger->tagterms($this->workerModel->getSlot(), $serverCommunication);

        // on error return false, store segment untagged, but mark it as defect / to be retagged
        if (empty($result)) {
            $this->setTermtagState($task, $segmentIds, $state);
            $toreturn = false;
        }
        else {
            $this->saveSegments($task, $result->segments);
            $toreturn = true;
        }

        // to initialize an new worker-queue-entry
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */

        if (!$worker->init($taskGuid, array('resourcePool' => 'import'))) {
            $this->log('TermTaggerImport-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue(ZfExtended_Models_Worker::STATE_WAITING);

        return $toreturn;
    }
    
    /**
     * Loads a list of segmentIds where terms are not tagged yet.
     * Limit for this list is $config->runtimeOptions->termTagger->segmentsPerCall
     * 
     * @param string $taskGuid
     */
    private function loadUntaggedSegmentIds($taskGuid) {
        $config = Zend_Registry::get('config');
        $limit = $config->runtimeOptions->termTagger->segmentsPerCall;
        
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $select = $this->getNextSegmentSelect($db, $taskGuid);
        $sql = $select->where('meta.termtagState IS NULL OR meta.termtagState IN (?)',
                            array($this::SEGMENT_STATE_UNTAGGED)) // later there may will be a state 'targetnotfound'
                    ->order('segment.id')
                    ->limit($limit);
        return $db->fetchAll($sql)->toArray();
    }
    
    /**
     * Loads a list with the next segmentId where terms are marked as to be "retag"ged
     * returns only one segment since this segments has to be single tagged
     * 
     * @param string $taskGuid
     * @return array
     */
    private function loadNextRetagSegmentId($taskGuid) {
        // get list of untagged segments
        $db = ZfExtended_Factory::get('editor_Models_Db_Segments');
        /* @var $db editor_Models_Db_Segments */
        $select = $this->getNextSegmentSelect($db, $taskGuid);
        $sql = $select->where('meta.termtagState = ?',$this::SEGMENT_STATE_RETAG)
                    ->limit(1);
        return $db->fetchAll($sql)->toArray();
    }
    
    /**
     * Helper function
     * @param editor_Models_Db_Segments $db
     * @param string $taskGuid
     * @return Zend_Db_Table_Select
     */
    private function getNextSegmentSelect(editor_Models_Db_Segments $db, $taskGuid) {
        $dbName = $db->info($db::NAME);
        /* @var $db editor_Models_Db_Segments */
        return $db->select()
                    ->from(array('segment' => $dbName), 'segment.id')
                    ->joinLeft(array('meta' => $dbName.'_meta'), 'segment.id = meta.segmentId', array())
                    ->where('segment.taskGuid = ?', $taskGuid);
    }
    
    /**
     * Creates a ServerCommunication-Object initialized with $task
     * inclusive all field of alls segments provided in $segmentIds
     * 
     * @param editor_Models_Task $task
     * @param array $segmentIds
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function fillServerCommunication (editor_Models_Task $task, array $segmentIds) {
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication', array($task));
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        $segmentFields = $fieldManager->getFieldList();
        $this->sourceFieldName = $fieldManager->getFirstSourceName();
        
        foreach ($segmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId['id']);
            $segment->meta()->setTermtagState($this::SEGMENT_STATE_INPROGRESS);
            $segment->meta()->save();
            
            $sourceText = $segment->get($this->sourceFieldName);
            
            foreach ($segmentFields as $field) {
                if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                    continue;
                }
                
                $serverCommunication->addSegment($segment->getId(), $field->name, $sourceText, $segment->getTargetEdit());
            }
        }
        
        return $serverCommunication;
    }
    
    /**
     * Save TermTagged-segments for $task povided in $segments
     * 
     * @param editor_Models_Task $task
     * @param unknown $segments
     */
    private function saveSegments(editor_Models_Task $task, $segments) {
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        
        $responses = $this->groupResponseById($segments);
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        foreach ($responses as $segmentId => $responseGroup) {
            $segment->load($segmentId);
        
            $segment->set($this->sourceFieldName, $responseGroup[0]->source);
            if ($task->getEnableSourceEditing()) {
                $segment->set($fieldManager->getEditIndex($this->sourceFieldName), $responseGroup[0]->source);
            }
        
            foreach ($responseGroup as $response) {
                $segment->set($response->field, $response->target);
                $segment->set($fieldManager->getEditIndex($response->field), $response->target);
            }
        
            $segment->save();
        
            $segment->meta()->setTermtagState($this::SEGMENT_STATE_TAGGED);
            $segment->meta()->save();
        }
    }
    
    /**
     * sets the meta TermtagState of the given segment ids to SEGMENT_STATE_RETAG
     * @param editor_Models_Task $task
     * @param array $segments
     * @param string $state
     */
    private function setTermtagState(editor_Models_Task $task, array $segments, $state) {
        $ids = array_map(function($seg){
            return $seg['id'];
        }, $segments);
        
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        foreach ($ids as $segmentId) {
            try {
                $meta->loadBySegmentId($segmentId);
            } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                $meta->init(array('taskGuid' => $task->getTaskGuid(), 'segmentId' => $segmentId));
            }
            $meta->setTermtagState($state);
            $meta->save();
        }
    }
    
    /**
     * In case of multiple target-fields in one segment, there are multiple responses for the same segment.
     * This function groups this different responses under the same segmentId
     * 
     * @param array $responses
     * @return array grouped
     */
    private function groupResponseById($responses) {
        $return = array();
        
        foreach ($responses as $response) {
            $return[$response->id][] = $response;
        }
        
        return $return;
    }
    
}