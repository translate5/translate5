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
 * MatchAnalysis Entity Object
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * 
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * 
 * @method integer getSegmentId() getSegmentId()
 * @method void setSegmentId() setSegmentId(integer $segmentId)
 * 
 * @method integer getSegmentNrInTask() getSegmentNrInTask()
 * @method void setSegmentNrInTask() setSegmentNrInTask(integer $segmentNrInTask)
 * 
 * @method integer getTmmtId() getTmmtId()
 * @method void setTmmtId() setTmmtId(integer $tmmtid)
 * 
 * @method integer getMatchRate() getMatchRate()
 * @method void setMatchRate() setMatchRate(integer $matchrate)
 * 
 * @method integer getWordCount() getWordCount()
 * @method void setWordCount() setWordCount(integer $wordCount)
 * 
 * @method integer getAnalysisId() getAnalysisId()
 * @method void setAnalysisId() setAnalysisId(integer $analysisId)
 * 
 */
class editor_Plugins_MatchAnalysis_Models_MatchAnalysis extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_MatchAnalysis';
    protected $validatorInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Validator_MatchAnalysis';
    
    
    /***
     * Load the result by best match rate. The results will be grouped in the followed groups:
     * Real groups:        103%, 102%, 101%, 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
     * Group result index: 103,  102,  101,  100,  99,      89,      79,      69,      59,      noMatch
     * 
     * The result array will contain the total word count in the result.
     * @param guid $taskGuid
     * @param boolean $isExport: is the data requested for export
     * @return NULL|array
     */
    public function loadByBestMatchRate($taskGuid,$isExport=false){
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from($this->db, array('LEK_match_analysis_taskassoc.created','analysisId','segmentId','taskGuid','matchRate','wordCount'))
        ->join('LEK_match_analysis_taskassoc', 'LEK_match_analysis_taskassoc.id=LEK_match_analysis.analysisId')
        ->where('LEK_match_analysis.taskGuid = ?', $taskGuid);
        
        $resultArray=$this->db->fetchAll($s)->toArray();
        
        if(empty($resultArray)){
            return null;
        }
        return $this->groupByMatchrate($resultArray,$isExport);
    }
    
    /***
     * Group the results by match rate group
     * @param array $results
     * @return array[]
     */
    private function groupByMatchrate($results,$isExport=false){
        
        $groupedResults=[];
        
        //103%, 102%, 101%. 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
        $groupBorder=[102=>'103',101=>'102',100=>'101',99=>'100',89=>'99',79=>'89',69=>'79',59=>'69',50=>'59'];
        $wordCountTotal=0;
        $createdDate=null;
        
        foreach ($results as $res){
        
            if(!$createdDate){
                $createdDate=$res['created'];
            }
            
            $resultFound=false;
            
            //check on which border group this result belongs to
            foreach ($groupBorder as $border=>$value){
                if($res['matchRate']>$border){
                    if(!isset($groupedResults[$value])){
                        if(!$isExport){
                            $groupedResults[$value]=[];
                            $groupedResults[$value]['rateCount']=0;
                            $groupedResults[$value]['wordCount']=0;
                        }else{
                            $groupedResults[$value]=0;
                        }
                    }
                    if(!$isExport){
                        $groupedResults[$value]['rateCount']++;
                        $groupedResults[$value]['wordCount']+=$res['wordCount'];
                    }else{
                        $groupedResults[$value]++;
                    }
                    
                    $wordCountTotal+=$res['wordCount'];
                    $resultFound=true;
                    break;
                }
            }
            
            if(!$resultFound){
                if(!isset($groupedResults['noMatch'])){
                    if(!$isExport){
                        $groupedResults['noMatch']=[];
                        $groupedResults['noMatch']['rateCount']=0;
                        $groupedResults['noMatch']['wordCount']=0;
                    }else{
                        $groupedResults['noMatch']=0;
                    }
                }
                
                if(!$isExport){
                    $groupedResults['noMatch']['rateCount']++;
                    $groupedResults['noMatch']['wordCount']+=$res['wordCount'];
                }else{
                    $groupedResults['noMatch']++;
                }
                
                $wordCountTotal+=$res['wordCount'];
            }
        }
        $groupedResults['wordCountTotal']=$wordCountTotal;
        $groupedResults['created']=$createdDate;
        
        return $groupedResults;
    }
}