<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Integration\FileBasedInterface;
use MittagQI\Translate5\Penalties\DataProvider\TaskPenaltyDataProvider;
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\PresetPrices;
use MittagQI\Translate5\Plugins\MatchAnalysis\Models\Pricing\PresetRange;
use ZfExtended_Factory as Factory;

/**
 * MatchAnalysis Entity Object
 *
 * @method string getId()
 * @method void setId(int $id)
 *
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 * @method string getSegmentId()
 * @method void setSegmentId(int $segmentId)
 *
 * @method string getSegmentNrInTask()
 * @method void setSegmentNrInTask(int $segmentNrInTask)
 *
 * @method string getLanguageResourceid()
 * @method void setLanguageResourceid(?int $languageResourceid)
 *
 * @method string getMatchRate()
 * @method void setMatchRate(int $matchrate)
 *
 * @method string getPenaltyGeneral()
 * @method void setPenaltyGeneral(int $penaltyGeneral)
 *
 * @method string getPenaltySublang()
 * @method void setPenaltySublang(int $penaltySublang)
 *
 * @method string getWordCount()
 * @method void setWordCount(int $wordCount)
 *
 * @method string getCharacterCount()
 * @method void setCharacterCount(int $characterCount)
 *
 * @method string getType()
 * @method void setType(string $type)
 *
 * @method string getAnalysisId()
 * @method void setAnalysisId(int $analysisId)
 *
 * @method string getInternalFuzzy()
 * @method void setInternalFuzzy(int $internalFuzzy)
 */
class editor_Plugins_MatchAnalysis_Models_MatchAnalysis extends ZfExtended_Models_Entity_Abstract
{
    /***
     * Database field name when counting analysis on character based
     */
    public const UNIT_COUNT_CHARACTER = 'characterCount';

    /***
     * Database field name when counting analysis on word based
     */
    public const UNIT_COUNT_WORD = 'wordCount';

    protected static $languageResourceCache = [];

    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_MatchAnalysis';

    protected $validatorInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Validator_MatchAnalysis';

    /**
     * Match analysis groups and calculation borders
     */
    protected array $fuzzyRanges = [];

    /**
     * Pricing info for current task's target language
     */
    protected array $pricing = [];

    /***
     * Load the result by best match rate. The results will be grouped in the followed groups:
     * Real groups:        103%, 102%, 101%, 100%, 99%-90%, 89%-80%, 79%-70%, 69%-60%, 59%-51%, 50% - 0%
     * Group result index: 103,  102,  101,  100,  99,      89,      79,      69,      59,      noMatch
     *
     * Ex: group index 99 is every matchrate between 90 and 99
     *
     * @param string $taskGuid
     * @param bool $groupData return raw data
     * @param string|null $unitType
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function loadByBestMatchRate(string $taskGuid, bool $groupData = true, ?string $unitType = null): array
    {
        /* @var $task editor_Models_Task */
        $task = Factory::get('editor_Models_Task');
        $task->loadByTaskGuid($taskGuid);
        $this->loadFuzzyBoundaries($task);

        //load the latest analysis for the given taskGuid
        /** @var editor_Plugins_MatchAnalysis_Models_TaskAssoc $analysisAssoc */
        $analysisAssoc = Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        $analysisAssoc = $analysisAssoc->loadNewestByTaskGuid($taskGuid);
        if (empty($analysisAssoc)) {
            return [];
        }

        if (is_null($unitType) || $unitType === 'word') {
            $unitTypeCol = self::UNIT_COUNT_WORD;
        } elseif ($unitType === 'character') {
            $unitTypeCol = self::UNIT_COUNT_CHARACTER;
        } else {
            $unitTypeCol = self::UNIT_COUNT_WORD;
        }

