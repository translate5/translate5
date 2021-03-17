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
 * editor_Plugins_TermTagger_Worker_TermTaggerImport Class
 */
class editor_Plugins_TermTagger_Worker_TermTaggerImport extends editor_Plugins_TermTagger_Worker_Abstract {
    /**
     * To much segments are causing timeouts when segments are longer
     */
    const SEGMENTS_PER_CALL = 5;
    
    /**
     * Defines the timeout in seconds how long a termtag call with multiple segments may need
     * @var integer
     */
    const TIMEOUT_REQUEST = 300;
    
    /**
     * Defines the timeout in seconds how long the upload and parse request of a TBX may need
     * @var integer
     */
    const TIMEOUT_TBXIMPORT = 600;
    
    /**
     * Logger Domain
     * @var string
     */
    const LOGGER_DOMAIN = 'editor.terminology.import';
    
    /**
     * Fieldname of the source-field of this task
     * @var string
     */
    private $sourceFieldName = '';
        
    
    /**
     * Flag to use the target original field for tagging instead edited
     * @var boolean
     */
    private $useTargetOriginal = false;
    
    /**
     * Flag to keep target original field untouched, must be disabled for import (default false)
     * Must be enabled for (true) for retagging segments!
     * @var boolean
     */
    private $keepTargetOriginal = false;
    
    public function __construct() {
        parent::__construct();
        $this->logger = Zend_Registry::get('logger')->cloneMe(self::LOGGER_DOMAIN);
    }
    
    /**
     * Special Paramters:
     *
     * $parameters['resourcePool']
     * sets the resourcePool for slot-calculation depending on the context.
     * Possible values are all values out of $this->allowedResourcePool
     *
     * $parameters['useTargetOriginal']
     * set to true to use the target original field instead of the target edited field
     * default is false
     *
     * $parameters['keepTargetOriginal']
     * set to true to leave the target original field unmodified!
     * default is false, since not needed for import, but for retagging of segments
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
        $this->useTargetOriginal = !empty($parameters['useTargetOriginal']);
        $parameters['useTargetOriginal'] = $this->useTargetOriginal;
        $this->keepTargetOriginal = !empty($parameters['keepTargetOriginal']);
        $parameters['keepTargetOriginal'] = $this->keepTargetOriginal;
        
        return parent::init($taskGuid, $parameters);
    }
    
    /**
     * Method for CallBack Workers to reset the termtag state
     * @param string $taskGuid
     */
    public function resetTermtagState($taskGuid) {
        $segMetaDb = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $segMetaDb editor_Models_Db_SegmentMeta */
        $segMetaDb->update(['termtagState' => self::SEGMENT_STATE_UNTAGGED], ['taskGuid = ?' => $taskGuid]);
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
        
        // Equal Languages from source & target: prevent the termtagger to process and add further workers
        if(static::isSourceAndTargetLanguageEqual($this->task)){
            $this->logger->error('E1326', 'TermTagger can not work when source and target language are equal.', ['task' => $this->task]);
            return false;
        }
        
        $taskGuid = $this->workerModel->getTaskGuid();
        $segmentIds = $this->loadUntaggedSegmentIds();
        
        if (empty($segmentIds)) {
            $segmentIds = $this->loadNextRetagSegmentId();
            $state = self::SEGMENT_STATE_DEFECT;
            if(empty($segmentIds)) {
                $this->reportDefectSegments($taskGuid);
                return false;
            }
        }
        
        $serverCommunication = $this->fillServerCommunication($segmentIds);
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $termTagger = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service', [$this->logger->getDomain(), self::TIMEOUT_REQUEST, self::TIMEOUT_TBXIMPORT]);
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        
        $slot = $this->workerModel->getSlot();
        if(empty($slot)) {
            return false;
        }
        try {
            $this->checkTermTaggerTbx($termTagger, $slot, $serverCommunication->tbxFile);
            $result = $termTagger->tagterms($slot, $serverCommunication);
            $this->saveSegments($this->markTransFound($result->segments));
        }
        //Malfunction means the termtagger is up, but the send data produces an error in the tagger.
        // 1. we set the segment satus to retag, so each segment is tagged again, segment by segment, not in a bulk manner
        // 2. we log all the data producing the error
        catch(editor_Plugins_TermTagger_Exception_Malfunction $exception) {
            if (empty($state)) {
                $state = self::SEGMENT_STATE_RETAG;
            }
            $this->setTermtagState($segmentIds, $state);
            $exception->addExtraData([
                'task' => $this->task
            ]);
            $this->logger->exception($exception, [
                'level' => ZfExtended_Logger::LEVEL_WARN,
                'domain' => self::LOGGER_DOMAIN
            ]);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            if($exception instanceof editor_Plugins_TermTagger_Exception_Down) {
                $this->disableSlot($slot);
            }
            $this->setTermtagState($segmentIds, self::SEGMENT_STATE_UNTAGGED);
            $exception->addExtraData([
                'task' => $this->task
            ]);
            $this->logger->exception($exception, [
                'domain' => self::LOGGER_DOMAIN
            ]);
            if($exception instanceof editor_Plugins_TermTagger_Exception_Open) {
                //editor_Plugins_TermTagger_Exception_Open Exceptions mean mostly that there is problem with the TBX data
                //so we do not create a new worker entry, that imports the task without terminology markup then
                $this->task->setTerminologie(0);
                $this->task->save();
                return false;
            }
        }
        
        // initialize an new worker-queue-entry to continue 'chained'-import-process
        $this->createNewWorkerChainEntry($taskGuid);
    }
    
    
    private function createNewWorkerChainEntry($taskGuid) {
        $worker = ZfExtended_Factory::get('editor_Plugins_TermTagger_Worker_TermTaggerImport');
        /* @var $worker editor_Plugins_TermTagger_Worker_TermTaggerImport */
        
        if (!$worker->init($taskGuid, $this->workerModel->getParameters())) {
            $this->logger->error('E1122', 'TermTaggerImport Worker can not be initialized!', [
                'taskGuid' => $taskGuid,
                'parameters' => $this->workerModel->getParameters(),
            ]);
            return false;
        }
        $worker->queue($this->workerModel->getParentId());
    }
    
