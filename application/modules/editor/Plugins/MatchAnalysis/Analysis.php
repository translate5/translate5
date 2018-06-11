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

    public function __construct(editor_Models_Task $task,$analysisId){
        $this->task=$task;
        $this->analysisId=$analysisId;
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
                    
                    $repetitionMatchRate=$repetitionByHash[md5($segment->getFieldOriginal('source'))];

                    //check the match rate for the initial segment (repetition) 
                    if($repetitionMatchRate>=100 && $repetitionMatchRate<editor_Plugins_MatchResource_Services_OpenTM2_Connector::CONTEXT_MATCH_VALUE){
                        $matchAnalysis->setMatchRate(editor_Plugins_MatchResource_Services_OpenTM2_Connector::REPETITION_MATCH_VALUE);
                    }else if($repetitionMatchRate==editor_Plugins_MatchResource_Services_OpenTM2_Connector::CONTEXT_MATCH_VALUE){
                        //TODO: if it is a context match, pretranslate it it is not counted as repetition
                        continue;
                    }else{
                        $matchAnalysis->setMatchRate($repetitionMatchRate);
                    }
                    
                    $matchAnalysis->setWordCount($wordCount->getSourceCount());
                    
                    $matchAnalysis->save();
                    continue;
            }
            
            $matchesByTm=[];
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
                
                //for each match, fint the best match rate, and save it
                foreach ($matches->getResult() as $match){
                    if($matchAnalysis->getMatchRate() >= $match->matchrate){
                        continue;
                    }
                    $matchAnalysis->setMatchRate($match->matchrate);
                }
                
                if($matchAnalysis->getMatchRate()==null){
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
            $repetitionByHash[md5($segment->getFieldOriginal('source'))]=$bestMatchRate;
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
}