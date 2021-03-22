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
 * After importing a task a match analysis will be created based on the assigned TM based MatchRessources.
 * To get the analysis results, each segment is send to the assigned MatchRessources. For each queried Sprachressource the received best match rate is stored in a separate DB table.
 * Out of this table all desired analysis are calculated.
 *
 */
class editor_Plugins_MatchAnalysis_Analysis extends editor_Plugins_MatchAnalysis_Pretranslation {
    const MAX_ERROR_PER_CONNECTOR = 2;
    
    /***
     * Analysis id
     *
     * @var mixed
     */
    protected $analysisId;
    
    
    /***
     * Flag if pretranslations is active
     * @var string
     */
    protected $pretranslate=false;

    /***
     * Flag if internal fuzzy will be calculated
     * @var string
     */
    protected $internalFuzzy=false;

    protected $connectorErrorCount = [];
    
    /**
     * Contains an array of segment IDs which have at least one repetition
     * @var array
     */
    protected $segmentIdsWithRepetitions = [];
    
    /**
     * Contains the bestMatchResult to a segment source hash
     * @var array
     */
    protected $repetitionByHash = [];
    
    /**
     * Contains the master segment to a segment source hash
     * @var array
     */
    protected $repetitionMasterSegments = [];
    
    /**
     * Holds the repetition updater
     * @var editor_Models_Segment_RepetitionUpdater
     */
    protected $repetitionUpdater;
    
    
    /**
     * @param editor_Models_Task $task
     * @param integer $analysisId
     * @param string $taskState the real state of the task, the state in the Task Model will be matchanalysis
     */
    public function __construct(editor_Models_Task $task, $analysisId, string $taskState){
        $this->task = $task;
        $this->analysisId=$analysisId;
        $this->taskState = $taskState;
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        parent::__construct($analysisId);
    }
    
    /***
     * Query the language resource service for each segment, calculate the best match rate, and save the match analysis model
     */
    public function calculateMatchrate(){

        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$this->task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */

        $this->initConnectors();
        
        if(empty($this->connectors)){
            return false;
        }
        $this->initRepetitions();
        
        //init the word count calculator
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            //get the best match rate, respecting repetitions
            $bestMatchRateResult = $this->handleRepetition($segment);
            
            if(!$this->pretranslate){
                continue;
            }
            //if TM and Term pretranslation should not be used, we set it null here to trigger MT (if enabled)
            if(!$this->usePretranslateTMAndTerm) {
                $bestMatchRateResult = null;
            }
            $useMt = empty($bestMatchRateResult) || $bestMatchRateResult->matchrate < $this->pretranslateMatchrate;
            $mtUsed=$this->usePretranslateMT && $useMt;
            if($mtUsed) {
                
                $bestMatchRateResult = $this->getMtResult($segment);

                if(!empty($bestMatchRateResult)){
                    //store the result for the repetitions, but only if there is not already a repeated result
                    if(empty($this->repetitionByHash[$segment->getSourceMd5()])) {
                        $this->repetitionByHash[$segment->getSourceMd5()] = $bestMatchRateResult;
                        $segment->setTargetEdit($bestMatchRateResult->target);
                        $this->repetitionMasterSegments[$segment->getSourceMd5()] = $segment;
                    }
                }else{
                    $bestMatchRateResult=null;
                }
            }
            //if no mt is used but the matchrate is lower than the pretranslateMatchrate (match lower than pretranslateMatchrate comming from the TM)
            if(!$mtUsed && !empty($bestMatchRateResult) && $bestMatchRateResult->matchrate < $this->pretranslateMatchrate){
                $bestMatchRateResult=null;
            }
            
            //if best matchrate results are found
            if(!empty($bestMatchRateResult)) {
                $this->updateSegment($segment, $bestMatchRateResult);
            }
        }
        
        $this->clean();