    /**
     * Loads a list of segmentIds where terms are not tagged yet.
     * @return array
     */
    private function loadUntaggedSegmentIds(): array {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $db editor_Models_Db_SegmentMeta */
        
        $db->getAdapter()->beginTransaction();
        $sql = $db->select()
        ->from($db, ['segmentId'])
        ->where('taskGuid = ?', $this->task->getTaskGuid())
        ->where('termtagState IS NULL OR termtagState IN (?)', [$this::SEGMENT_STATE_UNTAGGED])
        ->order('id')
        ->limit(self::SEGMENTS_PER_CALL)
        ->forUpdate(true);
        $segmentIds = $db->fetchAll($sql)->toArray();
        $segmentIds = array_column($segmentIds, 'segmentId');
        
        if(empty($segmentIds)) {
            $db->getAdapter()->commit();
            return $segmentIds;
        }
        
        $db->update(['termtagState' => $this::SEGMENT_STATE_INPROGRESS], [
            'taskGuid = ?' => $this->task->getTaskGuid(),
            'segmentId in (?)' => $segmentIds,
        ]);
        $db->getAdapter()->commit();
        
        return $segmentIds;
    }
    
    /**
     * returns a list with the next segmentId where terms are marked as to be "retag"ged
     * returns only one segment since this segments has to be single tagged
     *
     * @param string $taskGuid
     * @return array
     */
    private function loadNextRetagSegmentId(): array {
        // get list of untagged segments
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        
        $sql = $dbMeta->select()
        ->from($dbMeta, ['segmentId'])
        ->where('taskGuid = ?', $this->task->getTaskGuid())
        ->where('termtagState IS NULL OR termtagState = ?', [$this::SEGMENT_STATE_RETAG])
        ->limit(1);
        
        return array_column($dbMeta->fetchAll($sql)->toArray(), 'segmentId');
    }
    
