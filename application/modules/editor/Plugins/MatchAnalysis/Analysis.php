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
 * //TODO Add class desc
 *
 */
class editor_Plugins_MatchAnalysis_Analysis{
    
    /***
     * @var editor_Models_Task
     */
    protected $task;
    
    
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
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;

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
            return;
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
        //error_log(print_r($repetitions,1));
        
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        
        $langModel->load($this->task->getSourceLang());
        
        $wordCount=ZfExtended_Factory::get('editor_Models_Segment_WordCount',[
                $langModel->getRfc5646()
        ]);
        
        /* @var $wordCount editor_Models_Segment_WordCount */
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */

            $wordCount->setSegment($segment);
            
            //check if the segment source hash exist in the repetition array
            //segment exist in the repetition array -> it is repetition, save it as 102 (repetition) and 0 tmmt
            //segment does not exist in repetition array -> query the tm save the best match rate per tm
            if(isset($repetitionsDb[$segment->getId()]) && isset($repetitionByHash[md5($segment->getFieldOriginal('source'))])){
                    
                    $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
                    /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
                    
                    $matchAnalysis->setSegmentId($segment->getId());
                    $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
                    $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
                    $matchAnalysis->setAnalysisId($this->analysisId);
                    
                    $matchAnalysis->setTmmtid(0);
                    
                    $repetitionResult=$repetitionByHash[md5($segment->getFieldOriginal('source'))];
                    $repetitionMatchRate=$repetitionResult->matchrate;
                    
                    //check the match rate for the initial segment (repetition) 
                    if($repetitionMatchRate>=100 && $repetitionMatchRate<editor_Plugins_MatchResource_Services_OpenTM2_Connector::CONTEXT_MATCH_VALUE){
                        $matchAnalysis->setMatchRate(editor_Plugins_MatchResource_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE);
                    }else if($repetitionMatchRate==editor_Plugins_MatchResource_Services_OpenTM2_Connector::CONTEXT_MATCH_VALUE){
                        //if it is a context match, pretranslate it it is not counted as repetition
                        $this->pretranslateSegment($segment, $repetitionResult);
                        continue;
                    }else{
                        $matchAnalysis->setTmmtid($repetitionResult->internalTmmtid);
                        $matchAnalysis->setMatchRate($repetitionMatchRate);
                    }
                    
                    $matchAnalysis->setWordCount($wordCount->getSourceCount());
                    
                    $matchAnalysis->save();
                    continue;
            }
            
            $bestMatchRateResult=null;
            $bestMatchRate=null;
            
            //query the segment for each assigned tm
            foreach ($this->connectors as $tmmtid => $connector){
                /* @var $connector editor_Plugins_MatchResource_Services_Connector_Abstract */
                
                $matches=[];
                $connector->resetResultList();
                $matches=$connector->query($segment);
                
                $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
                /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
                
                $matchAnalysis->setSegmentId($segment->getId());
                $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
                $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
                $matchAnalysis->setAnalysisId($this->analysisId);
                
                $matchResults=$matches->getResult();
                
                //for each match, fint the best match rate, and save it
                foreach ($matchResults as $match){
                    
                    //TODO: pretranslation with best sort rate
                    
                    if($matchAnalysis->getMatchRate() >= $match->matchrate){
                        continue;
                    }
                    $matchAnalysis->setMatchRate($match->matchrate);
                    
                    //store best match rate results
                    if($matchAnalysis->getMatchRate()>$bestMatchRate){
                        $bestMatchRateResult=$match;
                        $bestMatchRateResult->internalTmmtid=$tmmtid;
                    }
                }
                
                if($matchAnalysis->getMatchRate()==null){
                    $matchAnalysis->setTmmtid($tmmtid);
                    $matchAnalysis->setWordCount($wordCount->getSourceCount());
                    //save match analysis
                    $matchAnalysis->save();
                    
                    $matches->resetResult();
                    continue;
                }
                
                if($matchAnalysis->getMatchRate()>$bestMatchRate){
                    $bestMatchRate=$matchAnalysis->getMatchRate();
                }
                
                $matchAnalysis->setTmmtid($tmmtid);
                $matchAnalysis->setWordCount($wordCount->getSourceCount());
                //save match analysis
                $matchAnalysis->save();
                
                $matches->resetResult();
            }
            
            //add the segment source hash in the array
            $repetitionByHash[md5($segment->getFieldOriginal('source'))]=$bestMatchRateResult;
            
            if($this->pretranslate){
                $this->pretranslateSegment($segment, $bestMatchRateResult);
            }
        }
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
            
            $connector=$manager->getConnector($tmmt);
            
            $this->connectors[$assoc['id']]=[];
            $this->connectors[$assoc['id']]=$connector;
        }
        
        return $this->connectors;
    }
    
    /***
     * Query the tm for the given segment 
     * 
     * @param editor_Models_Segment $segment
     * @param integer $tmmtid
     * @return editor_Plugins_MatchResource_Services_ServiceResult
     */
    public function querySegment(editor_Models_Segment $segment,integer $tmmtid){
        $tmmt=ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
        /* @var $tmmt editor_Plugins_MatchResource_Models_TmMt  */
        
        //check taskGuid of segment against loaded taskguid for security reasons
        //checks if the current task is associated to the tmmt
        $tmmt->checkTaskAndTmmtAccess($this->task->getTaskGuid(),$tmmtid, $segment);
        
        $tmmt->load($tmmtid);
        
        $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
        /* @var $manager editor_Plugins_MatchResource_Services_Manager */
        $connector=$manager->getConnector($tmmt);
        
        return $connector->query($segment);
    }
    
    /***
     * Pretranslate the given segment from the given resource
     * @param editor_Models_Segment $segment
     * @param stdClass $result
     */
    protected function pretranslateSegment(editor_Models_Segment $segment, $result){
        //if the segment target is not empty or best match rate is not found do not pretranslate
        //TODO: in the final version check for the autoState and not on empty!
        //pretranslation only for editable segments, check if the segment interattor already does that
        if(!empty($segment->getFieldOriginal('target')) || !isset($result)){
            return;
        }
        if($result->matchrate>=100 && $result->matchrate!=editor_Plugins_MatchResource_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE){
            $segment->set($this->sfm->getFirstTargetName(),$result->target); //use sfm->getFirstTargetName here
            $segment->set($this->sfm->getFirstTargetName().'Edit',$result->target); //use sfm->getFirstTargetName here
            
            //$matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
            /* @var $matchrateType editor_Models_Segment_MatchRateType */
            //set the type
            //$matchrateType->initEdited($tmmt->getResource()->getType());
            
            //$segment->setMatchRateType((string) $matchrateType);
            
            $segment->save();
        }
        //TODO: change this when the best sort rate is implemented
        //100, 101, 103 -> pretranslate the segment
    }
    
    protected function isBestSortedPretranslatable($tmmtid,$matchRate){
        //TODO: check if the match rate is in pretranslatable range
        //TODO: check if the tmmtid is best sorted
    }
    
    public function setPretranslate($pretranslate){
        $this->pretranslate=$pretranslate;
    }
}