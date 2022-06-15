<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
abstract class editor_Plugins_SpellCheck_Worker_Abstract extends editor_Segment_Quality_SegmentWorker {
    
    /**
     * Allowd values for setting resourcePool
     * @var array(strings)
     */
    protected static $allowedResourcePools = ['default', 'gui', 'import'];
    /**
     * Praefix for workers resource-name
     * @var string
     */
    protected static $praefixResourceName = 'SpellCheck_';
    
    
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
     * resourcePool for the different SpellCheck-Operations;
     * Possible Values: $this->allowdResourcePools = array('default', 'gui', 'import');
     * @var string
     */
    protected $resourcePool = 'default';
    /**
     * @var ZfExtended_Logger
     */
    private $logger;
    /**
     * @var editor_Plugins_SpellCheck_Configuration
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
        $this->config = new editor_Plugins_SpellCheck_Configuration($this->task);
        $this->logger = null;
        $this->proccessedTags = null;
        $this->skipDueToEqualLangs = ($this->task->getSourceLang() === $this->task->getTargetLang());
        return $return;
    }
    /***
     * Update the progres based on the tagged field in lek segments meta
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::updateProgress()
     */
    public function updateProgress(float $progress = 1){
        $meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        /* @var $meta editor_Models_Segment_Meta */
        $progress = $meta->getSpellcheckSegmentProgress($this->taskGuid);
        parent::updateProgress($progress);
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
        $this->malfunctionState = editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_RECHECK;
        $this->loadedSegmentIds = $this->loadUncheckedSegmentIds();
        if (empty($this->loadedSegmentIds)) {
            $this->loadedSegmentIds = $this->loadNextRecheckSegmentId();
            // if the loading of rechecked segments does not work we need to set them to be defect ...
            $this->malfunctionState = editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_DEFECT;
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
                $this->getLogger()->error('E1326', 'SpellCheck can not work when source and target language are equal?.', ['task' => $this->task]);
                return false;
            }
            return true;
        }
        
        return $this->processSegmentsTags(editor_Segment_Tags::fromSegments($this->task, $this->processingMode, $segments, editor_Segment_Processing::isOperation($this->processingMode)), $slot);
    }
    
    protected function processSegmentsTags(array $segmentsTags, string $slot) : bool {
        //try {
            $processor = new editor_Plugins_SpellCheck_SegmentProcessor($this->task, $this->config, $this->processingMode, $this->isWorkerThread);
            $processor->process($segmentsTags, $slot, true);
            $this->setSpellcheckState($this->loadedSegmentIds, editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_CHECKED);
        //}
        //catch (ZfExtended_ErrorCodeException $exception) {

        //}
        //Malfunction means the termtagger is up, but the send data produces an error in the tagger.
        // 1. we set the segment satus to retag, so each segment is tagged again, segment by segment, not in a bulk manner
        // 2. we log all the data producing the error
        /*catch(editor_Plugins_TermTagger_Exception_Malfunction $exception) {
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
            //if we run in a timeout then set status retag, so that the affected segments are tagged lonely not as batch
            if($exception instanceof editor_Plugins_TermTagger_Exception_TimeOut) {
                // if we are in the retag loop the timeout should be handled as malfunction
                $state = $this->malfunctionState; //this is either retag or defect (later if we are already processing retags)
            }
            else {
                $state = editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_UNTAGGED;
            }

            $this->setTermtagState($this->loadedSegmentIds, $state);

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
        }*/
        return true;
    }
    /**
     * Loads a list of segmentIds where terms are not tagged yet.
     * @return array
     */
    private function loadUncheckedSegmentIds(): array {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $db editor_Models_Db_SegmentMeta */
        
        $db->getAdapter()->beginTransaction();
        $sql = $db->select()
            ->from($db, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('spellcheckState IS NULL OR spellcheckState IN (?)', [editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_UNCHECKED])
            ->order('id')
            ->limit(editor_Plugins_SpellCheck_Configuration::IMPORT_SEGMENTS_PER_CALL)
            ->forUpdate(Zend_Db_Select::FU_MODE_SKIP);
        $segmentIds = $db->fetchAll($sql)->toArray();
        $segmentIds = array_column($segmentIds, 'segmentId');
        
        if(empty($segmentIds)) {
            $db->getAdapter()->commit();
            return $segmentIds;
        }
        
        $db->update(['spellcheckState' => editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_INPROGRESS], [
            'taskGuid = ?' => $this->task->getTaskGuid(),
            'segmentId in (?)' => $segmentIds,
        ]);
        $db->getAdapter()->commit();
        
        return $segmentIds;
    }

    /**
     * returns a list with the next segmentId where terms are marked as to be "rechecked"
     * returns only one segment since this segments has to be single checked
     *
     * @return array
     */
    private function loadNextRecheckSegmentId(): array {
        // get list of unchecked segments
        $dbMeta = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $dbMeta editor_Models_Db_SegmentMeta */
        
        $sql = $dbMeta->select()
            ->from($dbMeta, ['segmentId'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('termtagState IS NULL OR termtagState = ?', [editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_RECHECK])
            ->limit(1);
        
        return array_column($dbMeta->fetchAll($sql)->toArray(), 'segmentId');
    }

    /**
     * sets the meta TermtagState of the given segment ids to the given state
     * @param editor_Models_Task $task
     * @param array $segments
     * @param string $state
     */
    private function setSpellcheckState(array $segments, $state) {
        $segMetaDb = ZfExtended_Factory::get('editor_Models_Db_SegmentMeta');
        /* @var $segMetaDb editor_Models_Db_SegmentMeta */
        $segMetaDb->update(['spellcheckState' => $state], [
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
            ->from($dbMeta, ['segmentId', 'spellcheckState'])
            ->where('taskGuid = ?', $this->task->getTaskGuid())
            ->where('spellcheckState IS NULL OR spellcheckState IN (?)', [
                editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_DEFECT,
                //editor_Plugins_TermTagger_Configuration::SEGMENT_STATE_OVERSIZE
            ]);
        
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
            
            if($defectsegment['spellcheckState'] == editor_Plugins_SpellCheck_Configuration::SEGMENT_STATE_DEFECT) {
                $segmentsToLog[] = $segment->getSegmentNrInTask().'; Source-Text: '.strip_tags($segment->get($fieldManager->getFirstSourceName()));
            }
            else {
                $segmentsToLog[] = $segment->getSegmentNrInTask().': Something wrong with with segment for SpellCheck';
            }
        }
        $this->getLogger()->warn('E1123', 'Some segments could not be checked by the SpellCheck.', [
            'task' => $this->task,
            'uncheckableSegments' => $segmentsToLog,
        ]);
    }

    /*************************** SINGLE SEGMENT PROCESSING ***************************/
    
    protected function processSegmentTags(editor_Segment_Tags $tags, string $slot) : bool {
        
        // skip processing when source & target language are equal
        if($this->skipDueToEqualLangs){
            return true;
        }
        // processes a single tag withot saving it, this is done in the Quaity provider
        //try {
            $processor = new editor_Plugins_SpellCheck_SegmentProcessor($this->task, $this->config, $this->processingMode, $this->isWorkerThread);
            $processor->process([ $tags ], $slot, false);
        /*}
        catch(editor_Plugins_SpellCheck_Exception_Abstract $exception) {
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
        }*/
        return true;
    }
}
