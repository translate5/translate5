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
 * Class editor_Plugins_MatchAnalysis_Export_Xml
 * Export the match analyse result as XML
 */
class editor_Plugins_MatchAnalysis_Export_Xml
{
    /**
     * List of attributes of a analyse node
     * WARNING: the here listed order of the attributes is important (segments before words) otherwise plunet can not import the XML file
     * @var array
     */
    const ATTRIBUTES = [
        'segments',
        'words',
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
    protected array $fuzzyRanges = [];

    /**
     * Collection of analyse nodes to be rendered
     * @var array
     */
    protected array $analyseNodes = [];
    
    /**
     * @var SimpleXMLElement
     */
    protected SimpleXMLElement $rootNode;

    /**
     * @throws editor_Models_ConfigException
     */
    public function __construct(editor_Models_Task $task) {

        //for XML export we may
        $configuredFuzzies = $task->getConfig()->runtimeOptions->plugins->MatchAnalysis->fuzzyBoundaries;
        $this->fuzzyRanges = [];
        foreach($configuredFuzzies->toArray() as $begin => $end) {
            if((int) $begin >= 100) {
                continue;
            }
            $this->fuzzyRanges[(string) $begin] = (string) min((int)$end, 99);
        }
    }

    /**
     * adds empty fuzzy nodes
     */
    protected function addEmptyFuzzyNodes() {
        foreach($this->fuzzyRanges as $min => $max) {
            $this->add('fuzzy', ['min' => $min, 'max' => $max]);
        }
        //we use separate loops to sort all internal fuzzies below the fuzzies
        foreach($this->fuzzyRanges as $min => $max) {
            $this->add('internalFuzzy', ['min' => $min, 'max' => $max]);
        }
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
     * @param $taskGuid
     * @return SimpleXMLElement
     */
    public function generateXML($rows, $taskGuid): SimpleXMLElement
    {
        $usedLanguageResources = [];
        
        $hasInternalFuzzy = false;
        
        $this->addEmptyFuzzyNodes();
        
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
                $this->add('perfect', $row);
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
        foreach($this->fuzzyRanges as $min => $max) {
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
        $min = array_key_exists('min', $row) ? (int) $row['min'] : 0;
        $max = array_key_exists('max', $row) ? (int) $row['max'] : 0;
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
        if(array_key_exists('segCount', $row)) {
            $node['segments'] += $row['segCount'];
        }
        if(array_key_exists('wordCount', $row)) {
            $node['words'] += $row['wordCount'];
        }
        $this->analyseNodes[$idx] = $node;
    }
    
    protected function createBatchTotalNode() {
        $batchTotal = $this->rootNode->addChild('batchTotal');
        $analyseNode = $batchTotal->addChild('analyse');

        //the order of the elements is important, so we generate the desired order first.
        $orderedNodes = array_fill_keys(self::NODES, []);

        //copy now the collected data into the ordered list
        foreach($this->analyseNodes as $nodeData) {
            $tag = $nodeData['tagName'];
            unset ($nodeData['tagName']);
            $orderedNodes[$tag][] = $nodeData;
        }

        //out of the ordered list we create the XML nodes
        foreach($orderedNodes as $nodeTag => $subNodeList) {
            if(empty($subNodeList)) {
                $node = $analyseNode->addChild($nodeTag);
                foreach(self::ATTRIBUTES as $attribute) {
                    $node->addAttribute($attribute, 0);
                }
            }
            else {
                //subnode list count will only be > 1 for fuzzy and internalFuzzy, for all other there is exactly one node
                foreach($subNodeList as $subNode) {
                    $node = $analyseNode->addChild($nodeTag);
                    foreach($subNode as $attribute => $value) {
                        $node->addAttribute($attribute, $value);
                    }
                }
            }
        }
    }

    /**
     * creates and returns the XML taskInfo Node
     * @param string $taskGuid
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
        
        $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        /* @var $customer editor_Models_Customer_Customer */
        $customerData = $customer->loadByIds([$task->getCustomerId()]);
        $taskInfo->addChild('customer')->addAttribute('name', $customerData[0]['name']);
        
        return $taskInfo;
    }
    
    protected function createSettings(SimpleXMLElement $taskInfo, bool $hasInternalFuzzy) {
        $settings = $taskInfo->addChild('settings');
        
        $settings->addAttribute('reportInternalFuzzyLeverage', $hasInternalFuzzy ? 'yes' : 'no');
        $settings->addAttribute('reportLockedSegmentsSeparately', 'no');
        $settings->addAttribute('reportCrossFileRepetitions', 'yes');
        $settings->addAttribute('minimumMatchScore', (string) min(array_keys($this->fuzzyRanges)));
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
    # inContextExact
    # repeated (translate5 will only have crossFileRepeated)
    # newBaseline (this is specific to SDL MT)
    # newLearnings (this is specific to SDL MT)
- the number and definitions of fuzzy elements will reflect the fuzzy ranges as defined in translate5
- all MT matches and matches not listed as fuzzy or better match will always be counted within "new"
- crossFileRepeated are translate5s repetitions (which are represented by 102% matches)
- exact are 100% and 101% and 104%-Matches from translate5
- perfect are 103%-Matches from translate5
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

