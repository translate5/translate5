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
 * Class editor_Plugins_MatchAnalysis_Export_Xml
 * Export the match analyse result as XML
 */
class editor_Plugins_MatchAnalysis_Export_Xml
{
    /**
     * List of attributes of a analyse node
     * @var array
     */
    const ATTRIBUTES = [
        'words',
        'segments',
        'characters',
        'placeables',
        'tags',
        'repairWords',
        'fullRecallWords',
        'partialRecallWords',
        'edits',
        'adaptiveWords',
        'baselineWords'
    ];
    
    /**
     * List of analyse nodes
     * @var array
     */
    const NODES = [
        'perfect',
        'inContextExact',
        'exact',
        'locked',
        'crossFileRepeated',
        'repeated',
        'total',
        'new',
        'newBaseline',
        'newLearnings',
        'fuzzy',
        'internalFuzzy'
    ];
    
    /**
     * defined fuzzy ranges
     * @var array
     */
    const FUZZY_RANGES = [
        '50' => '59',
        '60' => '69',
        '70' => '79',
        '80' => '89',
        '90' => '99'
    ];
    
    /**
     * Collection of analyse nodes to be rendered
     * @var array
     */
    protected $analyseNodes = [];
    
    /**
     * @var SimpleXMLElement
     */
    protected $rootNode = null;
    
    /**
     * @param SimpleXMLElement $object
     * @param $data
     * @return SimpleXMLElement
     */
    protected function addFuzzyChilds(SimpleXMLElement $object, $data, $childName = 'fuzzy'): SimpleXMLElement
    {
        foreach ($this->FUZZY_RANGES as $min => $max) {
            $child = $object->batchTotal->analyse->addChild($childName);
            $child->addAttribute('min', $min);
            $child->addAttribute('max', $max);
            foreach (self::ATTRIBUTES as $attr) {
                if ($attr == 'words') {
                    $child->addAttribute($attr, $data[$max]);
                }else{
                    $child->addAttribute($attr, '0');
                }
            }
        }
        return $object;
    }

    /**
     * @param SimpleXMLElement $child
     * @param $data
     * @return SimpleXMLElement
     */
    protected function addAttributes(SimpleXMLElement $child, $data): SimpleXMLElement
    {

        foreach (self::ATTRIBUTES as $attr) {
            $child->addAttribute($attr, '0');
            if ($attr == 'words' && $child->getName() == 'fuzzy') {
                $attrib = (array)$child->attributes()->max;

                $child->addAttribute($attr, $data[$attrib[0]]);
            }

        }

        return $child;
    }

    /**
     * @param $rows
     * @return SimpleXMLElement
     * @throws Exception
     */

    public function generateXML($rows, $taskGuid): SimpleXMLElement
    {
        $usedLanguageResources = [];
        
        $hasInternalFuzzy = false;
        
        //llop over data and categorize it
        foreach ($rows as $row) {
            $isMt = $row['type'] == editor_Models_Segment_MatchRateType::TYPE_MT;
            
            $isInternalFuzzy = $row['internalFuzzy'] == 1;
            $hasInternalFuzzy = $hasInternalFuzzy || $isInternalFuzzy;
            if($isMt) {
                $this->add('new', $row);
            }elseif($isInternalFuzzy && $this->isFuzzyRange($row)) {
                $this->add('internalFuzzy', $row); //→ min max kann intern ermittelt werden über die MatchRate
            }
            //crossFileRepeated are translate5s repetitions (which are represented by 102% matches)
            elseif($row['matchRate'] == 102) {
                $this->add('crossFileRepeated', $row);
            }
            //inContextExact are 103%-Matches from translate5
            elseif($row['matchRate'] == 103) {
                $this->add('inContextExact', $row);
            }
            //exact are 100% and 101% and 104%-Matches from translate5, since Trados does not know our 101 and 104%-Matches
            elseif($row['matchRate'] >= 100) {
                $this->add('exact', $row);
            }
            elseif($this->isFuzzyRange($row)) {
                $this->add('fuzzy', $row); //→ min max kann intern ermittelt werden über die MatchRate
            }
            else {
                //if not matching a fuzzy range, it is considered as new
                $this->add('new', $row);
            }
            
            //total is the overall sum
            $this->add('total', $row);
            
            if(!empty($row['languageResourceid'])){
                $usedLanguageResources[$row['languageResourceid']] = $row['name'];
            }
        }

        $this->createRootNode();
        $taskInfo = $this->createTaskInfo($taskGuid);

        foreach($usedLanguageResources as $resourceName) {
            $taskInfo->addChild('tm')->addAttribute('name', $resourceName);
        }

        $this->createSettings($taskInfo, $hasInternalFuzzy);
        $this->createBatchTotalNode();

        return $this->rootNode;
    }
    