        $sqlV3 = 'SELECT bestRates.internalFuzzy,bestRates.languageResourceid,bestRates.matchRate, SUM(bestRates.' . $unitTypeCol . ') unitCount, SUM(bestRates.segCount) segCount
                  FROM (
                    SELECT t1.*, 1 as segCount
                    FROM LEK_match_analysis AS t1
                    INNER JOIN LEK_segments s ON t1.segmentId = s.id AND s.autoStateId != ? AND s.autoStateId != ?
                    LEFT OUTER JOIN LEK_match_analysis AS t2
                    ON t1.segmentId = t2.segmentId
                      AND (t1.matchRate < t2.matchRate OR t1.matchRate = t2.matchRate AND t1.id < t2.id) AND t2.analysisId = ?
                    WHERE t2.segmentId IS NULL AND t1.analysisId = ?
                  ) bestRates
                  GROUP BY bestRates.internalFuzzy, bestRates.languageResourceid, bestRates.matchRate;';

        $bind = [editor_Models_Segment_AutoStates::LOCKED, editor_Models_Segment_AutoStates::BLOCKED, $analysisAssoc['id'], $analysisAssoc['id']];

        $resultArray = $this->db->getAdapter()->query($sqlV3, $bind)->fetchAll();
        if (empty($resultArray)) {
            // we have to set an emoty result here, in order that the result is correctly grouped and displayed in the UI
            $resultArray = [[
                'internalFuzzy' => '0',
                'languageResourceid' => '0',
                'matchRate' => '0',
                'unitCount' => '0',
                'segCount' => '0',
            ]];
        }
        if ($groupData) {
            // Get grouped data
            $rows = $this->groupByMatchrate($resultArray, $analysisAssoc);

            // Calculate and append summary row
            $summary = [];
            foreach ($rows as $row) {
                foreach ($row as $prop => $value) {
                    if (is_numeric($prop) || $prop == 'unitCountTotal' || $prop == 'noMatch') {
                        $summary[$prop] = ($summary[$prop] ?? 0) + $value;
                    } elseif ($prop == 'resourceName') {
                        $summary[$prop] = 'summary';
                    } else {
                        $summary[$prop] = '';
                    }
                }
            }
            $rows[] = $summary;

            // If requested unitType equals to current preset's unitType
            // it means at least one preset exists having such unitType,
            // so we add pricing row
            if ($unitType == $this->pricing['unitType']) {
                // Pricing row
                $pricing = [];

                // Calculate values for pricing row
                foreach ($summary as $prop => $value) {
                    if (is_numeric($prop)) {
                        $pricing[$prop] = ($pricing[$prop] ?? 0) + round($value * $this->pricing['prices'][$prop], 2);
                    } elseif ($prop == 'noMatch') {
                        $pricing[$prop] = ($pricing[$prop] ?? 0) + round($value * $this->pricing['noMatch'], 2);
                    } elseif ($prop == 'unitCountTotal' || $prop == 'penaltyTotal') {
                        // Skip that prop
                    } elseif ($prop == 'resourceName') {
                        $pricing[$prop] = 'amount';
                    } else {
                        $pricing[$prop] = 0;
                    }
                }

                // Get total, price adjustment and final amount
                $pricing['unitCountTotal'] = round(array_sum($pricing), 2);
                $pricing['priceAdjustment'] = (int) $this->pricing['priceAdjustment'];
                $pricing['finalAmount'] = round($pricing['unitCountTotal'] + $pricing['priceAdjustment'], 2);

                // Append pricing-row
                $rows[] = $pricing;
            }

            // Return
            return $rows;
        }

