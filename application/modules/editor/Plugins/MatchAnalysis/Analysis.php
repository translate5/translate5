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
     * Collection of fuzzy resource connectors
     * @var array
     */
    protected $fuzzyConnectors=array();
    
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
    
    
    public function __construct(editor_Models_Task $task,$analysisId){
        $this->task=$task;
        $this->analysisId=$analysisId;
        $this->sfm = editor_Models_SegmentFieldManager::getForTaskGuid($task->getTaskGuid());
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

        //init the word count calculator
        $this->initWordCount();
        
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $this->wordCount->setSegment($segment);
            
            //check if the segment source hash exist in the repetition array
            //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 languageResource
            //segment does not exist in repetition array -> query the tm save the best match rate per tm
            if(isset($repetitionsDb[$segment->getId()]) && isset($repetitionByHash[md5($segment->getFieldOriginal('source'))])){
                    
                //get the best match rate for the repetition segment, it can be context match
                $repetitionResult=$this->getBestMatchrate($segment,false);
                
                //save the repetition analysis
                $this->saveAnalysis($segment, editor_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE, 0);
                
                $this->pretranslateSegment($segment,$repetitionResult);
                
                continue;
            }
            
            $bestMatchRateResult=$this->getBestMatchrate($segment,true);
            //add the segment source hash in the array

            $repetitionByHash[md5($segment->getFieldOriginal('source'))]=$bestMatchRateResult;
            
            if($this->pretranslate){
                $this->pretranslateSegment($segment, $bestMatchRateResult);
            }
        }
        
        //remove fuzzy languageResource from opentm2
        $this->removeFuzzyResources();
        
        return true;
    }
    
    /***
     * Get best match rate result for the segment. If $saveAnalysis is provided, for each best match rate for the tm,
     * one analysis will be saved
     * 
     * @param editor_Models_Segment $segment
     * @param boolean $saveAnalysis
     * @return NULL|stdClass
     */
    public function getBestMatchrate(editor_Models_Segment $segment,$saveAnalysis=true){
        $bestMatchRateResult=null;
        $bestMatchRate=null;
        
        //query the segment for each assigned tm
        foreach ($this->connectors as $languageResourceid => $connector){
            /* @var $connector editor_Services_Connector_Abstract */
            
            $matches=[];
            
            $hasFuzzyConnector=isset($this->fuzzyConnectors[$languageResourceid]);
            //use fuzzy connector if internal fuzzy is active
            if($this->internalFuzzy && $hasFuzzyConnector){
                $connector=$this->fuzzyConnectors[$languageResourceid];
            }
            
            $connector->resetResultList();
            $matches=$connector->query($segment);
            
            //update the segment with custom target in fuzzy tm
            if($this->internalFuzzy && $hasFuzzyConnector){
                $segment->setTargetEdit("translate5-unique-id[".$segment->getTaskGuid()."]");
                $connector->update($segment);
            }
            
            //     tm     100     90               
            //     tm1    3       0             
            //     tm2    0       0
            $matchResults=$matches->getResult();
            
            $matchRateInternal=new stdClass();
            $matchRateInternal->matchrate=null;
            //for each match, find the best match rate, and save it
            foreach ($matchResults as $match){
                if($matchRateInternal->matchrate >= $match->matchrate){
                    continue;
                }
                $matchRateInternal=$match;
                
                //store best match rate results
                if($matchRateInternal->matchrate>$bestMatchRate){
                    $bestMatchRateResult=$match;
                    $bestMatchRateResult->internalLanguageResourceid=$languageResourceid;
                }
            }
            
            //no match rate is found in the languageResource result
            if($matchRateInternal->matchrate==null){
                $saveAnalysis && $this->saveAnalysis($segment, null, $languageResourceid);
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
        
        //TODO: pretranslate at the end because of the language resources order(last jira issue) (first find the best match rate from all language resources, and based on this pretranslate if active)
        //Also ignor internal fuzzy matches when pretranslateing
        
        return $bestMatchRateResult;
    }
    
    /***
     * Save match analysis to the database
     * 
     * @param editor_Models_Segment $segment
     * @param mixed $matchRateResult : it can be stdClass (opentm2 match result) or integer (only the matchrate)
     * @param integer $languageResourceid
     */
    public function saveAnalysis($segment,$matchRateResult,$languageResourceid){
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
            $isFuzzy=$matchRateResult->target == "translate5-unique-id[".$segment->getTaskGuid()."]";
        }
        $matchAnalysis->setInternalFuzzy($isFuzzy  ? 1 : 0);
        $matchAnalysis->save();
    }
    
    
    /***
     * Init the languageResource connectiors
     * 
     * @return array
     */
    public function initConnectors(){
        
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
            
            //ignore non analysable resources
            if(!$resource->getAnalysable()){
                continue;
            }
            
            //store the resource type for the languageResource
            $this->resourceType[$languageresource->getId()]=$resource->getType();
            
            $connector=$manager->getConnector($languageresource,$languageresource->getSourceLang(),$languageresource->getTargetLang());
            $this->connectors[$assoc['id']]=[];
            $this->connectors[$assoc['id']]=$connector;
            
            //if internal fuzzy is active, get the fuzzy connector
            if($this->internalFuzzy){
                //this function will clone the existing languageResource in opentm2 under oldname+Fuzzy-Analysis
                $fuzzyConnector=clone $connector->initFuzzyAnalysis();
                if(!empty($fuzzyConnector)){
                    $this->fuzzyConnectors[$assoc['id']]=$fuzzyConnector;
                }
            }
        }
        return $this->connectors;
    }
    
    /***
     * Init word counter 
     */
    public function initWordCount(){
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
    public function removeFuzzyResources(){
        if(empty($this->fuzzyConnectors)){
            return;
        }
        
        foreach($this->fuzzyConnectors as $connector){
            $connector->delete();
        }
    }
    
    protected function isBestSortedPretranslatable($languageResourceid,$matchRate){
        //TODO: check if the match rate is in pretranslatable range
        //TODO: check if the languageResourceid is best sorted
    }
    
    public function setPretranslate($pretranslate){
        $this->pretranslate=$pretranslate;
    }
    
    public function setInternalFuzzy($internalFuzzy) {
        $this->internalFuzzy=$internalFuzzy;
    }
}