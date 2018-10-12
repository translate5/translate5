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
 * @method integer getInternalFuzzy() getInternalFuzzy()
 * @method void setInternalFuzzy() setInternalFuzzy(integer $internalFuzzy)
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
     * @param string $taskGuid
     * @param boolean $isExport: is the data requested for export
     * @return array
     */
    public function loadByBestMatchRate($taskGuid,$isExport=false){
        
        $sql='SELECT m.*
              FROM LEK_match_analysis m 
                  LEFT JOIN LEK_match_analysis b
                      ON m.tmmtid = b.tmmtid and m.segmentId = b.segmentId 
                      AND b.matchRate > m.matchRate 
              WHERE b.matchRate IS NULL 
              AND m.taskGuid=? 
              AND m.analysisId = (
				  SELECT MAX(id)
				  FROM LEK_match_analysis_taskassoc
				  WHERE taskGuid = ?
			   )';
        
        $resultArray=$this->db->getAdapter()->query($sql,[$taskGuid,$taskGuid])->fetchAll();
        if(empty($resultArray)){
            return array();
        }
        return $this->groupByMatchrate($resultArray,$isExport);
    }
    
    /***
     * Group the results by match rate group.
     * @param array $results
     * @return array[]
     */
    private function groupByMatchrate($results,$isExport=false){
        
        $groupedResults=[];
        
        //103%, 102%, 101%. 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
        $groupBorder=[102=>'103',101=>'102',100=>'101',99=>'100',89=>'99',79=>'89',69=>'79',59=>'69',50=>'59','noMatch'=>'noMatch'];
        
        $analysisCreated=null;
        
        $bestTmmtMatches=[];
        $segmentBest=[];
        //count only the best match rate from the tm
        foreach ($results as $res){
            if(!isset($bestTmmtMatches[$res['segmentNrInTask']])){
                $bestTmmtMatches[$res['segmentNrInTask']]=$res['matchRate'];
                $segmentBest[$res['segmentNrInTask']]=$res['tmmtid'];
            }
            if($bestTmmtMatches[$res['segmentNrInTask']]<$res['matchRate']){
                $segmentBest[$res['segmentNrInTask']]=$res['tmmtid'];
            }
        }
        
        unset($bestTmmtMatches);
        
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        foreach ($results as $res){
        
            //for each tmmt separate array
            if(!isset($groupedResults[$res['tmmtid']])){
                $groupedResults[$res['tmmtid']]=[];
                $groupedResults[$res['tmmtid']]['resourceName']="";
                $groupedResults[$res['tmmtid']]['resourceColor']="";
                //set tmmt color and name
                if($res['tmmtid']>0){
                    $tmmtModel=ZfExtended_Factory::get('editor_Models_TmMt');
                    /* $tmmtModel editor_Models_TmMt */
                    try {
                        $tmmtModel->load($res['tmmtid']);
                        $groupedResults[$res['tmmtid']]['resourceName']=$tmmtModel->getName();
                        $groupedResults[$res['tmmtid']]['resourceColor']=$tmmtModel->getColor();
                    } catch (Exception $e) {
                        $groupedResults[$res['tmmtid']]['resourceName']="This resource is removed";
                        $groupedResults[$res['tmmtid']]['resourceColor']="";
                    }
                }
                
                
            }
            
            
            $resultFound=false;
            
            //check on which border group this result belongs to
            foreach ($groupBorder as $border=>$value){
                
                if(!isset($groupedResults[$res['tmmtid']][$value])){
                    if(!$isExport){
                        $groupedResults[$res['tmmtid']][$value]=[];
                        $groupedResults[$res['tmmtid']][$value]['rateCount']=0;
                        $groupedResults[$res['tmmtid']][$value]['wordCount']=0;
                    }else{
                        $groupedResults[$res['tmmtid']][$value]=0;
                    }
                }
                //if result is found, create only empty column
                if($resultFound || $segmentBest[$res['segmentNrInTask']]!=$res['tmmtid']){
                    continue;
                }

                //check matchrate border
                if($res['matchRate']>$border){
                    if(!$isExport){
                        $groupedResults[$res['tmmtid']][$value]['rateCount']++;
                        $groupedResults[$res['tmmtid']][$value]['wordCount']+=$res['wordCount'];
                    }else{
                        $groupedResults[$res['tmmtid']][$value]+=$res['wordCount'];
                    }
                    $resultFound=true;
                }
            }
            if(!$resultFound){
                if(!$isExport){
                    $groupedResults[$res['tmmtid']]['noMatch']['rateCount']++;
                    $groupedResults[$res['tmmtid']]['noMatch']['wordCount']+=$res['wordCount'];
                }else{
                    $groupedResults[$res['tmmtid']]['noMatch']+=$res['wordCount'];
                }
            }
            
            //get the analysis created date
            if(!$analysisCreated){
                $model=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
                /* @var $model editor_Plugins_MatchAnalysis_Models_TaskAssoc */
                $model->load($res['analysisId']);
                $analysisCreated=$model->getCreated();
                $internalFuzzy=$model->getInternalFuzzy();
                $pretranslateMatchrate=$model->getPretranslateMatchrate();
            }
            
            $groupedResults[$res['tmmtid']]['created']=$analysisCreated;
            $groupedResults[$res['tmmtid']]['internalFuzzy']=filter_var($internalFuzzy, FILTER_VALIDATE_BOOLEAN) ? $translate->_("Ja"): $translate->_("Nein");
            $groupedResults[$res['tmmtid']]['pretranslateMatchrate']=$pretranslateMatchrate;
        }
        unset($segmentBest);
        return $this->sortByTm($groupedResults);
    }

    /***
     * Sort by best  tmmt
     * TODO: implement me
     * @param array $groupedResults
     * @return array
     */
    private function sortByTm($groupedResults){
        $retArray=[];
        foreach ($groupedResults as $tmmtgroup){
            array_push($retArray, $tmmtgroup);
        }
        return $retArray;
    }
}