    /**
     * returns true if rows matchrate is in one of the defined fuzzy ranges,
     *   if yes add the min and max of the range to the row via reference
     * @param array $row
     * @return boolean
     */
    protected function isFuzzyRange(array &$row): bool {
        foreach(self::FUZZY_RANGES as $min => $max) {
            if($min <= $row['matchRate'] && $row['matchRate'] <= $max) {
                $row['min'] = $min;
                $row['max'] = $max;
                return true;
            }
        }
        return false;
    }
    
    /**
     * add / update a analyse node
     * @param string $name
     * @param array $row
     */
    protected function add(string $name, array $row) {
        $isFuzzy = ($name == 'fuzzy' || $name == 'internalFuzzy');
        $idx = $name;
        $min = (int) $row['min'];
        $max = (int) $row['max'];
        if($isFuzzy) {
            $idx = $name.'_'.$min.'_'.$max;
        }
        if(empty($this->analyseNodes[$idx])) {
            if($isFuzzy) {
                //this empty adding of min/max here is only because of the sorting of the attributes, they should be at the beginning
                $node = array_fill_keys(array_merge(['min', 'max'], self::ATTRIBUTES), 0);
            }
            else {
                $node = array_fill_keys(self::ATTRIBUTES, 0);
            }
            $node['tagName'] = $name;
        }
        else {
            $node = $this->analyseNodes[$idx];
        }
        if($isFuzzy) {
            $node['min'] = $min;
            $node['max'] = $max;
        }
        //summing up the currently available values
        $node['words'] += $row['wordCount'];
        $node['segments'] += $row['segCount'];
        $this->analyseNodes[$idx] = $node;
    }
    
    protected function createBatchTotalNode() {
        $batchTotal = $this->rootNode->addChild('batchTotal');
        $analyseNode = $batchTotal->addChild('analyse');
        $addedNodes = [];
        foreach($this->analyseNodes as $nodeData) {
            $node = $analyseNode->addChild($nodeData['tagName']);
            $addedNodes[] = $nodeData['tagName'];
            unset ($nodeData['tagName']);
            foreach($nodeData as $attribute => $value) {
                $node->addAttribute($attribute, $value);
            }
        }
        
        //add missing nodes with empty values
        $missingEmptyNodes = array_diff(self::NODES, $addedNodes);
        foreach($missingEmptyNodes as $node) {
            //fuzzy tags are only added with content
            if($node == 'internalFuzzy' || $node == 'fuzzy') {
                continue;
            }
            $node = $analyseNode->addChild($node);
            foreach(self::ATTRIBUTES as $attribute) {
                $node->addAttribute($attribute, 0);
            }
        }
    }
    
