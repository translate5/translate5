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

class editor_Plugins_MatchAnalysis_Export_ExportXml extends ZfExtended_Models_Entity_Abstract
{

    protected $dbInstanceClass = 'editor_Plugins_MatchAnalysis_Models_Db_BatchResult';
    public static $ATTRIBUTES = [
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

    public static $NODES = [
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
        'fuzzy'
    ];
    public static $FUZZY_RANGES = [
        '50' => '59',
        '60' => '69',
        '70' => '79',
        '80' => '89',
        '90' => '99'
    ];

    /**
     * @param SimpleXMLElement $object
     * @param $data
     * @return SimpleXMLElement
     */
    public static function addFuzzyChilds(SimpleXMLElement $object, $data, $childName = 'fuzzy'): SimpleXMLElement
    {
        foreach (self::$FUZZY_RANGES as $min => $max) {
            $child = $object->batchTotal->analyse->addChild($childName);
            $child->addAttribute('min', $min);
            $child->addAttribute('max', $max);
            foreach (self::$ATTRIBUTES as $attr) {
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
    public static function addAttributes(SimpleXMLElement $child, $data): SimpleXMLElement
    {
        foreach (self::$ATTRIBUTES as $attr) {
            $child->addAttribute($attr, '0');
            if ($attr == 'words' && $child->getName() == 'fuzzy') {
                $attrib = (array)$child->attributes()->max;

                $child->addAttribute($attr, $data[$attrib[0]]);
            }
        }

        return $child;
    }

    /**
     * @param SimpleXMLElement $object
     * @param $data
     * @return SimpleXMLElement
     */
    public static function generateNodesWithAttributes(SimpleXMLElement $object, $data): SimpleXMLElement
    {

        foreach (self::$NODES as $node) {
            if ($node == 'fuzzy') {
                $object = self::addFuzzyChilds($object, $data);
            } else {
                $child = $object->batchTotal->analyse->addChild($node);
                foreach (self::$ATTRIBUTES as $attr) {

                    if ($attr == 'words' && $child->getName() == 'crossFileRepeated') {
                        //crossFileRepeated are translate5s repetitions (which are represented by 102% matches)
                        $child->addAttribute($attr, $data['102']);
                    }elseif ($attr == 'words' && $child->getName() == 'inContextExact'){
                        //inContextExact are 103%-Matches from translate5
                        $child->addAttribute($attr, $data['103']);
                    }elseif ($attr == 'words' && $child->getName() == 'exact'){
                        //exact are 100% and 101% and 104%-Matches from translate5, since Trados does not know our 101 and 104%-Matches
                        $value = $data['100'] + $data['101'] + $data['104'];
                        $child->addAttribute($attr, $value);
                    }else{
                        $child->addAttribute($attr, '0');
                    }
                }
            }
        }
        return $object;
    }

    /**
     * @param $rows
     * @return SimpleXMLElement
     * @throws Exception
     */

    public static function generateXML($rows, $taskGuid): SimpleXMLElement
    {

        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);

        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        $customerData = $customer->loadByIds([$task->getCustomerId()]);
        $languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        $assocs = $languageResource->loadByAssociatedTaskGuid($task->getTaskGuid());

        $renderData = [
            'resourceName' => '',
            'resourceColor' => '',
            'created' => '',
            'internalFuzzy' => 'No',
            '104' => 0,
            '103' => 0,
            '102' => 0,
            '101' => 0,
            '100' => 0,
            '99' => 0,
            '89' => 0,
            '79' => 0,
            '69' => 0,
            '59' => 0,
            'noMatch' => 0,
            'wordCount' => 0,
            'type' => ''
        ];

        foreach ($rows as $row) {
            $renderData['resourceName'] .= $row['resourceName'].', ';
            if($row['internalFuzzy'] == 'Yes'){
                $renderData['internalFuzzy'] = 'Yes';
            }
            $renderData['104'] += $row['104'];
            $renderData['103'] += $row['103'];
            $renderData['102'] += $row['102'];
            $renderData['101'] += $row['101'];
            $renderData['100'] += $row['100'];
            $renderData['99'] += $row['99'];
            $renderData['89'] += $row['89'];
            $renderData['79'] += $row['79'];
            $renderData['69'] += $row['69'];
            $renderData['59'] += $row['59'];
            $renderData['wordCount'] += $row['wordCount'];

        }


        $analysisAssoc = ZfExtended_Factory::get('editor_Plugins_MatchAnalysis_Models_TaskAssoc');
        /* @var $analysisAssoc editor_Plugins_MatchAnalysis_Models_TaskAssoc */
        $analysisAssoc = $analysisAssoc->loadNewestByTaskGuid($taskGuid);
        $to_time = strtotime( $task->getOrderdate());

        $from_time = strtotime($analysisAssoc['finishedAt']);
        $difference = round(abs($to_time - $from_time) / 3600,2); //seconds



        $xml_header = '<?xml version="1.0" encoding="UTF-8"?><task name="analyse"></task>';
        $xml = new SimpleXMLElement($xml_header);

        $subnode1 = $xml->addChild('taskInfo');
        $subnode1->addAttribute('taskId', $taskGuid);
        $subnode1->addAttribute('runAt', $task->getOrderdate());
        $subnode1->addAttribute('runTime', $difference. ' seconds');

        $subnode2 = $subnode1->addchild('project');
        $subnode2->addAttribute('name', $task->getTaskName());
        $subnode2->addAttribute('number', $task->getId());

        $innerNode1 = $subnode1->addChild('language');
        $innerNode1->addAttribute('lcid', '1.2.1');
        $innerNode1->addAttribute('name', 'English');

        $innerNode2 = $subnode1->addChild('customer');
        $innerNode2->addAttribute('name', $customerData[0]['name']);

        $inner_node3 = $subnode1->addChild('tm');
        $inner_node3->addAttribute('name', $renderData['resourceName']);

        $internalFuzzy = $renderData['internalFuzzy'];

        $innerNode4 = $subnode1->addChild('settings');
        $innerNode4->addAttribute('reportInternalFuzzyLeverage', $internalFuzzy);
        $innerNode4->addAttribute('reportLockedSegmentsSeparately', 'no');
        $innerNode4->addAttribute('reportCrossFileRepetitions', 'yes');
        $innerNode4->addAttribute('minimumMatchScore', 'lowestFuzzyValueThatIsConfiguredToBeShownInTranslate5');
        $innerNode4->addAttribute('searchMode', 'bestWins');
        $innerNode4->addAttribute('missingFormattingPenalty', '1');
        $innerNode4->addAttribute('differentFormattingPenalty', '1');
        $innerNode4->addAttribute('multipleTranslationsPenalty', '1');
        $innerNode4->addAttribute('autoLocalizationPenalty', '0');
        $innerNode4->addAttribute('textReplacementPenalty', '0');
        $innerNode4->addAttribute('fullRecallMatchedWords', '2');
        $innerNode4->addAttribute('partialRecallMatchedWords', 'n/a');
        $innerNode4->addAttribute('fullRecallSignificantWords', '2');
        $innerNode4->addAttribute('partialRecallSignificantWords', 'n/a');


        $batchTotal = $xml->addChild('batchTotal');
        $batchTotal->addChild('analyse');
        $fileName = 'report'. date('Y-m-d H:i:s').'.xml';

        header('Content-disposition: attachment; filename='.$fileName);
        header ("Content-Type:text/xml");
//output the XML data
        echo  $xml;
        // if you want to directly download then set expires time
        header("Expires: 0");

        return self::generateNodesWithAttributes($xml, $renderData);
    }
}

