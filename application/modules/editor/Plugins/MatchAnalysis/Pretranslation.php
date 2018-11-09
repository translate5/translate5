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
 */
class editor_Plugins_MatchAnalysis_Pretranslation{
    use editor_Models_Import_FileParser_TagTrait;
    
    /***
     * 
     * @var editor_Models_Task
     */
    protected $task;
    
    /***
     * 
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /***
     * 
     * @var editor_Models_TaskUserAssoc
     */
    protected $userTaskAssoc;
    
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
    
    /***
     * Collection of assigned languageResource resources types where key is languageResourceid and resource type is the value
     *
     * @var array
     */
    protected $resources=array();
    
    
    /***
     * Minimum matchrate so the segment is pretransalted
     * @var integer
     */
    protected $pretranslateMatchrate=100;
    

    /***
     * Pretranslate with translation memory and term collection priority
     * @var boolean
     */
    protected $usePretranslateTMAndTerm=false;
    
    
    /***
     * Pretranslate with mt priority only when the tm pretranslation matchrate is not over the $pretranslateMatchrate
     * @var boolean
     */
    protected $usePretranslateMT=false;
    
    
    /***
     * Pretranslation mt connectors(the mt resources associated to a task)
     * @var array
     */
    protected $mtConnectors=array();
    
    
    /***
     * Use the given TM analyse (or MT if analyse was empty) result to update the segment
     * Update the segment only if it is not TRANSLATED  
     * 
     * @param editor_Models_Segment $segment
     * @param stdClass $result - match resources result
     */
    protected function updateSegment(editor_Models_Segment $segment, $result){
        
        //if the segment target is not empty or best match rate is not found do not pretranslate
        //pretranslation only for editable segments
        if(($segment->getAutoStateId()!=editor_Models_Segment_AutoStates::NOT_TRANSLATED)){
            return;
        }
        //if($result->matchrate==editor_Services_Connector_FilebasedAbstract::REPETITION_MATCH_VALUE){
            //return;
        //}
        //the internalLanguageResourceid is set when the segment bestmatchrate is found(see analysis getbestmatchrate function)
        $languageResourceid=$result->internalLanguageResourceid;
        
        $history = $segment->getNewHistoryEntity();
        
        $segmentField=$this->sfm->getFirstTargetName();
        $segmentFieldEdit=$segmentField.'Edit';
        
        $targetResult=$result->target;
        
        //ignore internal fuzzy match target
        //FIXME hier Wechselwirkung mit Vorübersetzung von Wiederholungen, diese wurden nicht vorübersetzt!
        if (strpos($targetResult, 'translate5-unique-id['.$segment->getTaskGuid().']') !== false){
            return;
        }
        
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
        
        $segment->setUserGuid($this->userGuid);//to the authenticated userGuid
        $segment->setUserName($this->userName);//to the authenticated userName
        
        $matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        /* @var $matchrateType editor_Models_Segment_MatchRateType */
        
        //set the type
        $langRes = $this->resources[$languageResourceid];
        /* @var $langRes editor_Models_LanguageResources_LanguageResource */
        
        //just to display the TM name too, we add it here to the type 
        $type = $langRes->getServiceName().' - '.$langRes->getName();
        $matchrateType->initPretranslated($langRes->getResourceType(), $type);
        
        $segment->setMatchRateType((string) $matchrateType);
        
        $segment->setMatchRate($result->matchrate);
        
        //if the task is in state import calculate the autostate
        if($this->task->getState()==editor_Models_Task::STATE_IMPORT){
            $autoStates=ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
            /* @var $autoStates editor_Models_Segment_AutoStates */
            
            $segment->setAutoStateId($autoStates->calculateImportState($segment->isEditable(), true));
            
        }else{
            $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
            /* @var $wfm editor_Workflow_Manager */
            $activeWorkflow=$wfm->getActive($this->task->getTaskGuid());
            
            $updateAutoStates = function($autostates, $segment, $tua) {
                //sets the calculated autoStateId
                $segment->setAutoStateId($autostates->calculateSegmentState($segment, $tua));
            };
            
            //init the task user association
            $this->initUsertTaskAssoc();
            
            if($this->userTaskAssoc->getIsPmOverride() == 1){
                
                $segment->setWorkflowStep(editor_Workflow_Abstract::STEP_PM_CHECK);
            }
            else {
                //sets the actual workflow step
                $segment->setWorkflowStepNr($this->task->getWorkflowStep());
                
                //sets the actual workflow step name, does currently depend only on the userTaskRole!
                $step = $activeWorkflow->getStepOfRole($this->userTaskAssoc->getRole());
                $step && $segment->setWorkflowStep($step);
            }
            
            $autostates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
            
            //set the autostate as defined in the given Closure
            /* @var $autostates editor_Models_Segment_AutoStates */
            $updateAutoStates($autostates, $segment, $this->userTaskAssoc);
        }
        
        
        //NOTE: remove me if to many problems
        //$segment->validate();
        
        if($this->task->getWorkflowStep()==1){
            $hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash', [$this->task]);
            /* @var $hasher editor_Models_Segment_RepetitionHash */
            //calculate and set segment hash
            $segmentHash=$hasher->hashTarget($targetResult, $segment->getSource());
            $segment->setTargetMd5($segmentHash);
        }
        
        //lock the pretranslations if 100 matches in the task are not editable
        if(!$this->task->getEdit100PercentMatch()){
            $segment->setEditable(false);
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
    
    
    /***
     * Init the task user assocition if exist. If not a default record will be initialized
     * @return editor_Models_TaskUserAssoc
     */
    public function initUsertTaskAssoc(){
        if($this->userTaskAssoc){
            return $this->userTaskAssoc;
        }
        
        $this->userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        try {
            $this->userTaskAssoc->loadByParams($this->userGuid,$this->task->getTaskGuid());
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->userTaskAssoc->setUserGuid($this->userGuid);
            $this->userTaskAssoc->setTaskGuid($this->task->getTaskGuid());
            $this->userTaskAssoc->setRole('');
            $this->userTaskAssoc->setState('');
            $this->userTaskAssoc->setIsPmOverride(true);
            $this->userTaskAssoc->setUsedInternalSessionUniqId(null);
            $this->userTaskAssoc->setUsedState(null);
            $this->userTaskAssoc->setState(editor_Workflow_Abstract::STATE_EDIT);
        }
    }
    
    /***
     * Query the segment using the Mt engines assigned to the task.
     * Ony the first mt engine will be used
     * @param editor_Models_Segment $segment
     * @return NULL|[stdClass]
     */
    protected function getMtResult(editor_Models_Segment $segment){
        if(empty($this->mtConnectors)){
            return null;
        }
        //INFO: use the first connector, since no mt engine priority exist
        $connector = $this->mtConnectors[0];
        /* @var $connector editor_Services_Connector_Abstract */
        $connector->resetResultList();
        $matches = $connector->query($segment);
        $matchResults=$matches->getResult();
        if(!empty($matchResults)){
            $result=$matchResults[0];
            $result->internalLanguageResourceid=$connector->getLanguageResource()->getId();
            return $result;
        }
        return null;
    }
    
    public function setUserGuid($userGuid) {
        $this->userGuid=$userGuid;
    }
    
    public function setUserName($userName) {
        $this->userName=$userName;
    }
    
    public function setPretranslateMatchrate($pretranslateMatchrate) {
        $this->pretranslateMatchrate=$pretranslateMatchrate;
    }
    
    /***
     * Set pretranslate from Mt priority flag
     * @param boolean $usePretranslateMT
     */
    public function setPretranslateMt($usePretranslateMT) {
        $this->usePretranslateMT=$usePretranslateMT;
    }
    
    /***
     * Set the pretranslate from the Tm and termcollection priority flag. This flag also will run the pretranslations
     * @param boolean $usePretranslateTMAndTerm
     */
    public function setPretranslateTmAndTerm($usePretranslateTMAndTerm) {
        $this->usePretranslateTMAndTerm=$usePretranslateTMAndTerm;
    }
}