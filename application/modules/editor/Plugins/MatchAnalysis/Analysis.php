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
     * Collection of assigned tmmt resources types where key is tmmtid and resource type is the value
     * 
     * @var array
     */
    protected $resourceType=array();
    
    
    /***
     * Flag if pretranslations is active
     * @var string
     */
    protected $pretranslate=false;
    
    /***
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /***
     * 
     * @var string
     */
    protected $userGuid;
    
    
    /***
     * 
     * @var string
     */
    protected $userName;

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
        //error_log(print_r($repetitions,1));
        
        $langModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $langModel editor_Models_Languages */
        
        $langModel->load($this->task->getSourceLang());
        
        $wordCount=ZfExtended_Factory::get('editor_Models_Segment_WordCount',[
                $langModel->getRfc5646()
        ]);
        
        //init pretranslatio class
        $pretranslation=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Pretranslation',[
            $this->task
        ]);
        /* @var $pretranslation editor_Plugins_MatchAnalysis_Pretranslation */
        
        $pretranslation->setResourceType($this->resourceType);
        $pretranslation->setUserGuid($this->userGuid);
        $pretranslation->setUserName($this->userName);
        
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
                    $matchAnalysis->setMatchRate(editor_Plugins_MatchResource_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE);
                    $matchAnalysis->setWordCount($wordCount->getSourceCount());
                    
                    $repetitionResult=$repetitionByHash[md5($segment->getFieldOriginal('source'))];
                    $repetitionMatchRate=$repetitionResult->matchrate;
                    
                    //A repetition, that also is found as a 103% match in the TM is NOT counted as a repetition. And it is pre-translated.
                    if($repetitionMatchRate==editor_Plugins_MatchResource_Services_OpenTM2_Connector::CONTEXT_MATCH_VALUE){
                        $pretranslation->pretranslateSegment($segment,$repetitionResult,$repetitionResult->internalTmmtid);
                        continue;
                    }
                    
                    if($repetitionMatchRate>=100){
                        $pretranslation->pretranslateSegment($segment,$repetitionResult,$repetitionResult->internalTmmtid);
                    }
                    
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
                
                //for each match, find the best match rate, and save it
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
                $pretranslation->pretranslateSegment($segment, $bestMatchRateResult,$tmmtid);
            }
        }
        return true;
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
        }
        
        return $this->connectors;
    }
    
    protected function isBestSortedPretranslatable($tmmtid,$matchRate){
        //TODO: check if the match rate is in pretranslatable range
        //TODO: check if the tmmtid is best sorted
    }
    
    public function setPretranslate($pretranslate){
        $this->pretranslate=$pretranslate;
    }
    
    public function setUserGuid($userGuid){
        $this->userGuid=$userGuid;
    }
    
    public function setUserName($userName){
        $this->userName=$userName;
    }
}