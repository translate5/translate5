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
 * //TODO find better name
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

        $repetitions=array();
        //error_log(print_r($repetitions,1));

        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */
            
            if(isset($repetitionsDb[$segment->getId()]) && isset($repetitions[md5($segment->getFieldOriginal('source'))])){
                    
                    $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
                    /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
                    
                    $matchAnalysis->setSegmentId($segment->getId());
                    $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
                    $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
                    $matchAnalysis->setAnalysisId($this->analysisId);
                    $matchAnalysis->setTmmtid(0);
                    $matchAnalysis->setMatchRate(102);
                    $matchAnalysis->save();
                    continue;
            }
            
            $repetitions[md5($segment->getFieldOriginal('source'))]=array();
            
            $matchesByTm=[];
            //query the segment for each assigned tm
            foreach ($this->connectors as $tmmtid => $connector){
                /* @var $resource editor_Plugins_MatchResource_Services_Connector_Abstract */
                
                try {
                    $matches=$connector->query($segment);
                } catch (Exception $e) {
                    error_log(print_r($segment->getFieldOriginal('source'),1));
                    error_log(print_r($segment->getSegmentNrInTask(),1));
                    error_log(print_r($repetitions,1));
                    error_log(print_r($repetitionsDb,1));
                    continue;
                }
                
                $matchesByTm[$tmmtid]=$matches->getResult();
            }
            
            //calculate the match count based on the received results
            $calcMatch=$this->calculateMatch($matchesByTm,$segment);
            
            //save the match analysis 
            //$this->saveMatchAnalysis($segment,$calcMatch['count'],$calcMatch['tmmtid']);
        }
    }
    
    
    public function initConnectors(){
        
        $tmmts=ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
        /* @var $tmmts editor_Plugins_MatchResource_Models_TmMt */
        
        $assocs=$tmmts->loadByAssociatedTaskGuid($this->task->getTaskGuid());
        
        if(empty($assocs)){
            return null;
        }
        
        foreach ($assocs as $assoc){
            $tmmt=ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_TmMt');
            /* @var $tmmt editor_Plugins_MatchResource_Models_TmMt  */
            
            $tmmt->load($assoc['id']);
            
            $manager = ZfExtended_Factory::get('editor_Plugins_MatchResource_Services_Manager');
            /* @var $manager editor_Plugins_MatchResource_Services_Manager */
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
     * TODO: implement me,
     * TODO: return [count=> the count, tmmtid=> best mathc tm] 
     * @param array $matches
     * @return array
     */
    public function calculateMatch($matchesByTm,$segment){
        
        
        //for each tm-match save the best
        foreach ($matchesByTm as $tmmtid=>$tmMatch){
            
            $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
            /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
            
            $matchAnalysis->setSegmentId($segment->getId());
            $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
            $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
            $matchAnalysis->setAnalysisId($this->analysisId);
            $matchAnalysis->setTmmtid($tmmtid);
            
            foreach ($tmMatch as $match){
                
                if($matchAnalysis->getMatchRate() >= $match->matchrate){
                    continue;
                }
                $matchAnalysis->setMatchRate($match->matchrate);
            }
            //save match analysis
            $matchAnalysis->save();
        }
    }
    
    
    /***
     * Save the match analysis record with the calculated matchrate to the database
     * 
     * @param editor_Models_Segment $segment
     * @param int $matchRate
     * @param int $tmmtid
     */
    public function saveMatchAnalysis(editor_Models_Segment $segment,$matchRate,$tmmtid){
        $matchAnalysis=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_MatchAnalysis');
        /* @var $matchAnalysis editor_Plugins_MatchAnalysis_Models_MatchAnalysis */
        
        //save match analysis
        $matchAnalysis->setMatchRate($matchRate);
        $matchAnalysis->setSegmentId($segment->getId());
        $matchAnalysis->setSegmentNrInTask($segment->getSegmentNrInTask());
        $matchAnalysis->setTaskGuid($this->task->getTaskGuid());
        $matchAnalysis->setTmmtId($tmmtid);
        $matchAnalysis->save();
    }
}