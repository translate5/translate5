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
            $child = self::addAttributes($child, $data);
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
                $child = self::addAttributes($child, $data);
            }
        }
        return $object;
    }

    /**
     * @param $rows
     * @return SimpleXMLElement
     * @throws Exception
     */

    public static function generateXML($rows): SimpleXMLElement
    {

        $xml_header = '<?xml version="1.0" encoding="UTF-8"?><task name="analyse"></task>';
        $xml = new SimpleXMLElement($xml_header);

        $subnode1 = $xml->addChild('taskInfo');
        $subnode1->addAttribute('taskId', '80094e22-25aa-4755-b8e3-7882535db225');
        $subnode1->addAttribute('runAt', date('Y-m-d H:i:s'));
        $subnode1->addAttribute('runTime', 'Less than 1 second');

        $subnode2 = $subnode1->addchild('project');
        $subnode2->addAttribute('name', 'P1579');
        $subnode2->addAttribute('number', '1.2');

        $inner_node1 = $subnode1->addChild('language');
        $inner_node1->addAttribute('lcid', '1.2.1');
        $inner_node1->addAttribute('name', 'English');

        $inner_node2 = $subnode1->addChild('customer');
        $inner_node2->addAttribute('name', '--');

        $inner_node3 = $subnode1->addChild('tm');
        $inner_node3->addAttribute('name', 'Q-Trials_IT_EN.sdltm');


        $inner_node4 = $subnode1->addChild('settings');
        $inner_node4->addAttribute('reportInternalFuzzyLeverage', 'translate5Value');
        $inner_node4->addAttribute('reportLockedSegmentsSeparately', 'no');
        $inner_node4->addAttribute('reportCrossFileRepetitions', 'yes');
        $inner_node4->addAttribute('minimumMatchScore', 'lowestFuzzyValueThatIsConfiguredToBeShownInTranslate5');
        $inner_node4->addAttribute('searchMode', 'bestWins');
        $inner_node4->addAttribute('missingFormattingPenalty', '1');
        $inner_node4->addAttribute('differentFormattingPenalty', '1');
        $inner_node4->addAttribute('multipleTranslationsPenalty', '1');
        $inner_node4->addAttribute('autoLocalizationPenalty', '0');
        $inner_node4->addAttribute('textReplacementPenalty', '0');
        $inner_node4->addAttribute('fullRecallMatchedWords', '2');
        $inner_node4->addAttribute('partialRecallMatchedWords', 'n/a');
        $inner_node4->addAttribute('fullRecallSignificantWords', '2');
        $inner_node4->addAttribute('partialRecallSignificantWords', 'n/a');


        $batchTotal = $xml->addChild('batchTotal');
        $batchTotal->addChild('analyse');
        return self::generateNodesWithAttributes($xml, $rows);
    }
}

