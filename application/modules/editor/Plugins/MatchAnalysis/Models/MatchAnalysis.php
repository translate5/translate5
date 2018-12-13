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
 * @method integer getLanguageResourceid() getLanguageResourceid()
 * @method void setLanguageResourceid() setLanguageResourceid(integer $languageResourceid)
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
     * Ex: group index 99 is every matchrate between 90 and 99 
     * 
     * @param string $taskGuid
     * @param boolean $isExport: is the data requested for export
     * @return array
     */
    public function loadByBestMatchRate($taskGuid,$isExport=false){
        //load the latest analysis for the given taskGuid
        $analysisAssoc=ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc=$analysisAssoc->loadNewestByTaskGuid($taskGuid);
        
        $sqlV1='SELECT wordCount,languageResourceid,matchRate,analysisId,internalFuzzy,segmentId FROM (
                SELECT SUM(o.wordCount) wordCount,o.languageResourceid,o.matchRate,o.analysisId,o.internalFuzzy,o.segmentId
                FROM LEK_match_analysis o
                  LEFT JOIN LEK_match_analysis b
                      ON o.segmentId = b.segmentId AND IF(o.matchRate IS NULL,0,o.matchRate) < IF(b.matchRate IS NULL,0,b.matchRate)
                WHERE b.matchRate is NULL
                AND o.analysisId=?
                GROUP BY o.internalFuzzy,o.languageResourceid,o.matchRate # group by internal fuzzie so we get it as separate row if exist
                ORDER BY o.languageResourceid DESC,o.matchRate DESC
                ) s GROUP BY s.segmentId;';
        $sqlV2='SELECT bestRates.internalFuzzy,bestRates.languageResourceid,bestRates.matchRate, SUM(bestRates.wordCount) wordCount  FROM (
                SELECT t1.*
                FROM LEK_match_analysis AS t1
                LEFT OUTER JOIN LEK_match_analysis AS t2
                ON t1.segmentId = t2.segmentId 
                        AND (t1.matchRate < t2.matchRate OR t1.matchRate = t2.matchRate AND t1.id < t2.id) AND t2.internalFuzzy = 0 AND t2.analysisId = ?
                WHERE t2.segmentId IS NULL AND t1.analysisId = ? AND t1.internalFuzzy = 0
                UNION
                SELECT t1.*
                FROM LEK_match_analysis AS t1
                LEFT OUTER JOIN LEK_match_analysis AS t2
                ON t1.segmentId = t2.segmentId 
                        AND (t1.matchRate < t2.matchRate OR t1.matchRate = t2.matchRate AND t1.id < t2.id) AND t2.internalFuzzy = 1 AND t2.analysisId = ?
                WHERE t2.segmentId IS NULL AND t1.analysisId = ? AND t1.internalFuzzy = 1
            ) bestRates GROUP BY bestRates.internalFuzzy,bestRates.languageResourceid,bestRates.matchRate;';
        $resultArray=$this->db->getAdapter()->query($sqlV2,[$analysisAssoc['id'],$analysisAssoc['id'],$analysisAssoc['id'],$analysisAssoc['id']])->fetchAll();
        //$resultArray=$this->db->getAdapter()->query($sqlV1,[$analysisAssoc['id']])->fetchAll();
        if(empty($resultArray)){
            return array();
        }
        return $this->groupByMatchrate($resultArray,$analysisAssoc);
    }
    
    /***
     * Group the results by matchrate. Each row is one language resource result group.
     * @param array $results
     * @param array $analysisAssoc
     * @return array
     */
    protected function groupByMatchrate(array $results,array $analysisAssoc){

        //104%, 103%, 102%, 101%. 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
        $groupBorder=['103'=>'104','102'=>'103','101'=>'102','100'=>'101','99'=>'100','89'=>'99','79'=>'89','69'=>'79','59'=>'69','50'=>'59','noMatch'=>'noMatch'];
        
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        $groupedResults=[];
        foreach ($results as $res){
            
            //the key will be languageResourceId + fuzzy flag (ex: "OpenTm2 memoryfuzzy")
            //because for the internal fuzzy additional row is displayed
            $rowKey=$res['languageResourceid'].($res['internalFuzzy']=='1' ? 'fuzzy' : '');
            
            //for each languageResource separate array
            if(!isset($groupedResults[$rowKey])){
                $groupedResults[$rowKey]=[];
                $groupedResults[$rowKey]['resourceName']="";
                $groupedResults[$rowKey]['resourceColor']="";
                //set languageResource color and name
                if($res['languageResourceid']>0){
                    $languageResourceModel=ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
                    /* $languageResourceModel editor_Models_LanguageResources_LanguageResource */
                    try {
                        $languageResourceModel->load($res['languageResourceid']);
                        //if the resource is internal fuzzy, change the name
                        $groupedResults[$rowKey]['resourceName']=$languageResourceModel->getName().($res['internalFuzzy']=='1' ? ' - internal Fuzzies':'');
                        $groupedResults[$rowKey]['resourceColor']=$languageResourceModel->getColor();
                    } catch (Exception $e) {
                        $groupedResults[$rowKey]['resourceName']="This resource is removed";
                        $groupedResults[$rowKey]['resourceColor']="";
                    }
                }
            }
            
            //results found in group
            $resultFound=false;
            
            //check on which border group this result belongs to
            foreach ($groupBorder as $border=>$value){
                
                //set the group key in the array
                if(!isset($groupedResults[$rowKey][$value])){
                    $groupedResults[$rowKey][$value]=0;
                }
                
                //if result is found, create only empty column
                if($resultFound){
                    continue;
                }
                
                //check matchrate border
                if($res['matchRate']>$border){
                    $groupedResults[$rowKey][$value]+=$res['wordCount'];
                    $resultFound=true;
                }
            }
            //if no results match group is found, the result is in noMatch group
            if(!$resultFound){
                $groupedResults[$rowKey]['noMatch']+=$res['wordCount'];
            }
            
            $groupedResults[$rowKey]['created']=$analysisAssoc['created'];
            $groupedResults[$rowKey]['internalFuzzy']=filter_var($analysisAssoc['internalFuzzy'], FILTER_VALIDATE_BOOLEAN) ? $translate->_("Ja"): $translate->_("Nein");
        }
        
        return array_values($groupedResults);
    }
    
    /***
     * Sort by best  languageResource
     * TODO: implement me
     * @param array $groupedResults
     * @return array
     */
    private function sortByTm($groupedResults){
        $retArray=[];
        foreach ($groupedResults as $languageResourcegroup){
            array_push($retArray, $languageResourcegroup);
        }
        return $retArray;
    }
}