        return $this->addLanguageResourceInfos($resultArray);
    }

    protected function loadFuzzyBoundaries(editor_Models_Task $task)
    {
        // Get pricing preset id for current task
        $presetId = $ranges = $task->meta()->getPricingPresetId();

        // Get ranges
        $ranges = Factory::get(PresetRange::class)->getPairsByPresetId($presetId);

        // Get pricing
        $this->pricing = Factory::get(PresetPrices::class)->getPricesFor(
            $presetId,
            (int) $task->getSourceLang(),
            (int) $task->getTargetLang()
        );

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
        $analysisAssoc = Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
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

        //init the language resources group array
        $groupedResults = $this->initResultArray($analysisAssoc);
        //flag, indicating if analysis contains imported match rate info (to avoid duplicates info output)
        $withImportedMatchRates = false;
        foreach ($results as $res) {
            //the key will be languageResource->ServiceType + fuzzy flag (ex: "OpenTm2 memoryfuzzy")
            //because for the internal fuzzy additional row is displayed
            $isInternalFuzzy = $res['internalFuzzy'] == '1';
            if ($isInternalFuzzy) {
                $lr = $this->getLanguageResourceCached((int) $res['languageResourceid']);
                $rowKey = $this->getFuzzyName($lr?->getResourceId() ?? 'deleted ressource');
            } else {
                $rowKey = $res['languageResourceid'];
                if (! $withImportedMatchRates && $rowKey === null) {
                    $withImportedMatchRates = true;
                }
            }

            //results found in group
            $resultFound = false;

            if (! isset($groupedResults[$rowKey]['unitCountTotal'])) {
                $groupedResults[$rowKey]['unitCountTotal'] = 0;
            }

            //check on which border group this result belongs to
            foreach ($this->fuzzyRanges as $begin => $end) {
                //check if the language resource is not initialized by group initializer
                if (! isset($groupedResults[$rowKey]['resourceName']) && $res['languageResourceid'] > 0) {
                    //the resource is removed for the assoc, but the analysis stays
                    $newName = $translate->_('Diese Sprachressource wurde entfernt (ID: %s)');
                    $groupedResults[$rowKey]['resourceName'] = sprintf(
                        $newName,
                        $isInternalFuzzy
                            ? $this->getFuzzyName($res['languageResourceid'])
                            : $res['languageResourceid']
                    );
                    $groupedResults[$rowKey]['resourceColor'] = "";
                }

                //set the group key in the array
                if (! isset($groupedResults[$rowKey][$begin])) {
                    $groupedResults[$rowKey][$begin] = 0;
                }

                //if result is found, create only empty column
                if ($resultFound) {
                    continue;
                }

                //check matchrate border
                if ($begin <= $res['matchRate'] && $res['matchRate'] <= $end) {
                    $groupedResults[$rowKey][$begin] += $res['unitCount'];
                    $groupedResults[$rowKey]['unitCountTotal'] += $res['unitCount'];
                    $resultFound = true;
                }
            }
            //if no results match group is found, the result is in noMatch group
            if (! $resultFound) {
                $groupedResults[$rowKey]['noMatch'] += $res['unitCount'];
                $groupedResults[$rowKey]['unitCountTotal'] += $res['unitCount'];
            }
        }

        // Either no duplicates or no imported match rates
        unset($groupedResults[$withImportedMatchRates ? 0 : null]);

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
        $taskAssoc = Factory::get('MittagQI\Translate5\LanguageResource\TaskAssociation');
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
            $row[$key]['errorCount'] = $analysisData['errorCount'];
            $row[$key]['internalFuzzy'] = $fuzzyString;
            $row[$key]['unitCountTotal'] = 0;
            if ($type !== 'auto-propagated') {
                $row[$key]['penaltyTotal'] = 0;
            }

            //init the fuzzy range groups with 0
            foreach (array_keys($this->fuzzyRanges) as $begin) {
                if (! isset($row[$key][$begin])) {
                    $row[$key][$begin] = 0;
                }
            }

            return $row;
        };

        $initGroups = [];

        $task = Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($analysisData['taskGuid']);

        $fuzzyTypes = [];

        $taskPenaltyDataProvider = TaskPenaltyDataProvider::create();

        foreach ($langResTaskAssocs as $res) {
            $lr = $this->getLanguageResourceCached($res['languageResourceId']);
            if ($isInternalFuzzy && $lr->getResourceType() == editor_Models_Segment_MatchRateType::TYPE_TM) {
                $fuzzyTypes[$lr->getResourceId()] = $lr->getServiceName();
            }

            //if the languageresource was deleted, we can not add additional data here
            if (empty($lr)) {
                $initGroups = $initGroups + $initRow($res['languageResourceId'], 'deleted!', 'cdcdcd', 'n/a');

                continue;
            }

            //init the group
            $initGroups = $initGroups + $initRow($lr->getId(), $lr->getName(), $lr->getColor(), $lr->getResourceType());

            // Calculate penalties
            $penalties = $taskPenaltyDataProvider->getPenalties($task->getTaskGuid(), $lr->getId());

            // Apply total penalty to assoc langres row in analysis grid
            $initGroups[$lr->getId()]['penaltyTotal'] = $penalties['penaltyGeneral'] + $penalties['penaltySublang'];
        }

        if ($isInternalFuzzy) {
            $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
            //the key will be languageResourceId + fuzzy flag (ex: "OpenTm2 memoryfuzzy")
            //for each internal fuzzy, additional row is displayed
            //init the internal fuzzies
            foreach ($fuzzyTypes as $resourceId => $name) {
                $parts = explode('_', $resourceId);
                $resourceNumber = array_pop($parts);

                $initGroups = $initGroups + $initRow(
                    $this->getFuzzyName($resourceId),
                    sprintf($translate->_('Interne Fuzzys (%s)'), "$name $resourceNumber"),
                    '',
                    editor_Models_Segment_MatchRateType::TYPE_TM
                );
            }
        }

        //init the repetition
        $initGroups = $initGroups + $initRow(0, "", "", editor_Models_Segment_MatchRateType::TYPE_AUTO_PROPAGATED);
        //init matchrate data from the imported file, see TRANSLATE-4221
        $initGroups = $initGroups + $initRow(null, editor_Models_Segment_MatchRateType::TYPE_SOURCE, "", "");

        return $initGroups;
    }

    private function getFuzzyName(string $resourceId): string
    {
        return 'Fuzzy ' . $resourceId;
    }

    /**
     * adds the language resource meta data to the result set
     */
    protected function addLanguageResourceInfos(array $data): array
    {
        foreach ($data as $idx => $row) {
            $id = (int) $row['languageResourceid'];
            $lr = $this->getLanguageResourceCached($id);
            if ($id === 0 && $row['matchRate'] == FileBasedInterface::REPETITION_MATCH_VALUE) {
                $data[$idx]['type'] = editor_Models_Segment_MatchRateType::TYPE_AUTO_PROPAGATED;
                $data[$idx]['name'] = 'repetition';
            } elseif (empty($lr)) {
                $data[$idx]['type'] = 'n/a';
                $data[$idx]['name'] = 'language resource deleted';
            } else {
                $data[$idx]['type'] = $lr->getResourceType();
                $data[$idx]['name'] = $lr->getName();
            }
        }

        return $data;
    }

    /**
     * Loads and returns a languageresource by id, returns null if the given language resource does not exist
     */
    protected function getLanguageResourceCached(int $id): ?editor_Models_LanguageResources_LanguageResource
    {
        if (! array_key_exists($id, self::$languageResourceCache)) {
            $lr = Factory::get('editor_Models_LanguageResources_LanguageResource');

            /* @var $lr editor_Models_LanguageResources_LanguageResource */
            try {
                $lr->load($id);
            } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                $lr = null;
            }
            self::$languageResourceCache[$id] = $lr;
        }

        return self::$languageResourceCache[$id];
    }

    /**
     * returns the defined match rate ranges, from highest to lowest
     */
    public function getFuzzyRanges(editor_Models_Task $task = null): array
    {
        // If $task is given - load ranges according to task's pricing preset id
        if ($task) {
            $this->loadFuzzyBoundaries($task);
        }

        return $this->fuzzyRanges;
    }

    /**
     * @return mixed|string
     */
    public function getPricing()
    {
        return $this->pricing;
    }
}
