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
 * @method void setId() setId(int $id)
 *
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $taskGuid)
 * @method integer getSegmentId() getSegmentId()
 * @method void setSegmentId() setSegmentId(int $segmentId)
 *
 * @method integer getSegmentNrInTask() getSegmentNrInTask()
 * @method void setSegmentNrInTask() setSegmentNrInTask(int $segmentNrInTask)
 *
 * @method integer getLanguageResourceid() getLanguageResourceid()
 * @method void setLanguageResourceid() setLanguageResourceid(int $languageResourceid)
 *
 * @method integer getMatchRate() getMatchRate()
 * @method void setMatchRate() setMatchRate(int $matchrate)
 *
 * @method integer getWordCount() getWordCount()
 * @method void setWordCount() setWordCount(int $wordCount)
 *
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 *
 * @method integer getAnalysisId() getAnalysisId()
 * @method void setAnalysisId() setAnalysisId(int $analysisId)
 *
 * @method integer getInternalFuzzy() getInternalFuzzy()
 * @method void setInternalFuzzy() setInternalFuzzy(int $internalFuzzy)
 *
 */
class editor_Plugins_MatchAnalysis_Models_MatchAnalysis extends ZfExtended_Models_Entity_Abstract
{

    /***
     * Custom task state when matchanalysis are running
     * @var string
     */
    const TASK_STATE_ANALYSIS = 'matchanalysis';

    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_MatchAnalysis';
    protected $validatorInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Validator_MatchAnalysis';

    /***
     * Match analysis groups and calculation borders
     *
     * 104%, 103%, 102%, 101%. 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
     */
    protected $groupBorder = ['103' => '104', '102' => '103', '101' => '102', '100' => '101', '99' => '100', '89' => '99', '79' => '89', '69' => '79', '59' => '69', '50' => '59', 'noMatch' => 'noMatch'];

    /***
     * Load the result by best match rate. The results will be grouped in the followed groups:
     * Real groups:        103%, 102%, 101%, 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
     * Group result index: 103,  102,  101,  100,  99,      89,      79,      69,      59,      noMatch
     *
     * Ex: group index 99 is every matchrate between 90 and 99
     *
     * @param string $taskGuid
     * @param bool $isExport : is the data requested for export
     * @return array
     */
    public function loadByBestMatchRate(string $taskGuid, bool $isExport = false): array
    {
        //load the latest analysis for the given taskGuid
        $analysisAssoc = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc = $analysisAssoc->loadNewestByTaskGuid($taskGuid);
        if (empty($analysisAssoc)) {
            return [];
        }
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        //if edit 100% matches is disabled for the task, filter out the blocked segments from the analysis 
        $blockedFilter = '';
        //TODO: this should be defined as config
        if (!$task->getEdit100PercentMatch()) {
            $blockedFilter = 'INNER JOIN LEK_segments s ON t1.segmentId = s.id AND s.autoStateId!=' . editor_Models_Segment_AutoStates::BLOCKED;
        }

        /*
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
        */
        $sqlV3 = 'SELECT bestRates.internalFuzzy,bestRates.languageResourceid,bestRates.matchRate, SUM(bestRates.wordCount) wordCount  FROM (
                SELECT t1.*
                    FROM LEK_match_analysis AS t1 '
            . $blockedFilter .
            '
                    LEFT OUTER JOIN LEK_match_analysis AS t2
                    ON t1.segmentId = t2.segmentId
                            AND (t1.matchRate < t2.matchRate OR t1.matchRate = t2.matchRate AND t1.id < t2.id) AND t2.analysisId =?
                	WHERE t2.segmentId IS NULL AND t1.analysisId = ?) bestRates GROUP BY bestRates.internalFuzzy,bestRates.languageResourceid,bestRates.matchRate;';
        //$resultArray=$this->db->getAdapter()->query($sqlV2,[$analysisAssoc['id'],$analysisAssoc['id'],$analysisAssoc['id'],$analysisAssoc['id']])->fetchAll();
        //$resultArray=$this->db->getAdapter()->query($sqlV1,[$analysisAssoc['id']])->fetchAll();
        $resultArray = $this->db->getAdapter()->query($sqlV3, [$analysisAssoc['id'], $analysisAssoc['id']])->fetchAll();
        if (empty($resultArray)) {
            return [];
        }