    /**
     * creates and returns the XML taskInfo Node
     * @param editor_Models_Task $task
     * @return SimpleXMLElement
     */
    protected function createTaskInfo(string $taskGuid): SimpleXMLElement {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        
        $analysisAssoc = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc = $analysisAssoc->loadNewestByTaskGuid($task->getTaskGuid());
        
        $to_time = strtotime( $analysisAssoc['created']);
        
        $from_time = strtotime($analysisAssoc['finishedAt']);
        $difference = abs($to_time - $from_time); //seconds
        
        $taskInfo = $this->rootNode->addChild('taskInfo');
        $taskInfo->addAttribute('taskId', $analysisAssoc['uuid']);
        $taskInfo->addAttribute('runAt', date('Y-m-d H:i:s', strtotime( $analysisAssoc['created'])));
        $taskInfo->addAttribute('runTime', $difference. ' seconds');
        
        $project = $taskInfo->addchild('project');
        $project->addAttribute('name', $task->getTaskName());
        $project->addAttribute('number', $task->getTaskGuid());
        
        $languagesModel=ZfExtended_Factory::get('editor_Models_Languages');
        /* @var $languagesModel editor_Models_Languages */
        $languagesModel->load($task->getSourceLang());
        
        $language = $taskInfo->addChild('language');
        $language->addAttribute('lcid', $languagesModel->getLcid());
        $language->addAttribute('name', $languagesModel->getLangName());
        
        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customerData = $customer->loadByIds([$task->getCustomerId()]);
        $taskInfo->addChild('customer')->addAttribute('name', $customerData[0]['name']);
        
        return $taskInfo;
    }
    
    protected function createSettings(SimpleXMLElement $taskInfo, bool $hasInternalFuzzy) {
        $settings = $taskInfo->addChild('settings');
        
        $settings->addAttribute('reportInternalFuzzyLeverage', $hasInternalFuzzy ? 'yes' : 'no');
        $settings->addAttribute('reportLockedSegmentsSeparately', 'no');
        $settings->addAttribute('reportCrossFileRepetitions', 'yes');
        // TODO Currently the value of minimum score is hardcoded, but will be changed in the future to dynamic (with TRANSLATE-2076)
        $settings->addAttribute('minimumMatchScore', '50');
        $settings->addAttribute('searchMode', 'bestWins');
        $settings->addAttribute('missingFormattingPenalty', 'n/a');
        $settings->addAttribute('differentFormattingPenalty', 'n/a');
        $settings->addAttribute('multipleTranslationsPenalty', 'n/a');
        $settings->addAttribute('autoLocalizationPenalty', 'n/a');
        $settings->addAttribute('textReplacementPenalty', 'n/a');
        $settings->addAttribute('fullRecallMatchedWords', 'n/a');
        $settings->addAttribute('partialRecallMatchedWords', 'n/a');
        $settings->addAttribute('fullRecallSignificantWords', 'n/a');
        $settings->addAttribute('partialRecallSignificantWords', 'n/a');
    }
    
    /**
     * creates the XML root node
     * @return SimpleXMLElement
     */
    protected function createRootNode() {
        $this->rootNode = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><task name="analyse"></task><!--
This file was generated with translate5.
- there are no file elements added, since translate5 does not support a file specific analysis right now
- for the settings element the following values are set:
  reportInternalFuzzyLeverage="AS SET IN Translate5"
  reportLockedSegmentsSeparately="no"
  reportCrossFileRepetitions="yes"
  minimumMatchScore="LOWEST FUZZY RANGE AS USED IN Translate5 analysis"
  searchMode="bestWins"
  all other settings are not available and marked as n/a
- In the batchTotal analysis section the following applies:
  For the following elements all numeric attributes are always set to "0",
  because they currently have no analogon in translate5:
    # locked
    # perfect
    # repeated (translate5 will only have crossFileRepeated)
    # newBaseline (this is specific to SDL MT)
    # newLearnings (this is specific to SDL MT)
- the number and definitions of fuzzy elements will reflect the fuzzy ranges as defined in translate5
- all MT matches will always be counted within "new"
- crossFileRepeated are translate5s repetitions (which are represented by 102% matches)
- exact are 100% and 101% and 104%-Matches from translate5
- inContextExact are 103%-Matches from translate5
- The following attributes will always have the value "0", since translate5 does not support them right now:
  # characters="0"
  # placeables="0"
  # tags="0"
  # repairWords="0"
  # fullRecallWords="0"
  # partialRecallWords="0"
  # edits="0"
  # adaptiveWords="0"
  # baselineWords="0"
-->');
    }
}

