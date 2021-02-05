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
 * 
 * Tags the segments on task import and also handles the segment saving (the class-name is historical)
 * @FIXME: This class should better be called editor_Plugins_TermTagger_Worker what requires renaming the dependencies in all Plugins
 */
class editor_Plugins_TermTagger_Worker_TermTaggerImport extends editor_Segment_Quality_SegmentWorker {
    
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
     * @var string
     */
    private $loggerDomain;
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
     * @var editor_Plugins_TermTagger_Service_ServerCommunication
     */
    private $communicationService;
    /**
     * 
     * @var editor_Segment_Tags
     */
    private $proccessedTags;
    /**
     * the termtagger will hang if source and target language are identical, so we skip work in that case
     * see TRANSLATE-2373
     * @var boolean
     */
    private $skipDueToEqualLangs = false;
    /**
     * This is only needed because the Match nalysis uses this worker to work on the segments directly ...
     * It results in the thrreaded multi-segment processing also saves back the segment directly and uses the direct segment data to instantiate the segment-tags
     * @var boolean
     */
    private $directSegmentProcessing = false;
    
   
    public function init($taskGuid = NULL, $parameters = array()) {
        $return = parent::init($taskGuid, $parameters);
        $this->config = new editor_Plugins_TermTagger_Configuration($this->task);
        $this->loggerDomain = null;
        $this->logger = null;
        $this->proccessedTags = null;
        $this->skipDueToEqualLangs = ($this->task->getSourceLang() === $this->task->getTargetLang());
        return $return;
    }
    /**
     * Needed Implementation for editor_Models_Import_Worker_ResourceAbstract
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        // required param steers the way the segments are processed: either directly or via the LEK_segment_tags
        if(array_key_exists('processSegmentsDirectly', $parameters)){
            $this->directSegmentProcessing = $parameters['processSegmentsDirectly'];
            return true;
        }
        return false;
    }
    /**
     *
     * @return string
     */
    protected function getLoggerDomain() : string {
        if(empty($this->loggerDomain)){
            $this->loggerDomain = ($this->isWorkerThread) ? editor_Plugins_TermTagger_Configuration::IMPORT_LOGGER_DOMAIN : editor_Plugins_TermTagger_Configuration::EDITOR_LOGGER_DOMAIN;
        }
        return $this->loggerDomain;
    }
    /**
     *
     * @return ZfExtended_Logger
     */
    protected function getLogger() : ZfExtended_Logger {
        if($this->logger == null){
            $this->logger = Zend_Registry::get('logger')->cloneMe($this->getLoggerDomain());
        }
        return $this->logger;
    }    
    /**
     * Needed Implementation for editor_Models_Import_Worker_ResourceAbstract
     * {@inheritDoc}
     * @see editor_Models_Import_Worker_ResourceAbstract::getAvailableSlots()
     */
    protected function getAvailableSlots($resourcePool = 'default') : array {
        return $this->config->getAvailableResourceSlots($resourcePool);
    }
    /**
     * Deactivates maintenance in editor-save mode / non-threaded run
     * {@inheritDoc}
     * @see editor_Models_Import_Worker_Abstract::isMaintenanceScheduled()
     */   
    protected function isMaintenanceScheduled() : bool {
        // non-threaded running shall not have dependencies to maintenance scheduling
        if(!$this->isWorkerThread){
            return false;
        }
        return parent::isMaintenanceScheduled();
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
        return $this->processSegmentsTags(editor_Segment_Tags::fromSegments($this->task, !$this->isWorkerThread, $segments, !$this->directSegmentProcessing), $slot);
    }
    