        return true;
    }
    
    /**
     * Checks for segment repetititons and handles them if needed
     * @param editor_Models_Segment $segment
     * @return stdClass
     */
    protected function handleRepetition(editor_Models_Segment $segment) {
        //calculate and set segment hash
        $segmentHash = $segment->getSourceMd5();

        //lazy init, we need only instance, the here given $segment will be overwritten wuth the updateRepetition call
        if(empty($this->repetitionUpdater)) {
            $this->repetitionUpdater = ZfExtended_Factory::get('editor_Models_Segment_RepetitionUpdater', [$segment,$this->task->getConfig()]);
        }
        
        //check if the segment source hash exist in the repetition array
        //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 languageResource
        //segment does not exist in repetition array -> query the tm save the best match rate per tm
        $hasRepetitions = in_array($segment->getId(), $this->segmentIdsWithRepetitions);
        $isRepetition = $hasRepetitions && array_key_exists($segmentHash, $this->repetitionMasterSegments);
        if(! $isRepetition) {
            $bestResult = $this->getBestResult($segment,true);
            if(!$hasRepetitions) {
                // if the segment has no repetitions at all we just return the found result
                return $bestResult;
            }
            //the first segment of multiple repetitions is always stored as master
            $this->repetitionMasterSegments[$segmentHash] = clone $segment;
            //store the found match for repetition reusage
            return $this->repetitionByHash[$segmentHash] = $bestResult;
        }
        $masterHasResult = !empty($this->repetitionByHash[$segmentHash]);
        if($masterHasResult && !$this->repetitionUpdater->updateRepetition($this->repetitionMasterSegments[$segmentHash], $segment)) {
            //if repetition could not be updated, handle segment as it is a segment without repetitions,
            // we may not update the repetitionHash, this would interfer with the other repetitions
            return $this->getBestResult($segment,true);
        }
        $repetitionRate = editor_Services_Connector_FilebasedAbstract::REPETITION_MATCH_VALUE;
        //get the best match rate for the repetition segment,
        // it can be context match (103%) which is better as the 102% repetition one
        // or the one stored for the repetition could be from a MT. So recalc here always.
        $bestResult = $this->getBestResult($segment,false);
        //save the repetition analysis with either 102% or 103% matchrate
        $this->saveAnalysis($segment, max($repetitionRate, $bestResult->matchrate ?? 0), 0);
        
        //if there is no match we can not update the target below
        if(!$masterHasResult) {
            //this means returning null:
            return $this->repetitionByHash[$segmentHash];
        }
        
        //the returning result must be the one from the first of the repetition group.
        // to get the correct content for the repetition we get the value from $segment, which was updated by the repetition updater
        $bestRepeatedResult = clone $this->repetitionByHash[$segmentHash];
        $bestRepeatedResult->target = $segment->getTargetEdit();
        return $bestRepeatedResult;
    }
    
    /**
     * Get best result (best matchrate) for the segment. If $saveAnalysis is provided, for each best match rate for the tm,
     * one analysis will be saved
     *
     * @param editor_Models_Segment $segment
     * @param bool $saveAnalysis
     * @return NULL|stdClass
     */
    protected function getBestResult(editor_Models_Segment $segment,$saveAnalysis=true){
        $bestMatchRateResult=null;
        $bestMatchRate=null;
        
        //query the segment for each assigned tm
        foreach ($this->connectors as $languageResourceid => $connector){
            /* @var $connector editor_Services_Connector */
            
            if($this->isDisabledDueErrors($connector, $languageResourceid)) {
                continue;
            }
            
            //if the current connector supports batch query, enable the batch query for this connector
            if($connector->isBatchQuery() && $this->batchQuery){
                $connector->enableBatch();
            }
            
            $connector->resetResultList();
            $isMtResource = $this->resources[$languageResourceid]->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_MT;
            
            try {
                $matches = $this->getMatches($connector, $segment, $isMtResource);
            }
            catch(Exception $e) {
                $this->handleConnectionError($e, $languageResourceid);
                // in case of an error we produce an empty result container for that query and log the error so that the analysis can proceed
                $matches = ZfExtended_Factory::get('editor_Services_ServiceResult');
            }

            $matchResults = $matches->getResult();
            
            $matchRateInternal=new stdClass();
            $matchRateInternal->matchrate=null;
            //for each match, find the best match rate, and save it
            foreach ($matchResults as $match){
                if($matchRateInternal->matchrate > $match->matchrate){
                    continue;
                }
                
                // If the matchrate is the same, we only check for a new best match if it is from a termcollection
                if ($matchRateInternal->matchrate == $match->matchrate && $match->languageResourceType != 'termcollection') {
                    continue;
                }
                
                if ($match->languageResourceType == 'termcollection') {
                    // - preferred terms > permitted terms
                    // - if multiple permitted terms: take the first
                    if (!is_null($bestMatchRateResult) && $bestMatchRateResult->languageResourceType == 'termcollection') {
                        $bestMatchMetaData = $bestMatchRateResult->metaData;
                        $bestMatchIsPreferredTerm = editor_Models_Term::isPreferredTerm($bestMatchMetaData['status']);
                        if ($bestMatchIsPreferredTerm) {
                            continue;
                        }
                    }
                    // - only allow preferred and permitted terms for best matches
                    $metaData = $match->metaData;
                    $matchIsPreferredTerm = editor_Models_Term::isPreferredTerm($metaData['status']);
                    $matchIsPermittedTerm = editor_Models_Term::isPermittedTerm($metaData['status']);
                    if (!$matchIsPreferredTerm && !$matchIsPermittedTerm) {
                        continue;
                    }
                }
                
                $matchRateInternal=$match;
                
                //store best match rate results(do not compare agains the mt results)
                if($matchRateInternal->matchrate>$bestMatchRate && !$isMtResource){
                    $bestMatchRateResult=$match;
                    $bestMatchRateResult->internalLanguageResourceid=$languageResourceid;
                }
            }
            
            //no match rate is found in the languageResource result
            if($matchRateInternal->matchrate==null){
                $saveAnalysis && $this->saveAnalysis($segment, 0, $languageResourceid);
                $matches->resetResult();
                continue;
            }
            
            //$matchRateInternal contains always the highest matchrate from $matchResults
            //update the bestmatchrate if $matchRateInternal contains highest matchrate
            if($matchRateInternal->matchrate>$bestMatchRate){
                $bestMatchRate=$matchRateInternal->matchrate;
            }
            
            //save the match analyses if needed
            $saveAnalysis && $this->saveAnalysis($segment, $matchRateInternal, $languageResourceid);
            
            //reset the result collection
            $matches->resetResult();
        }
        
        return $bestMatchRateResult;
    }
    
    /**
     * Checks how many errors the connector has produced. If too much, disable it.
     * @param mixed $connector
     * @param integer $id
     * @return boolean
     */
    protected function isDisabledDueErrors($connector, $id) {
        if(!isset($this->connectorErrorCount[$id]) || $this->connectorErrorCount[$id] <= self::MAX_ERROR_PER_CONNECTOR) {
            return false;
        }
        $langRes = $connector->getLanguageResource();
        $this->log->warn('E1101', 'Disabled Language Resource {name} ({service}) for analysing and pretranslation due too much errors.',[
            'task' => $this->task,
            'languageResource' => $langRes,
            'name' => $langRes->getName(),
            'service' => $langRes->getServiceName(),
        ]);
        unset($this->connectors[$id]);
        return true;
    }
    
    /**
     * Log and count the connection error
     * @param Exception $e
     * @param int $id
     */
    protected function handleConnectionError(Exception $e, $id) {
        $this->log->exception($e, [
            'level' => $this->log::LEVEL_WARN,
            'domain' => $this->log->getDomain(),
            'task' => $this->task,
        ]);
        settype($this->connectorErrorCount[$id], 'integer');
        $this->connectorErrorCount[$id]++;
    }
    
    /**
     * @param editor_Services_Connector $connector
     * @param bool $isMtResource
     * @return editor_Services_ServiceResult
     */
    protected function getMatches(editor_Services_Connector $connector, editor_Models_Segment $segment, $isMtResource) {
        if($isMtResource){
            //the resource is of type mt, so we do not need to query the mt for results, since we will receive always the default MT defined matchrate
            //the mt resource only will be searched when pretranslating
            
            //get the query string from the segment
            $queryString = $connector->getQueryString($segment);
            
            $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
            /* @var $internalTag editor_Models_Segment_InternalTag */
            $queryString = $internalTag->toXliffPaired($queryString, true);
            $matches=ZfExtended_Factory::get('editor_Services_ServiceResult',[
                $queryString
            ]);
            /* @var $dummyResult editor_Services_ServiceResult */
            $matches->setLanguageResource($connector->getLanguageResource());
            $matches->addResult('',$connector->getDefaultMatchRate());
            return $matches;
        }
        
        // if the current resource type is MT, query the tm or termcollection
        $matches = $connector->query($segment);
        
        //update the segment with custom target in fuzzy tm
        if($this->internalFuzzy && $connector->isInternalFuzzy()){
            $origTarget = $segment->getTargetEdit();
            $dummyTargetText = self::renderDummyTargetText($segment->getTaskGuid());
            $segment->setTargetEdit($dummyTargetText);
            $connector->update($segment);
            $segment->setTargetEdit($origTarget);
        }
        return $matches;
    }
    
    /***
     * Save match analysis to the database
     *
     * @param editor_Models_Segment $segment
     * @param mixed $matchRateResult : it can be stdClass (opentm2 match result) or int (only the matchrate)
     * @param int $languageResourceid
     */
    protected function saveAnalysis($segment,$matchRateResult,$languageResourceid){
        $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
        /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
        $matchAnalysis->setSegmentId($segment->getId());
        $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
        $matchAnalysis->setAnalysisId($this->analysisId);
        $matchAnalysis->setLanguageResourceid($languageResourceid);
        $matchAnalysis->setWordCount($segment->meta()->getSourceWordCount());
        $matchAnalysis->setMatchRate($matchRateResult->matchrate ?? $matchRateResult);

        $isFuzzy=false;
        $dummyTargetText = self::renderDummyTargetText($segment->getTaskGuid());
        if(isset($matchRateResult) && is_object($matchRateResult)){
            //ignore internal fuzzy match target
            $isFuzzy = strpos($matchRateResult->target, $dummyTargetText) !== false;
        }
        $matchAnalysis->setInternalFuzzy($isFuzzy  ? 1 : 0);
        $matchAnalysis->save();
    }
    
    /**
     * Inits data for repetition handling
     */
    protected function initRepetitions(){
        $segmentModel=ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segmentModel editor_Models_Segment */
        $results=$segmentModel->getRepetitions($this->task->getTaskGuid());
        $this->segmentIdsWithRepetitions = array_column($results, 'id');
        $this->repetitionByHash = [];
        $this->repetitionMasterSegments= [];
    }
    
    /***
     * Init the languageResource connectiors
     *
     * @return array
     */
    protected function initConnectors(){
        $languageResources=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResources editor_Models_LanguageResources_LanguageResource */
        $assocs=$languageResources->loadByAssociatedTaskGuid($this->task->getTaskGuid());
        
        $availableConnectorStatus = [
            editor_Services_Connector_Abstract::STATUS_AVAILABLE,
            //NOT_LOADED must be also considered as AVAILABLE, since OpenTM2 Tms are basically not loaded and therefore we can not decide if they are usable or not
            editor_Services_Connector_FilebasedAbstract::STATUS_NOT_LOADED
        ];
        
        if(empty($assocs)){
            return array();
        }
        
        foreach ($assocs as $assoc){
            $languageresource=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageresource editor_Models_LanguageResources_LanguageResource  */
            
            $languageresource->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $resource = $manager->getResource($languageresource);
            
            $connector=null;
            try {
                $connector=$manager->getConnector($languageresource,$this->task->getSourceLang(),$this->task->getTargetLang(),$this->task->getConfig());

                //throw a worning if the language resource is not available
                $status = $connector->getStatus($resource);
                if(!in_array($status, $availableConnectorStatus)){
                    $this->log->warn('E1239','MatchAnalysis Plug-In: Language resource "{name}" has status "{status}" and is not available for match analysis and pre-translations.',[
                        'task' => $this->task,
                        'name' => $languageresource->getName(),
                        'status' => $status,
                        'moreInfo' => $connector->getLastStatusInfo(),
                        'languageResource' => $languageresource,
                    ]);
                    continue;
                }
                //collect the mt resource, so it can be used for pretranslations if needed
                //collect only if it has matchrate >= of the current set pretranslationMatchrate
                if($resource->getType()==editor_Models_Segment_MatchRateType::TYPE_MT){
                    $this->mtConnectors[]=$connector;
                }
                //store the languageResource
                $this->resources[$languageresource->getId()] = $languageresource;
            } catch (Exception $e) {
                
    //FIXME this try catch should not be needed anymore, after refactoring of December 2020
                
                $errors = [];
                //if the exception is of type ZfExtended_ErrorCodeException, get the additional exception info, and log it
                if($e instanceof ZfExtended_ErrorCodeException){
                    $errors = $e->getErrors() ?? [];
                }
                $this->log->warn('E1102', 'Unable to use connector from Language Resource "{name}". Error was: "{msg}".',array_merge( [
                    'task' => $this->task,
                    'name' => $languageresource->getName(),
                    'msg' => $e->getMessage(),
                    'languageResource' => $languageresource,
                ],$errors));
                $this->log->exception($e, [
                    'task' => $this->task,
                    'level' => $this->log::LEVEL_WARN,
                    'domain' => $this->log->getDomain(),
                ]);
                continue;
            }
            
            //ignore non analysable resources
            if(!$resource->getAnalysable()){
                continue;
            }
            
            $this->connectors[$assoc['id']]=[];

            //if internal fuzzy is active and the connector supports the internal fuzzy calculation, get the fuzzy connector
            if($this->internalFuzzy){
                $this->connectors[$assoc['id']]=$connector->initForFuzzyAnalysis($this->analysisId);
            }else{
                $this->connectors[$assoc['id']]=$connector;
            }
        }
        return $this->connectors;
    }

    /***
     * Remove fuzzy resources from the opentm2
     */
    protected function removeFuzzyResources(){
        if(empty($this->connectors)){
            return;
        }
        
        foreach($this->connectors as $connector){
            if($connector->isInternalFuzzy()){
                $connector->delete();
            }
        }
    }
    
    /***
     * Remove batch cache for all batch connectors if the batch query is enabled
     */
    protected function removeBatchCache(){
        if(empty($this->connectors) || !$this->batchQuery){
            return;
        }
        $model = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_BatchResult');
        /* @var $model editor_Plugins_MatchAnalysis_Models_BatchResult */
        foreach($this->connectors as $connector){
            if($connector->isBatchQuery()){
                $model->deleteForLanguageresource($connector->getLanguageResource()->getId());
            }
        }
    }
    
    /***
     * Remove not required analysis object and data
     */
    public function clean(){
        //remove fuzzy languageResource from opentm2
        $this->removeFuzzyResources();
        //clean the batch query cache if there is one
        $this->removeBatchCache();
        
        $this->connectors = null;
    }
    
    public function setPretranslate($pretranslate){
        $this->pretranslate=$pretranslate;
    }
    
    public function setInternalFuzzy($internalFuzzy) {
        $this->internalFuzzy=$internalFuzzy;
    }
    
    public function setBatchQuery(bool $batchQuery) {
        $this->batchQuery = $batchQuery;
    }
}