        return $this->groupByMatchrate($resultArray, $analysisAssoc);
    }

    /***
     * Load the last analysis by the taskGuid.
     * The return result are not grouped
     * @param string $taskGuid
     * @return array
     */
    public function loadLastByTaskGuid(string $taskGuid)
    {
        //load the latest analysis for the given taskGuid
        $analysisAssoc = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc = $analysisAssoc->loadNewestByTaskGuid($taskGuid);
        $s = $this->db->select()
            ->where('analysisId=?', $analysisAssoc['id']);
        return $this->db->fetchAll($s)->toArray();
    }

    /***
     * Group the results by matchrate. Each row is one language resource result group.
     * @param array $results
     * @param array $analysisAssoc
     * @return array
     */
    protected function groupByMatchrate(array $results, array $analysisAssoc)
    {

        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        //init the language reources group array
        $groupedResults = $this->initResultArray($analysisAssoc);
        foreach ($results as $res) {

            //the key will be languageResourceId + fuzzy flag (ex: "OpenTm2 memoryfuzzy")
            //because for the internal fuzzy additional row is displayed
            $rowKey = $res['languageResourceid'] . ($res['internalFuzzy'] == '1' ? 'fuzzy' : '');

            //results found in group
            $resultFound = false;

            //check on which border group this result belongs to
            foreach ($this->groupBorder as $border => $value) {

                //check if the language resource is not initialized by group initializer
                if (!isset($groupedResults[$rowKey]) && $res['languageResourceid'] > 0) {
                    //the resource is removed for the assoc, but the analysis stays
                    $groupedResults[$rowKey]['resourceName'] = $translate->_("Diese Ressource wird entfernt");
                    $groupedResults[$rowKey]['resourceColor'] = "";
                }

                //set the group key in the array
                if (!isset($groupedResults[$rowKey][$value])) {
                    $groupedResults[$rowKey][$value] = 0;
                }

                //if result is found, create only empty column
                if ($resultFound) {
                    continue;
                }

                //check matchrate border
                if ($res['matchRate'] > $border) {
                    $groupedResults[$rowKey][$value] += $res['wordCount'];
                    $resultFound = true;
                }
            }
            //if no results match group is found, the result is in noMatch group
            if (!$resultFound) {
                $groupedResults[$rowKey]['noMatch'] += $res['wordCount'];
            }
        }
        return array_values($groupedResults);
    }

    /***
     * Init match analysis result array. For each assoc language resource one row will be created.
     * @param array $analysisData
     * @return array|number
     */
    //protected function initResultArray($taskGuid,$internalFuzzy){
    protected function initResultArray(array $analysisData)
    {
        $taskAssoc = ZfExtended_Factory::get('editor_Models_LanguageResources_Taskassoc');
        /* @var $taskAssoc editor_Models_LanguageResources_Taskassoc */
        $result = $taskAssoc->loadByTaskGuids([$analysisData['taskGuid']]);

        $isInternalFuzzy = $analysisData['internalFuzzy'] == '1';
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $fuzzyString = $isInternalFuzzy ? $translate->_("Ja") : $translate->_("Nein");

        //create empty group for given key,name and color
        $initRow = function ($key, $name, $color) use ($analysisData, $fuzzyString) {
            $row = [];
            $row[$key] = [];
            $row[$key]['resourceName'] = "";
            $row[$key]['resourceColor'] = "";
            //set languageResource color and name
            //if the resource is internal fuzzy, change the name
            $row[$key]['resourceName'] = $name;
            $row[$key]['resourceColor'] = $color;

            $row[$key]['created'] = $analysisData['created'];
            $row[$key]['internalFuzzy'] = $fuzzyString;


            //init the result borders
            foreach ($this->groupBorder as $border => $value) {
                if (!isset($row[$key][$value])) {
                    $row[$key][$value] = 0;
                }
            }
            return $row;
        };

        $initGroups = [];

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($analysisData['taskGuid']);

        $resourceCache = [];
        foreach ($result as $res) {
            $lr = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $lr editor_Models_LanguageResources_LanguageResource */
            if (!isset($resourceCache[$res['languageResourceId']])) {
                $lr->load($res['languageResourceId']);
                $resourceCache[$res['languageResourceId']] = $lr;
            }
            $lr = $resourceCache[$res['languageResourceId']];

            //init the group
            $initGroups = $initGroups + $initRow($lr->getId(), $lr->getName(), $lr->getColor());

            //if internal fuzzy is activated, and the langage resource is of type tm, add aditional internal fuzzy row
            if ($isInternalFuzzy && $lr->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TM) {
                //the key will be languageResourceId + fuzzy flag (ex: "OpenTm2 memoryfuzzy")
                //for each internal fuzzy, additional row is displayed
                $initGroups = $initGroups + $initRow(($lr->getId() . 'fuzzy'), ($lr->getName() . ' - internal Fuzzies'), $lr->getColor());
            }
        }
        //init the repetition
        $initGroups = $initGroups + $initRow(0, "", "");
        return $initGroups;
    }

    /***
     * Sort by best  languageResource
     * TODO: implement me
     * @param array $groupedResults
     * @return array
     */
    private function sortByTm($groupedResults)
    {
        $retArray = [];
        foreach ($groupedResults as $languageResourcegroup) {
            array_push($retArray, $languageResourcegroup);
        }
        return $retArray;
    }
}