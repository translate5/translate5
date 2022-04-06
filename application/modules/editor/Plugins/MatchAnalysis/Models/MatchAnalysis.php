<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
    
    protected static $languageResourceCache = [];

    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_MatchAnalysis';
    protected $validatorInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Validator_MatchAnalysis';

    /**
     * Match analysis groups and calculation borders
     */
    protected array $fuzzyRanges = [];

    /***
     * Load the result by best match rate. The results will be grouped in the followed groups:
     * Real groups:        103%, 102%, 101%, 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
     * Group result index: 103,  102,  101,  100,  99,      89,      79,      69,      59,      noMatch
     *
     * Ex: group index 99 is every matchrate between 90 and 99
     *
     * @param string $taskGuid
     * @param bool $rawData return raw data
     * @return array
     */
    public function loadByBestMatchRate(string $taskGuid, bool $groupData = true): array
    {
        //load the latest analysis for the given taskGuid
        /** @var editor_Plugins_MatchAnalysis_Models_TaskAssoc $analysisAssoc */
        $analysisAssoc = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        $analysisAssoc = $analysisAssoc->loadNewestByTaskGuid($taskGuid);
        if (empty($analysisAssoc)) {
            return [];
        }
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $this->loadFuzzyBoundaries($task);

        $sqlV3 = 'SELECT bestRates.internalFuzzy,bestRates.languageResourceid,bestRates.matchRate, SUM(bestRates.wordCount) wordCount, SUM(bestRates.segCount) segCount
                  FROM (
                    SELECT t1.*, 1 as segCount
                    FROM LEK_match_analysis AS t1
                    INNER JOIN LEK_segments s ON t1.segmentId = s.id AND s.autoStateId != ?
                    LEFT OUTER JOIN LEK_match_analysis AS t2
                    ON t1.segmentId = t2.segmentId
                      AND (t1.matchRate < t2.matchRate OR t1.matchRate = t2.matchRate AND t1.id < t2.id) AND t2.analysisId = ?
                    WHERE t2.segmentId IS NULL AND t1.analysisId = ?
                  ) bestRates
                  GROUP BY bestRates.internalFuzzy, bestRates.languageResourceid, bestRates.matchRate;';

        $bind = [editor_Models_Segment_AutoStates::BLOCKED, $analysisAssoc['id'], $analysisAssoc['id']];
        
        $resultArray = $this->db->getAdapter()->query($sqlV3, $bind)->fetchAll();
        if (empty($resultArray)) {
            // we have to set an emoty result here, in order that the result is correctly grouped and displayed in the UI
            $resultArray = [[
                'internalFuzzy' => '0',
                'languageResourceid' => '0',
                'matchRate' => '0',
                'wordCount' => '0',
                'segCount' => '0',
            ]];
        }
        if($groupData) {
            return $this->groupByMatchrate($resultArray, $analysisAssoc);
        }
        return $this->addLanguageResourceInfos($resultArray);
    }

    protected function loadFuzzyBoundaries(editor_Models_Task $task) {
        $ranges = $task->getConfig()->runtimeOptions->plugins->MatchAnalysis->fuzzyBoundaries->toArray();
        ksort($ranges);
        $this->fuzzyRanges = array_reverse($ranges, true);
        $this->fuzzyRanges['noMatch'] = 'noMatch';
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

            if (!isset($groupedResults[$rowKey]['wordCountTotal'])) {
                $groupedResults[$rowKey]['wordCountTotal'] = 0;
            }

            //check on which border group this result belongs to
            foreach ($this->fuzzyRanges as $begin => $end) {

                //check if the language resource is not initialized by group initializer
                if (!isset($groupedResults[$rowKey]) && $res['languageResourceid'] > 0) {
                    //the resource is removed for the assoc, but the analysis stays
                    $groupedResults[$rowKey]['resourceName'] = $translate->_("Diese Ressource wird entfernt");
                    $groupedResults[$rowKey]['resourceColor'] = "";
                }

                //set the group key in the array
                if (!isset($groupedResults[$rowKey][$begin])) {
                    $groupedResults[$rowKey][$begin] = 0;
                }

                //if result is found, create only empty column
                if ($resultFound) {
                    continue;
                }

                //check matchrate border
                if ($begin <= $res['matchRate'] && $res['matchRate'] <= $end) {
                    $groupedResults[$rowKey][$begin] += $res['wordCount'];
                    $groupedResults[$rowKey]['wordCountTotal'] += $res['wordCount'];
                    $resultFound = true;
                }
            }
            //if no results match group is found, the result is in noMatch group
            if (!$resultFound) {
                $groupedResults[$rowKey]['noMatch'] += $res['wordCount'];
                $groupedResults[$rowKey]['wordCountTotal'] += $res['wordCount'];
            }
        }
        return array_values($groupedResults);
    }

    /***
     * Init match analysis result array. For each assoc language resource one row will be created.
     * @param array $analysisData
     * @return array
     * @throws Zend_Exception
     */
    //protected function initResultArray($taskGuid,$internalFuzzy){
    protected function initResultArray(array $analysisData): array
    {
        $taskAssoc = ZfExtended_Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
        /* @var $taskAssoc MittagQI\Translate5\LanguageResource\TaskAssociation */
        $langResTaskAssocs = $taskAssoc->loadByTaskGuids([$analysisData['taskGuid']]);

        $isInternalFuzzy = $analysisData['internalFuzzy'] == '1';
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        $fuzzyString = $isInternalFuzzy ? $translate->_("Ja") : $translate->_("Nein");

        //create empty group for given key,name and color
        $initRow = function ($key, $name, $color, $type) use ($analysisData, $fuzzyString) {
            $row = [];
            $row[$key] = [];
            $row[$key]['resourceName'] = "";
            $row[$key]['resourceColor'] = "";
            //set languageResource color and name
            //if the resource is internal fuzzy, change the name
            $row[$key]['resourceName'] = $name;
            $row[$key]['resourceType'] = $type;
            $row[$key]['resourceColor'] = $color;

            $row[$key]['created'] = $analysisData['created'];
            $row[$key]['internalFuzzy'] = $fuzzyString;
            $row[$key]['wordCountTotal'] = 0;


            //init the fuzzy range groups with 0
            foreach (array_keys($this->fuzzyRanges) as $begin) {
                if (!isset($row[$key][$begin])) {
                    $row[$key][$begin] = 0;
                }
            }
            return $row;
        };

        $initGroups = [];

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($analysisData['taskGuid']);

        foreach ($langResTaskAssocs as $res) {
            $lr = $this->getLanguageResourceCached($res['languageResourceId']);
            
            //if the languageresource was deleted, we can not add additional data here
            if(empty($lr)) {
                $initGroups = $initGroups + $initRow($res['languageResourceId'], 'deleted!', 'cdcdcd', 'n/a');
                continue;
            }

            //init the group
            $initGroups = $initGroups + $initRow($lr->getId(), $lr->getName(), $lr->getColor(), $lr->getResourceType());

            //if internal fuzzy is activated, and the langage resource is of type tm, add aditional internal fuzzy row
            if ($isInternalFuzzy && $lr->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TM) {
                //the key will be languageResourceId + fuzzy flag (ex: "OpenTm2 memoryfuzzy")
                //for each internal fuzzy, additional row is displayed
                $initGroups = $initGroups + $initRow(($lr->getId() . 'fuzzy'), ($lr->getName() . ' - internal Fuzzies'), $lr->getColor(), $lr->getResourceType());
            }
        }
        //init the repetition
        $initGroups = $initGroups + $initRow(0, "", "", editor_Models_Segment_MatchRateType::TYPE_AUTO_PROPAGATED);
        return $initGroups;
    }

    /**
     * adds the language resource meta data to the result set
     * @param array $data
     * @return array
     */
    protected function addLanguageResourceInfos(array $data): array {
        foreach($data as $idx => $row) {
            $id = (int) $row['languageResourceid'];
            $lr = $this->getLanguageResourceCached($id);
            if($id === 0 && $row['matchRate'] == editor_Services_Connector_FilebasedAbstract::REPETITION_MATCH_VALUE) {
                $data[$idx]['type'] = editor_Models_Segment_MatchRateType::TYPE_AUTO_PROPAGATED;
                $data[$idx]['name'] = 'repetition';
            }
            elseif(empty($lr)) {
                $data[$idx]['type'] = 'n/a';
                $data[$idx]['name'] = 'language resource deleted';
            }
            else {
                $data[$idx]['type'] = $lr->getResourceType();
                $data[$idx]['name'] = $lr->getName();
            }
        }
        return $data;
    }
    
    /**
     * Loads and returns a languageresource by id, returns null if the given language resource does not exist
     * @param int $id
     * @return editor_Models_LanguageResources_LanguageResource|NULL
     */
    protected function getLanguageResourceCached(int $id): ?editor_Models_LanguageResources_LanguageResource {
        if(!array_key_exists($id, self::$languageResourceCache)) {
            $lr = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $lr editor_Models_LanguageResources_LanguageResource */
            try {
                $lr->load($id);
            }catch (ZfExtended_Models_Entity_NotFoundException $e) {
                $lr = null;
            }
            self::$languageResourceCache[$id] = $lr;
        }
        return self::$languageResourceCache[$id];
    }

    /**
     * returns the defined match rate ranges, from highest to lowest
     * @return array
     */
    public function getFuzzyRanges(): array {
        return $this->fuzzyRanges;
    }
}