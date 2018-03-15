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
class editor_Plugins_MatchAnalysis_Service{
    
    /***
     * @var string
     */
    protected $taskGuid;
    
    /***
     * Query the match resource service for each segment, calculate the best match rate, and save the match analysis model
     */
    public function calculateMatchrate(){
        // create a segment-iterator to get all segments of this task as a list of editor_Models_Segment objects
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$this->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */

        $assoc=ZfExtended_Factory::get('editor_Plugins_MatchResource_Models_Taskassoc');
        /* @var $assoc editor_Plugins_MatchResource_Models_Taskassoc */
        
        $filter = ZfExtended_Factory::get('ZfExtended_Models_Filter_ExtJs',array(
                $assoc,
                []
        ));
        $assoc->filterAndSort($filter);
        
        //add the checked filter, so only the assigned tms are returned
        $assoc->getFilter()->addFilter((object)[
                'field' => 'checked',
                'table' => 'LEK_matchresource_tmmt',
                'comparison' => 'eq',
                'type' =>  'boolean',
                'value' => true,
        ]);
        
        $assocResult=$assoc->getAssocTasksWithResources($this->getTaskGuid());
        
        
        foreach($segments as $segment) {
            /* @var $segment editor_Models_Segment */
            
            $matches=[];
            //query the segment for each assigned tm
            foreach ($assocResult as $singleAssoc){
                
                //query the tm with the segment
                $result=$this->querySegment($segment,(integer)$singleAssoc['id']);
                
                $matches[$singleAssoc['id']]=$result->getResult();
            }
            
            //calculate the match count based on the received results
            $calcMatch=$this->calculateMatch($matches);
            
            //save the match analysis 
            $this->saveMatchAnalysis($segment,$calcMatch['count'],$calcMatch['tmmtid']);
            
        }
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
        $tmmt->checkTaskAndTmmtAccess($tmmtid, $segment);
        
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
    public function calculateMatch($matches){
        
        return $matches;
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
        $matchAnalysis->setTaskGuid($this->getTaskGuid());
        $matchAnalysis->setTmmtId($tmmtid);
        $matchAnalysis->save();
    }
    
    public function setTaskGuid($taskGuid){
        $this->taskGuid=$taskGuid;
    }
    
    public function getTaskGuid(){
        return $this->taskGuid;
    }
    
}