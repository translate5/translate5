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
 * To get the analysis results, each segment is send to the assigned MatchRessources. For each queried MatchRessource the received best match rate is stored in a separate DB table. 
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
     * Query the match resource service for each segment, calculate the best match rate, and save the match analysis model
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

        //init task user assoc so the segment can be edited
        $this->initUserAssoc();
        
        //init the word count calculator
        $this->initWordCount();
        
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $this->wordCount->setSegment($segment);
            
            //check if the segment source hash exist in the repetition array
            //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 tmmt
            //segment does not exist in repetition array -> query the tm save the best match rate per tm
            if(isset($repetitionsDb[$segment->getId()]) && isset($repetitionByHash[md5($segment->getFieldOriginal('source'))])){
                    
                //get the best match rate for the repetition segment, it can be context match
                $repetitionResult=$this->getBestMatchrate($segment,false);
                
                //save the repetition analysis
                $this->saveAnalysis($segment, editor_Plugins_MatchResource_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE, 0);
                
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
        
        //remove fuzzy tmmt from opentm2
        $this->removeFuzzyResources();
        
        //remove the entry in the task user assoc table
        $this->removeUserAssoc();
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
        foreach ($this->connectors as $tmmtid => $connector){
            /* @var $connector editor_Plugins_MatchResource_Services_Connector_Abstract */
            
            $matches=[];
            
            $hasFuzzyConnector=isset($this->fuzzyConnectors[$tmmtid]);
            //use fuzzy connector if internal fuzzy is active
            if($this->internalFuzzy && $hasFuzzyConnector){
                $connector=$this->fuzzyConnectors[$tmmtid];
            }
            
            $connector->resetResultList();
            $matches=$connector->query($segment);
            
            //update the segment with custom target in fuzzy tm
            if($this->internalFuzzy && $hasFuzzyConnector){
                $segment->setTargetEdit("translate5-unique-id[".$segment->getTaskGuid()."]");
                $connector->update($segment);
            }
            
            $matchResults=$matches->getResult();
            
            $matchRateInternal=null;
            //for each match, find the best match rate, and save it
            foreach ($matchResults as $match){
                if($matchRateInternal >= $match->matchrate){
                    continue;
                }
                $matchRateInternal=$match->matchrate;
                
                //store best match rate results
                if($matchRateInternal>$bestMatchRate){
                    $bestMatchRateResult=$match;
                    $bestMatchRateResult->internalTmmtid=$tmmtid;
                }
            }
            //no match rate is found in the tmmt result
            if($matchRateInternal==null){
                $saveAnalysis && $this->saveAnalysis($segment, null, $tmmtid);
                $matches->resetResult();
                continue;
            }
            
            if($matchRateInternal>$bestMatchRate){
                $bestMatchRate=$matchRateInternal;
            }
            
            //save the match analyses if needed
            $saveAnalysis && $this->saveAnalysis($segment, $matchRateInternal, $tmmtid);
            
            //reset the result collection
            $matches->resetResult();
        }
        
        return $bestMatchRateResult;
    }
    
    public function saveAnalysis($segment,$matchRate,$tmmtid){
        $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
        /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
        
        $matchAnalysis->setSegmentId($segment->getId());
        $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
        $matchAnalysis->setAnalysisId($this->analysisId);
        $matchAnalysis->setTmmtid($tmmtid);
        $matchAnalysis->setWordCount($this->wordCount->getSourceCount());
        $matchAnalysis->setMatchRate($matchRate);
        $matchAnalysis->save();
    }
    
    
    /***
     * Init the tmmt connectiors
     * 
     * @return array
     */
    public function initConnectors(){
        
        $tmmts=ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
        /* @var $tmmts editor_Plugins_MatchResource_Models_TmMt */
        
        $assocs=$tmmts->loadByAssociatedTaskGuid($this->task->getTaskGuid());
        
        if(empty($assocs)){
            return array();
        }
        
        foreach ($assocs as $assoc){
            $tmmt=ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
            /* @var $tmmt editor_Plugins_MatchResource_Models_TmMt  */
            
            $tmmt->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
            /* @var $manager editor_Plugins_MatchResource_Services_Manager */
            $resource=$manager->getResource($tmmt);
            
            //ignore non analysable resources
            if(!$resource->getAnalysable()){
                continue;
            }
            
            //store the resource type for the tmmt
            $this->resourceType[$tmmt->getId()]=$resource->getType();
            
            $connector=$manager->getConnector($tmmt);
            $this->connectors[$assoc['id']]=[];
            $this->connectors[$assoc['id']]=$connector;
            
            //if internal fuzzy is active, get the fuzzy connector
            if($this->internalFuzzy){
                //this function will clone the existing tmmt in opentm2 under oldname+Fuzzy-Analysis
                $fuzzyConnector=clone $connector->initFuzzyAnalysis();
                if(!empty($fuzzyConnector)){
                    $this->fuzzyConnectors[$assoc['id']]=$fuzzyConnector;
                }
            }
        }
        return $this->connectors;
    }
    
    /***
     * Init user assoc, so we are able to edit segments
     */
    public function initUserAssoc(){
        $this->userTaskAssoc=ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        
        $this->userTaskAssoc->setUserGuid($this->userGuid);
        $this->userTaskAssoc->setTaskGuid($this->task->getTaskGuid());
        $this->userTaskAssoc->setRole('');
        $this->userTaskAssoc->setState('');
        $this->userTaskAssoc->setIsPmOverride(true);
        $this->userTaskAssoc->setUsedInternalSessionUniqId(null);
        $this->userTaskAssoc->setUsedState(null);
        $this->userTaskAssoc->setState(editor_Workflow_Abstract::STATE_EDIT);
        $result=$this->userTaskAssoc->save();
        $this->userTaskAssoc->setId($result);
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
     * Remove the user assoc
     */
    public function removeUserAssoc(){
        if($this->userTaskAssoc->getId()){
            $this->userTaskAssoc->deletePmOverride();
        }
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
    
    protected function isBestSortedPretranslatable($tmmtid,$matchRate){
        //TODO: check if the match rate is in pretranslatable range
        //TODO: check if the tmmtid is best sorted
    }
    
    public function setPretranslate($pretranslate){
        $this->pretranslate=$pretranslate;
    }
    
    public function setInternalFuzzy($internalFuzzy) {
        $this->internalFuzzy=$internalFuzzy;
    }
}