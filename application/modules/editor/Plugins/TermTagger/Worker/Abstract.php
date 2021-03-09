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
 * Base Implementation of the Term-Tagger Worker.
 * Tags the segments on task import and also handles the segment saving (the class-name is historical)
 */
abstract class editor_Plugins_TermTagger_Worker_Abstract extends editor_Segment_Quality_SegmentWorker {
    
    /**
     * Allowd values for setting resourcePool
     * @var array(strings)
     */
    protected static $allowedResourcePools = array('default', 'gui', 'import');
    /**
     * Praefix for workers resource-name
     * @var string
     */
    protected static $praefixResourceName = 'TermTagger_';
    
    
    /**
     * overwrites $this->workerModel->maxLifetime
     */
    protected $maxLifetime = '2 HOUR';
    /**
     * Multiple workers are allowed to run simultaneously per task
     * @var string
     */
    protected $onlyOncePerTask = false;
    /**
     * resourcePool for the different TermTagger-Operations;
     * Possible Values: $this->allowdResourcePools = array('default', 'gui', 'import');
     * @var string
     */
    protected $resourcePool = 'default';
    /**
     * @var ZfExtended_Logger
     */
    private $logger;
    /**
     * @var editor_Plugins_TermTagger_Configuration
     */
    private $config;
    /**
     * @var string
     */
    private $malfunctionState;
    /**
     * @var array
     */
    private $loadedSegmentIds;
    /**
     * @var editor_Segment_Tags
     */
    private $proccessedTags;
    /**
     * the termtagger will hang if source and target language are identical, so we skip work in that case
     * see TRANSLATE-2373
     * @var boolean
     */
    private $skipDueToEqualLangs = false;
    
    
    public function init($taskGuid = NULL, $parameters = array()) {
        $return = parent::init($taskGuid, $parameters);
        $this->config = new editor_Plugins_TermTagger_Configuration($this->task);
        $this->logger = null;
        $this->proccessedTags = null;
        $this->skipDueToEqualLangs = ($this->task->getSourceLang() === $this->task->getTargetLang());
        return $return;
    }
    /**
     *
     * @return ZfExtended_Logger
     */
    protected function getLogger() : ZfExtended_Logger {
        if($this->logger == null){
            $this->logger = Zend_Registry::get('logger')->cloneMe($this->config->getLoggerDomain($this->processingMode));
        }
        return $this->logger;
    }    
    /**
     * Needed Implementation for editor_Models_Import_Worker_ResourceAbstract
     * {@inheritDoc}
     * @see editor_Models_Import_Worker_ResourceAbstract::getAvailableSlots()
     */
    protected function getAvailableSlots($resourcePool='default') : array {
        return $this->config->getAvailableResourceSlots($resourcePool);
    }

    protected function raiseNoAvailableResourceException(){
        // E1131 No TermTaggers available, please enable term taggers to import this task.
        throw new editor_Plugins_TermTagger_Exception_Down('E1131', [
            'task' => $this->task
        ]);
    }

    /*************************** THREADED MULTI-SEGMENT PROCESSING ***************************/
    
    protected function loadNextSegments(string $slot) : array {
        $this->malfunctionState = editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_RETAG;
        $this->loadedSegmentIds = $this->loadUntaggedSegmentIds();
        if (empty($this->loadedSegmentIds)) {
            $this->loadedSegmentIds = $this->loadNextRetagSegmentId();
            // if the loading of retagged segments does not work we need to set them to be defect ...
            $this->malfunctionState = editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_DEFECT;
            if(empty($this->loadedSegmentIds)) {
                $this->reportDefectSegments();
                return [];
            }
        }
        $segments = [];
        foreach ($this->loadedSegmentIds as $segmentId) {
            $segment = ZfExtended_Factory::get('editor_Models_Segment');
            /* @var $segment editor_Models_Segment */
            $segment->load($segmentId);
            $segments[] = $segment;
        }
        return $segments;
    }
    
    protected function processSegments(array $segments, string $slot) : bool {
        if($this->skipDueToEqualLangs){
            if($this->isWorkerThread){
                $this->getLogger()->error('E1326', 'TermTagger can not work when source and target language are equal.', ['task' => $this->task]);
                return false;
            }
            return true;
        }
        return $this->processSegmentsTags(editor_Segment_Tags::fromSegments($this->task, $this->processingMode, $segments, ($this->processingMode == editor_Segment_Processing::IMPORT)), $slot);
    }
    