    protected function processSegmentsTags(array $segmentsTags, string $slot) : bool {
        try {
            $this->tagSegmentsTags($segmentsTags, $slot, true);
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
                'domain' => $this->getLoggerDomain()
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
                'domain' => $this->getLoggerDomain()
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
     * Tags the passed segment tags using the given slots, applies the fetched data and save the tags (if wanted)
     * TODO: add evaluation of qualities !
     * @param editor_Segment_Tags[] $segmentsTags
     * @param string $slot
     * @throws ZfExtended_Exception
     */
    private function tagSegmentsTags(array $segmentsTags, string $slot, bool $doSaveTags) {
        
        $this->proccessedTags = [];
        $this->communicationService = $this->config->createServerCommunicationServiceFromTags($segmentsTags, !$this->isWorkerThread);
        $termTagger = ZfExtended_Factory::get(
            'editor_Plugins_TermTagger_Service',
            [$this->getLoggerDomain(),
            $this->config->getRequestTimeout($this->isWorkerThread),
            editor_Plugins_TermTagger_Configuration::TIMEOUT_TBXIMPORT]);
        /* @var $termTagger editor_Plugins_TermTagger_Service */
        $this->config->checkTermTaggerTbx($termTagger, $slot, $this->communicationService->tbxFile);
        $result = $termTagger->tagterms($slot, $this->communicationService);
        $taggedSegments = $this->config->markTransFound($result->segments);
        $taggedSegmentsById = $this->groupResponseById($taggedSegments);
        foreach ($segmentsTags as $tags) { /* @var $tags editor_Segment_Tags */
            $segmentId = $tags->getSegmentId();
            if(array_key_exists($segmentId, $taggedSegmentsById)){
                // bring the tagged segment content back to the tags model
                // TODO: this is Ugly and counteracts the object-oriented nature of the tags
                $this->applyResponseToTags($taggedSegmentsById[$segmentId], $tags);
                     // add qualities if found in the target tags
                $this->findAndAddQualitiesInTags($tags);
                // save the tags, either to the tags-model or back to the segment if configured
                if($doSaveTags){
                    if($this->directSegmentProcessing){
                        $tags->flush();
                    } else {
                        $tags->save(editor_Plugins_TermTagger_QualityProvider::PROVIDER_TYPE);
                        $tags->saveQualities();
                    }
                } else {
                    // makes the currently proccessed tags accessible
                    $this->proccessedTags = $tags;
                }
            } else {
                // TODO FIXME: proper exception
                throw new ZfExtended_Exception('Response of termtagger did not contain the sent segment with ID '.$segmentId);
            }
        }
    }
    /**
     * Finds term tags of certain classes (= certain term stati) in the tags that represent a problem
     * @param editor_Segment_Tags $tags
     */
    private function findAndAddQualitiesInTags(editor_Segment_Tags $tags){
        $providerType = editor_Plugins_TermTagger_QualityProvider::PROVIDER_TYPE;
        $fields = $tags->getFieldsByTargetTagsTypeAndClass($providerType, editor_Models_Term::TRANSSTAT_NOT_FOUND);
        if(count($fields) > 0){
            $tags->addQuality($fields, $providerType, editor_Models_Term::TRANSSTAT_NOT_FOUND);
        }
        $fields = $tags->getFieldsByTargetTagsTypeAndClass($providerType, editor_Models_Term::TRANSSTAT_NOT_DEFINED);
        if(count($fields) > 0){
            $tags->addQuality($fields, $providerType, editor_Models_Term::TRANSSTAT_NOT_DEFINED);
        }
        $fields = $tags->getFieldsByTargetTagsTypeAndClass($providerType, editor_Models_Term::STAT_SUPERSEDED);
        if(count($fields) > 0){
            $tags->addQuality($fields, $providerType, editor_Models_Term::STAT_SUPERSEDED);
        }
        $fields = $tags->getFieldsByTargetTagsTypeAndClass($providerType, editor_Models_Term::STAT_DEPRECATED);
        if(count($fields) > 0){
            $tags->addQuality($fields, $providerType, editor_Models_Term::STAT_DEPRECATED);
        }
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
     * Transfers a single termtagger response to the corresponding tags-model
     * @param array $responseGroup
     * @param editor_Segment_Tags $tags
     * @throws ZfExtended_Exception
     */
    private function applyResponseToTags(array $responseGroup, editor_Segment_Tags $tags) {
        // UGLY: this should better be done by adding real tag-objects instead of setting the tags via text
        if(count($responseGroup) < 1){
            // TODO FIXME: proper exception
            throw new ZfExtended_Exception('Response of termtagger did not contain data for the segment ID '.$tags->getSegmentId());
        }
        $responseFields = $this->groupResponseByField($responseGroup);
        $sourceText = null;
        if($tags->hasOriginalSource()){
            if(!array_key_exists('SourceOriginal', $responseFields)){
                // TODO FIXME: proper exception
                throw new ZfExtended_Exception('Response of termtagger did not contain data for the original source for the segment ID '.$tags->getSegmentId());
            }
            $source = $tags->getOriginalSource();
            $source->setTagsByText($responseFields[$source->getTermtaggerName()]->source);            
        }
        foreach($tags->getTargets() as $target){ /* @var $target editor_Segment_FieldTags */
            if($sourceText === null){
                $sourceText = $target->getFieldText();
            }
            $field = $target->getTermtaggerName();
            if(!array_key_exists($field, $responseFields)){
                // TODO FIXME: proper exception
                throw new ZfExtended_Exception('Response of termtagger did not contain the field "'.$field.'" for the segment ID '.$tags->getSegmentId());
            }
            $target->setTagsByText($responseFields[$field]->target);
        }
        $source = $tags->getSource();
        $source->setTagsByText($sourceText);
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
     * In case of multiple target-fields in one segment, there are multiple responses for the same segment.
     * This function groups this different responses under the same segmentId
     *
     * @param array $responses
     * @return array
     */
    private function groupResponseById($responses) {
        $result = [];
        foreach ($responses as $response) {
            if(!array_key_exists($response->id, $result)){
                $result[$response->id] = [];
            }
            $result[$response->id][] = $response;
        }
        return $result;
    }
    /**
     * 
     * @param array $responseGroup
     * @return array
     */
    private function groupResponseByField($responseGroup) {
        $result = [];
        foreach ($responseGroup as $fieldData) {
            $result[$fieldData->field] = $fieldData;
        }
        return $result;
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
            $this->tagSegmentsTags([ $tags ], $slot, false);
        }
        catch(editor_Plugins_TermTagger_Exception_Abstract $exception) {
            if($exception instanceof editor_Plugins_TermTagger_Exception_Down) {
                $this->config->disableResourceSlot($slot);
            }
            $this->communicationService->task = '- see directly in event -';
            $exception->addExtraData([
                'task' => $this->task,
                'termTagData' => $this->communicationService,
            ]);
            $this->getLogger()->exception($exception, [
                'domain' => $this->getLoggerDomain()
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
    }

    public function getProcessedTags(){
        return $this->proccessedTags;
    }

}