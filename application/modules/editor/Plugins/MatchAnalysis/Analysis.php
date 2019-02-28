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
class editor_Plugins_MatchAnalysis_Analysis extends editor_Plugins_MatchAnalysis_Pretranslation{
    const MAX_ERROR_PER_CONNECTOR = 2;
    
    /***
     * Analysis id 
     * 
     * @var mixed
     */
    protected $analysisId;
    
    
    /***
     * Collection of assigned resources to the task
     * @var array
     */
    protected $connectors=array();
    
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

    /***
     * Segment word count calculator
     * 
     * @var editor_Models_Segment_WordCount
     */
    protected $wordCount;
    
    protected $connectorErrorCount = [];
    
    
    public function __construct(editor_Models_Task $task,$analysisId){
        $this->task=$task;
        $this->analysisId=$analysisId;
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
        $this->initHelper();
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
        
        $segmentModel=ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segmentModel editor_Models_Segment */
        $results=$segmentModel->getRepetitions($this->task->getTaskGuid());
        
        $repetitionsDb=array();
        foreach($results as $key=>$value){
            $repetitionsDb[$value['id']] = $value;
        }
        unset($results);

        $repetitionByHash=array();

        $taskTotalWords=0;
        //init the word count calculator
        $this->initWordCount();
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $this->wordCount->setSegment($segment);
            
            //collect the total words in the task
            $taskTotalWords+=$this->wordCount->getSourceCount();
            
            //calculate and set segment hash
            $segmentHash = $segment->getSourceMd5();
            
            //check if the segment source hash exist in the repetition array
            //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 languageResource
            //segment does not exist in repetition array -> query the tm save the best match rate per tm
            $isRepetition = isset($repetitionsDb[$segment->getId()]) && array_key_exists($segmentHash,$repetitionByHash);
            if($isRepetition){
                $repetitionRate = editor_Services_Connector_FilebasedAbstract::REPETITION_MATCH_VALUE;
                //get the best match rate for the repetition segment, 
                // it can be context match (103%) which is better as the 102% repetition one
                // or the one stored for the repetition could be from a MT. So recalc here always.
                $bestMatchRateResult = $this->getBestMatchrate($segment,false);
                $foundRate = empty($bestMatchRateResult) ? 0 : $bestMatchRateResult->matchrate;
                //save the repetition analysis with either 102% or 103% matchrate
                $this->saveAnalysis($segment, max($repetitionRate, $foundRate), 0);
                
                //the returning result must be the one from the first of the repetition group.
                // that ensures that the repeated segments are pre-translated the same way as the first found one 
                $bestMatchRateResult = $repetitionByHash[$segmentHash];
            }
            else {
                $bestMatchRateResult = $this->getBestMatchrate($segment,true);
                //store the found match for repetition reusage
                $repetitionByHash[$segmentHash] = $bestMatchRateResult;
            }
            
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
                    //store the result for the repetitions too
                    $repetitionByHash[$segmentHash] = $bestMatchRateResult;
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
        
        //remove fuzzy languageResource from opentm2
        $this->removeFuzzyResources();
        
        //update the task total words
        $this->task->setWordCount($taskTotalWords);
        $this->task->save();
        
        return true;
    }
    
