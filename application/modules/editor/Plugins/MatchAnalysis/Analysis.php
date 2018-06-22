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
    use editor_Models_Import_FileParser_TagTrait;
    
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
     * Session data
     * @var array
     */
    protected $sessionData;

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
                    
                    //if it is a context match, pretranslate it it is not counted as repetition
                    if($repetitionMatchRate==editor_Plugins_MatchResource_Services_OpenTM2_Connector::CONTEXT_MATCH_VALUE){
                        $this->pretranslateSegment($segment,$repetitionResult,$repetitionResult->internalTmmtid);
                        continue;
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
                $this->pretranslateSegment($segment, $bestMatchRateResult,$tmmtid);
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
     * @param stdClass $result - match resources result
     * @param int $tmmtid
     */
    protected function pretranslateSegment(editor_Models_Segment $segment, $result,$tmmtid){
        //if the segment target is not empty or best match rate is not found do not pretranslate
        //pretranslation only for editable segments, check if the segment interattor already does that
        if(($segment->getAutoStateId()!=editor_Models_Segment_AutoStates::NOT_TRANSLATED) || !isset($result)){
            return;
        }
        if($result->matchrate>=100 && $result->matchrate!=editor_Plugins_MatchResource_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE){
            
            $history = $segment->getNewHistoryEntity();
            
            $segmentField=$this->sfm->getFirstTargetName();
            $segmentFieldEdit=$segmentField.'Edit';
            
            $targetResult=$result->target;
            
            $internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
            /* @var $internalTag editor_Models_Segment_InternalTag */
            
            //since our internal tags are a div span construct with plain content in between, we have to replace them first
            $targetResult = $internalTag->protect($targetResult);
            
            //this method splits the content at tag boundaries, and sanitizes the textNodes only
            $targetResult = $this->parseSegmentProtectWhitespace($targetResult);
            
            //revoke the internaltag replacement
            $targetResult = $internalTag->unprotect($targetResult);
            
            $segment->set($segmentField,$targetResult); //use sfm->getFirstTargetName here
            $segment->set($segmentFieldEdit,$targetResult); //use sfm->getFirstTargetName here
            
            $segment->updateToSort($segmentField);
            $segment->updateToSort($segmentFieldEdit);
            
            $userSession=$this->sessionData['user'];
            $segment->setUserGuid($userSession->userGuid);//to the authenticated userGuid
            $segment->setUserName($userSession->userName);//to the authenticated userName
            
            $matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
            /* @var $matchrateType editor_Models_Segment_MatchRateType */
            //set the type
            $matchrateType->initEdited($this->resourceType[$tmmtid]);
            
            $segment->setMatchRateType((string) $matchrateType);


            if($this->task->getState()==editor_Models_Task::STATE_IMPORT){
                $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
                /* @var $wfm editor_Workflow_Manager */
                $activeWorkflow=$wfm->getActive($this->task->getTaskGuid());
                $activeWorkflow->setSession($this->sessionData);
                $activeWorkflow->beforeSegmentSave($segment);
            }else{
                
                $autoStates=ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
                /* @var $autoStates editor_Models_Segment_AutoStates */
                
                //TODO: is the segment translated here ?
                $segment->setAutoStateId($autoStates->calculateImportState($segment->isEditable(), true));
            }
            
            
            //NOTE: remove me if to many problems
            $segment->validate();
            
            if($this->task->getWorkflowStep()==1){
                $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$this->task]);
                /* @var $hasher editor_Models_Segment_RepetitionHash */
                //calculate and set segment hash
                $segmentHash=$hasher->hashTarget($targetResult, $segment->getSource());
                $segment->setTargetMd5($segmentHash);
            }
            
            $duration=new stdClass();
            $duration->$segmentField=0;
            $segment->setTimeTrackData($duration);
            
            $duration=new stdClass();
            $duration->$segmentFieldEdit=0;
            $segment->setTimeTrackData($duration);
            
            $history->save();
            $segment->setTimestamp(null);
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
    
    public function setSessionData($sessionData){
        $this->sessionData=$sessionData;
    }
}