    protected function processSegmentsTags(array $segmentsTags, string $slot) : bool {
        try {
            $processor = new editor_Plugins_TermTagger_SegmentProcessor($this->task, $this->config, $this->processingMode, $this->isWorkerThread);
            $processor->process($segmentsTags, $slot, true);
        }
        //Malfunction means the termtagger is up, but the send data produces an error in the tagger.
        // 1. we set the segment satus to retag, so each segment is tagged again, segment by segment, not in a bulk manner
        // 2. we log all the data producing the error
        catch(editor_Plugins_TermTagger_Exception_Malfunction $exception) {
            $this->setTermtagState($this->loadedSegmentIds, $this->malfunctionState);
            $exception->addExtraData([
                'task' => $this->task
            ]);
            $this->getLogger()->exception($exception, [
                'level' => ZfExtended_Logger::LEVEL_WARN,
                'domain' => $this->config->getLoggerDomain($this->processingMode)
            ]);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            if($exception instanceof editor_Plugins_TermTagger_Exception_Down) {
                $this->config->disableResourceSlot($slot);
            }
            $this->setTermtagState($this->loadedSegmentIds, editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_UNTAGGED);
            $exception->addExtraData([
                'task' => $this->task
            ]);
            $this->getLogger()->exception($exception, [
                'domain' => $this->config->getLoggerDomain($this->processingMode)
            ]);
            if($exception instanceof editor_Plugins_TermTagger_Exception_Open) {
                //editor_Plugins_TermTagger_Exception_Open Exceptions mean mostly that there is problem with the TBX data
                //so we do not create a new worker entry, that imports the task without terminology markup then
                $this->task->setTerminologie(0);
                $this->task->save();
                return false;
            }
        }
        return true;
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
            ->where('termtagState IS NULL OR termtagState IN (?)', [editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_UNTAGGED])
            ->order('id')
            ->limit(editor_Plugins_TermTagger_Configuration::IMPORT_SEGMENTS_PER_CALL)
            ->forUpdate(true);
        $segmentIds = $db->fetchAll($sql)->toArray();
        $segmentIds = array_column($segmentIds, 'segmentId');
        
        if(empty($segmentIds)) {
            $db->getAdapter()->commit();
            return $segmentIds;
        }
        
        $db->update(['termtagState' => editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_INPROGRESS], [
            'taskGuid = ?' => $this->task->getTaskGuid(),
            'segmentId in (?)' => $segmentIds,
        ]);
        $db->getAdapter()->commit();
        
        return $segmentIds;
    }
    
    /**
     * returns a list with the next segmentId where terms are marked as to be "retagged"
     * returns only one segment since this segments has to be single tagged
     *
     * @return array
     */
    private function loadNextRetagSegmentId(): array {
        // get list of untagged segments
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('termtagState IS NULL OR termtagState = ?', [editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_RETAG])
            ->limit(1);
        
        return array_column($dbMeta->fetchAll($sql)->toArray(), 'segmentId');
    }
    /**
     * sets the meta TermtagState of the given segment ids to the given state
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
     * 
     */
    private function reportDefectSegments() {
        // get list of defect segments
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId', 'termtagState'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('termtagState IS NULL OR termtagState IN (?)', [editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_DEFECT, editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE]);
        
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
            $fieldManager->initFields($this->workerModel->getTaskGuid());
            
            if($defectsegment['termtagState'] == editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_DEFECT) {
                $segmentsToLog[] = $segment->getSegmentNrInTask().'; Source-Text: '.strip_tags($segment->get($fieldManager->getFirstSourceName()));
            }
            else {
                $segmentsToLog[] = $segment->getSegmentNrInTask().': Segment to long for TermTagger';
            }
        }
        $this->getLogger()->warn('E1123', 'Some segments could not be tagged by the TermTagger.', [
            'task' => $this->task,
            'untaggableSegments' => $segmentsToLog,
        ]);
    }
    
    /*************************** SINGLE SEGMENT PROCESSING ***************************/
    
    protected function processSegmentTags(editor_Segment_Tags $tags, string $slot) : bool {
        
        // skip processing when source & target language are equal
        if($this->skipDueToEqualLangs){
            return true;
        }
        // processes a single tag withot saving it, this is done in the Quaity provider
        try {
            $processor = new editor_Plugins_TermTagger_SegmentProcessor($this->task, $this->config, $this->processingMode, $this->isWorkerThread);
            $processor->process([ $tags ], $slot, false);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            if($exception instanceof editor_Plugins_TermTagger_Exception_Down) {
                $this->config->disableResourceSlot($slot);
            }
            $processor->getCommunicationsService()->task = '- see directly in event -';
            $exception->addExtraData([
                'task' => $this->task,
                'termTagData' => $processor->getCommunicationsService(),
            ]);
            $this->getLogger()->exception($exception, [
                'domain' => $this->config->getLoggerDomain($this->processingMode)
            ]);
            if($exception instanceof editor_Plugins_TermTagger_Exception_Open) {
                //editor_Plugins_TermTagger_Exception_Open Exceptions mean mostly that there is problem with the TBX data
                //so we have to disable termtagging for this task, otherwise on each segment save we will get such a warning
                $this->task->setTerminologie(0);
                $this->task->save();
                return false;
            }
        }
        return true;
    }}