    /**
     * Get best match rate result for the segment. If $saveAnalysis is provided, for each best match rate for the tm,
     * one analysis will be saved
     * 
     * @param editor_Models_Segment $segment
     * @param bool $saveAnalysis
     * @return NULL|stdClass
     */
    protected function getBestMatchrate(editor_Models_Segment $segment,$saveAnalysis=true){
        $bestMatchRateResult=null;
        $bestMatchRate=null;
        
        //query the segment for each assigned tm
        foreach ($this->connectors as $languageResourceid => $connector){
            /* @var $connector editor_Services_Connector */
            
            if($this->isDisabledDueErrors($connector, $languageResourceid)) {
                continue;
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
                if($matchRateInternal->matchrate >= $match->matchrate){
                    continue;
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
     * @param editor_Services_Connector $connector
     * @param int $id
     * @return boolean
     */
    protected function isDisabledDueErrors(editor_Services_Connector_Abstract $connector, $id) {
        if(!isset($this->connectorErrorCount[$id]) || $this->connectorErrorCount[$id] <= self::MAX_ERROR_PER_CONNECTOR) {
            return false;
        }
        $langRes = $connector->getLanguageResource();
        $msg = __CLASS__.'::getBestMatchrate: ';
        $msg .= 'Disabled the following Language Resource for analysing and pretranslation due too much errors. ';
        $msg .= 'Errors see above in log. Task: '.$this->task->getTaskName().' - '.$this->task->getTaskNr().' ('.$this->task->getTaskGuid().')';
        $msg .= 'Language Resource: '.$langRes->getName().' ('.$langRes->getServiceName().'; ID: '.$id.')';
        error_log($msg);
        unset($this->connectors[$id]);
        return true;
    }
    
    /**
     * Log and count the connection error
     * @param Exception $e
     * @param int $id
     */
    protected function handleConnectionError(Exception $e, $id) {
        settype($this->connectorErrorCount[$id], 'integer');
        $this->connectorErrorCount[$id]++;
    }
    
    /**
     * @param editor_Services_Connector $connector
     * @param bool $isMtResource
     * @return editor_Services_ServiceResult
     */
    protected function getMatches(editor_Services_Connector_Abstract $connector, editor_Models_Segment $segment, $isMtResource) {
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
            $segment->setTargetEdit("translate5-unique-id[".$segment->getTaskGuid()."]");
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
    public function saveAnalysis($segment,$matchRateResult,$languageResourceid){
        //error_log('segmentNrInTask='.$segment->getSegmentNrInTask().' wordCount:'.$this->wordCount->getSourceCount().' totalCount:');
        $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
        /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
        $matchAnalysis->setSegmentId($segment->getId());
        $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
        $matchAnalysis->setAnalysisId($this->analysisId);
        $matchAnalysis->setLanguageResourceid($languageResourceid);
        $matchAnalysis->setWordCount($this->wordCount->getSourceCount());
        $matchAnalysis->setMatchRate(isset($matchRateResult->matchrate) ? $matchRateResult->matchrate : $matchRateResult);

        $isFuzzy=false;
        if(isset($matchRateResult) && is_object($matchRateResult)){
            //ignore internal fuzzy match target
            $isFuzzy = strpos($matchRateResult->target, 'translate5-unique-id['.$segment->getTaskGuid().']') !== false;
        }
        $matchAnalysis->setInternalFuzzy($isFuzzy  ? 1 : 0);
        $matchAnalysis->save();
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
        
        if(empty($assocs)){
            return array();
        }
        
        foreach ($assocs as $assoc){
            $languageresource=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $languageresource editor_Models_LanguageResources_LanguageResource  */
            
            $languageresource->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Services_Manager');
            /* @var $manager editor_Services_Manager */
            $resource=$manager->getResource($languageresource);
            
            $connector=null;
            try {
                $connector=$manager->getConnector($languageresource,$this->task->getSourceLang(),$this->task->getTargetLang());

                //collect the mt resource, so it can be used for pretranslations if needed
                //collect only if it has matchrate >= of the current set pretranslationMatchrate
                if($resource->getType()==editor_Models_Segment_MatchRateType::TYPE_MT){
                    $this->mtConnectors[]=$connector;
                }
                //store the languageResource
                $this->resources[$languageresource->getId()] = $languageresource;
            } catch (ZfExtended_Exception $e) {
                error_log("Unable to use connector from Language Resource: resourceName: ".$languageresource->getName().', resourceId: '.$languageresource->getId().'. Error was: '.$e);
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
     * Init word counter 
     */
    protected function initWordCount(){
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        
        $langModel->load($this->task->getSourceLang());
        
        $this->wordCount=ZfExtended_Factory::get('editor_Models_Segment_WordCount',[
            $langModel->getRfc5646()
        ]);
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
    
    public function setPretranslate($pretranslate){
        $this->pretranslate=$pretranslate;
    }
    
    public function setInternalFuzzy($internalFuzzy) {
        $this->internalFuzzy=$internalFuzzy;
    }
}