    /**
     * Creates a ServerCommunication-Object initialized with $task
     * inclusive all field of alls segments provided in $segmentIds
     *
     * @param array $segmentIds
     * @return editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private function fillServerCommunication (array $segmentIds) {
        
        $serverCommunication = ZfExtended_Factory::get('editor_Plugins_TermTagger_Service_ServerCommunication', array($this->task));
        /* @var $serverCommunication editor_Plugins_TermTagger_Service_ServerCommunication */
        
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        $segmentFields = $fieldManager->getFieldList();
        $this->sourceFieldName = $fieldManager->getFirstSourceName();
        
        foreach ($segmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId);
            
            $sourceText = $segment->get($this->sourceFieldName);
            
            foreach ($segmentFields as $field) {
                if($field->type != editor_Models_SegmentField::TYPE_TARGET || !$field->editable) {
                    continue;
                }
                $targetText = $this->useTargetOriginal ? $segment->getTarget() : $segment->getTargetEdit();
                $serverCommunication->addSegment($segment->getId(), $field->name, $sourceText, $targetText);
            }
        }
        
        return $serverCommunication;
    }
    
    /**
     * Save TermTagged-segments for $task povided in $segments
     *
     * @param array $segments
     */
    private function saveSegments($segments) {
        $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        /* @var $fieldManager editor_Models_SegmentFieldManager */
        $fieldManager->initFields($this->workerModel->getTaskGuid());
        
        $responses = $this->groupResponseById($segments);
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        foreach ($responses as $segmentId => $responseGroup) {
            $segment->load($segmentId);
        
            $segment->set($this->sourceFieldName, $responseGroup[0]->source);
            if ($this->task->getEnableSourceEditing()) {
                $segment->set($fieldManager->getEditIndex($this->sourceFieldName), $responseGroup[0]->source);
            }
        
            foreach ($responseGroup as $response) {
                if(! $this->keepTargetOriginal) {
                    $segment->set($response->field, $response->target);
                }
                if(! $this->useTargetOriginal) {
                    $segment->set($fieldManager->getEditIndex($response->field), $response->target);
                }
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
    private function setTermtagState(array $segments, $state) {
        $segMetaDb = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $segMetaDb editor_Models_Db_SegmentMeta */
        $segMetaDb->update(['termtagState' => $state], [
            'taskGuid = ?' => $this->task->getTaskGuid(),
            'segmentId in (?)' => $segments,
        ]);
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
    
    private function reportDefectSegments($taskGuid) {
        // get list of defect segments
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        
        $sql = $dbMeta->select()
        ->from($dbMeta, ['segmentId', 'termtagState'])
        ->where('taskGuid = ?', $this->task->getTaskGuid())
        ->where('termtagState IS NULL OR termtagState IN (?)', [$this::SEGMENT_STATE_DEFECT, $this::SEGMENT_STATE_OVERSIZE]);
        
        $defectSegments = $dbMeta->fetchAll($sql)->toArray();
        
        if (empty($defectSegments)) {
            return;
        }
        
        $segmentsToLog = [];
        foreach ($defectSegments as $defectsegment) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($defectsegment['segmentId']);
            
            $fieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
            /* @var $fieldManager editor_Models_SegmentFieldManager */
            $fieldManager->initFields($taskGuid);
            
            if($defectsegment['termtagState'] == $this::SEGMENT_STATE_DEFECT) {
                $segmentsToLog[] = $segment->getSegmentNrInTask().'; Source-Text: '.strip_tags($segment->get($fieldManager->getFirstSourceName()));
            }
            else {
                $segmentsToLog[] = $segment->getSegmentNrInTask().': Segment to long for TermTagger';
            }
        }
        
        $this->logger->warn('E1123', 'Some segments could not be tagged by the TermTagger.', [
            'task' => $this->task,
            'untaggableSegments' => $segmentsToLog,
        ]);
    }
    
    /***
     * Term tagging takes approximately 15 % of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight() {
        return 15;
    }
}