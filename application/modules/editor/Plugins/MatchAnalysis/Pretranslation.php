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
    use ZfExtended_Logger_DebugTrait;
    
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
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * @var editor_Models_Segment_AutoStates
     */
    protected $autoStates;
    
    /***
     * Pretranslation mt connectors(the mt resources associated to a task)
     * @var array
     */
    protected $mtConnectors=array();
    
    /**
     * contains the real state of the task. $this->task->state will contain matchanalysis
     * @var string
     */
    protected $taskState;
    
    
    /**
     * @var ZfExtended_EventManager
     */
    protected $events = false;
    
    /***
     * Analysis id
     *
     * @var mixed
     */
    protected $analysisId;
    
    /***
     * Is the current analysis and pretranslation running with batch query enabled
     * @var boolean
     */
    protected $batchQuery = false;
    
    public function __construct(int $analysisId){
        $this->initLogger('E1100', 'plugin.matchanalysis', '', 'Plug-In MatchAnalysis: ');
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->autoStates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        $this->events = ZfExtended_Factory::get('ZfExtended_EventManager', array('editor_Plugins_MatchAnalysis_Pretranslation'));
        $this->analysisId=$analysisId;
    }
    
    /**
     * Use this for internal fuzzy match target that will be ignored.
     */
    public static function renderDummyTargetText($taskGuid) {
        return "translate5-unique-id[".$taskGuid."]";
    }
    
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
        if($segment->getAutoStateId() != editor_Models_Segment_AutoStates::NOT_TRANSLATED){
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
        
        $matchrateType = ZfExtended_Factory::get('editor_Models_Segment_MatchRateType');
        /* @var $matchrateType editor_Models_Segment_MatchRateType */
        
        //set the type
        $languageResource = $this->resources[$languageResourceid];
        /* @var $langRes editor_Models_LanguageResources_LanguageResource */
        
        //just to display the TM name too, we add it here to the type
        $type = $languageResource->getServiceName().' - '.$languageResource->getName();
        
        //ignore internal fuzzy match target
        $dummyTargetText = self::renderDummyTargetText($segment->getTaskGuid());
        if (strpos($targetResult, $dummyTargetText) !== false){
            //set the internal fuzzy available matchrate type
            $matchrateType->initPretranslated(editor_Models_Segment_MatchRateType::TYPE_INTERNAL_FUZZY_AVAILABLE,$type);
            $segment->setMatchRateType((string) $matchrateType);
            
            //save the segment and history
            $this->saveSegmentAndHistory($segment,$history);
            return;
        }
        
        $hasText = $this->internalTag->hasText($segment->getSource());
        if($hasText) {
            //if the result language resource is termcollection, set the target result first character to uppercase
            if($this->isTermCollection($languageResourceid)){
                $targetResult=ZfExtended_Utils::mb_ucfirst($targetResult);
            }
            $targetResult = $this->internalTag->removeIgnoredTags($targetResult);
            $segment->setMatchRate($result->matchrate);
            $matchrateType->initPretranslated($languageResource->getResourceType(), $type);
        }
        //if the source contains no text but tags only, we set the target to the source directly
        else {
            $targetResult = $segment->getSource();
            $segment->setMatchRate(editor_Services_Connector_FilebasedAbstract::CONTEXT_MATCH_VALUE);
            $matchrateType->initPretranslated($matchrateType::TYPE_SOURCE);
            $segment->setEditable(false);
        }
        $segment->setMatchRateType((string) $matchrateType);
        
        
        $segment->set($segmentField,$targetResult); //use sfm->getFirstTargetName here
        $segment->set($segmentFieldEdit,$targetResult); //use sfm->getFirstTargetName here
        
        $segment->updateToSort($segmentField);
        $segment->updateToSort($segmentFieldEdit);
        
        $segment->setUserGuid($this->userGuid);//to the authenticated userGuid
        $segment->setUserName($this->userName);//to the authenticated userName

        $this->calculateSegmentAutoState($segment, $hasText);
        
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
        if($result->matchrate >= 100 && !$this->task->getEdit100PercentMatch()){
            $segment->setEditable(false);
        }

        //save the segment and history
        $this->saveSegmentAndHistory($segment,$history);
        
        $this->events->trigger('afterAnalysisSegmentPretranslate', $this, [
            'entity' => $segment,
            'analysisId' => $this->analysisId,
            'languageResourceId' => $languageResourceid,
            'result' => $result
        ]);
    }
    
    /**
     * calculates the autostate in the given segment
     * @param editor_Models_Segment $segment
     * @param bool $hasText
     */
    protected function calculateSegmentAutoState(editor_Models_Segment $segment, bool $hasText): void {
        if(!$hasText) {
            $segment->setAutoStateId($this->autoStates->calculateImportState(false, true));
            return;
        }
        
        //if the task is in state import calculate the autostate
        if($this->taskState == editor_Models_Task::STATE_IMPORT){
            $segment->setAutoStateId($this->autoStates->calculateImportState($segment->isEditable(), true));
            return;
        }
        
        //if a user pretranslates an already imported task, we set the autostate and workflow step to values fitting to the user
        $wfm = ZfExtended_Factory::get('editor_Workflow_Manager');
        /* @var $wfm editor_Workflow_Manager */
        $activeWorkflow=$wfm->getActive($this->task->getTaskGuid());
        
        $updateAutoStates = function(editor_Models_Segment_AutoStates $autostates, $segment, $tua) {
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
        
        //set the autostate as defined in the given Closure
        $updateAutoStates($this->autoStates, $segment, $this->userTaskAssoc);
    }
    
    /***
     * Init the task user assocition if exist. If not a default record will be initialized
     * @return editor_Models_TaskUserAssoc
     */
    protected function initUsertTaskAssoc(){
        if($this->userTaskAssoc){
            return $this->userTaskAssoc;
        }
        
        try {
            $this->userTaskAssoc = editor_Models_Loaders_Taskuserassoc::loadByTaskForceWorkflowRole($this->userGuid, $this->task);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->userTaskAssoc = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
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
        /* @var $connector editor_Services_Connector */
        
        //if the current connector supports batch query, enable the batch query for this connector
        if($connector->isBatchQuery() && $this->batchQuery){
            $connector->enableBatch();
        }
        
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
    
    /***
     * Check if the given language resource id is a valid termcollection resource
     * @param int $languageResourceId
     * @return boolean
     */
    protected function isTermCollection($languageResourceId){
        if(!isset($this->resources[$languageResourceId])){
            return false;
        }
        $lr=$this->resources[$languageResourceId];
        /* @var $lr editor_Models_LanguageResources_LanguageResource */
        $tcs=ZfExtended_Factory::get('editor_Services_TermCollection_Service');
        /* @var $tcs editor_Services_TermCollection_Service */
        return $lr->getServiceName()==$tcs->getName();
    }
    
    /***
     * Save the segment(set the duration and the timestamp) and the segmenthistory
     * @param editor_Models_Segment $segment
     * @param editor_Models_SegmentHistory $history
     */
    protected function saveSegmentAndHistory(editor_Models_Segment $segment,editor_Models_SegmentHistory $history){
        $segmentField=$this->sfm->getFirstTargetName();
        $segmentFieldEdit=$segmentField.'Edit';
        $duration=new stdClass();
        $duration->$segmentField=0;
        $segment->setTimeTrackData($duration);
        
        $duration=new stdClass();
        $duration->$segmentFieldEdit=0;
        $segment->setTimeTrackData($duration);
        
        $history->save();
        $segment->setTimestamp(NOW_ISO);
        $segment->save();
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
     * @param bool $usePretranslateMT
     */
    public function setPretranslateMt($usePretranslateMT) {
        $this->usePretranslateMT=$usePretranslateMT;
    }
    
    /***
     * Set the pretranslate from the Tm and termcollection priority flag. This flag also will run the pretranslations
     * @param bool $usePretranslateTMAndTerm
     */
    public function setPretranslateTmAndTerm($usePretranslateTMAndTerm) {
        $this->usePretranslateTMAndTerm=$usePretranslateTMAndTerm;
    }
}