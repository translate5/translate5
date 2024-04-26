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

use editor_Models_Segment_InternalTag as InternalTag;
use MittagQI\Translate5\ContentProtection\ContentProtector;
use MittagQI\Translate5\ContentProtection\NumberProtector;
use MittagQI\Translate5\Segment\Tag\Placeable;

/**
 * Count the word in segment text
 */
class editor_Models_Segment_WordCount
{
    private const INTERNAL_TAGS_NOT_TO_COUNT = [NumberProtector::TAG_NAME, Placeable::MARKER_CLASS, 'char'];

    /**
     * Segment entity
     * @var editor_Models_Segment
     */
    protected $segment;

    /**
     * List of non-word characters (empty spaces)
     * This list is the practical implementation of the https://www.xtm.cloud/manuals/gmx-v/GMX-V-2.0.html#Words standard.
     * @var array
     */
    protected $whiteSpaceChars = [
        '00000009',
        '0000000A',
        '0000000B',
        '0000000C',
        '0000000D',
        '00000020',
        '00000085',
        '000000A0',
        '00001680',
        '00002000',
        '00002001',
        '00002002',
        '00002003',
        '00002004',
        '00002005',
        '00002006',
        '00002007',
        '00002008',
        '00002009',
        '0000200A',
        '0000200D',
        '00002028',
        '00002029',
        '0000202F',
        '0000205F',
        '00003000',
        '0000feff',
    ];

    /**
     * Rfc language code for the segment
     * @var string
     */
    protected $rfcLanguage;

    /**
     * The segment query string
     * @var string
     */
    protected $queryString;

    /**
     * Dummy connector used to get the qeury string
     * @var editor_Services_OpenTM2_Connector
     */
    protected $connector;

    /**
     * Regex used for word break
     * @var string
     */
    protected $regexWordBreak;

    protected ContentProtector $contentProtector;

    protected editor_Models_Segment_UtilityBroker $utilityBroker;

    public function __construct($rfcLanguage = "")
    {
        $this->rfcLanguage = $rfcLanguage;
        $this->utilityBroker = ZfExtended_Factory::get(editor_Models_Segment_UtilityBroker::class);
        $this->connector = ZfExtended_Factory::get(editor_Services_OpenTM2_Connector::class);
        $config = Zend_Registry::get('config');
        $this->regexWordBreak = $config->runtimeOptions->editor->export->wordBreakUpRegex;
        $this->contentProtector = ContentProtector::create($this->utilityBroker->whitespace);
    }

    /**
     * Approximate word count based on averages of characters per word (East Asian languages by language)
     *
     * @param string $text
     * @param number $average
     * @return number
     */
    protected function getGraphemeCount($text, $average)
    {
        $count = grapheme_strlen($text);

        return round($count / $average);
    }

    /**
     * Return number of words in text
     * @param string $text
     * @return number
     */
    protected function getWordsCount($text)
    {
        //replace 'hyphen' and 'apostrophe' characters with on one side bordering the segment and on the other whitespace with whitespace
        $search = [
            '/^\s*‐\s+/s',
            '/\s+‐\s*$/s',

            '/^\s*\'\s+/s',
            '/\s+\'\s*$/s',

            '/^\s*-\s+/s',
            '/\s+-\s*$/s',

            '/^\s*‐\s+/s',
            '/\s+‐\s*$/s',

            '/^\s*֊\s+/s',
            '/\s+֊\s*$/s',

            '/^\s*゠\s+/s',
            '/\s+゠\s*$/s',

            //replace 'hyphen' and 'apostrophe' characters with surounding whitespace with whitespace
            '/\s+‐\s+/s',
            '/\s+\'\s+/s',
            '/\s+-\s+/s',
            '/\s+‐\s+/s',
            '/\s+֊\s+/s',
            '/\s+゠\s+/s',

            //remove decimal and thousands chars from numbers
            '/(\d+)\.(\d+)/s',
            '/(\d+),(\d+)/s',
        ];
        $replace = [
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            " ",
            "\\1\\2",
            "\\1\\2",
        ];
        $text = preg_replace($search, $replace, $text);

        //replace 'hyphen' and 'apostrophe' characters with underscore
        $search = ["‐", "'", "-", "‐", "֊", "゠"];
        $text = str_replace($search, "_", $text);

        //replace html entities with each real chars
        $text = html_entity_decode($text, ENT_HTML5);
        $words = preg_split($this->regexWordBreak, $text, flags: PREG_SPLIT_NO_EMPTY);

        return count($words);
    }

    public function setSegment(editor_Models_Segment $segment)
    {
        $this->segment = $segment;
        $this->queryString = $this->connector->getQueryString($this->segment);
    }

    /**
     * Get count of the words in source field in the segment
     * All segment tags, and punctuation will be removed before the segment words are counted.
     * For easter asian languages, the grapheme count based on average grapheme per word will be calculated
     *
     * @return number|mixed
     */
    public function getSourceCount(): float|int
    {
        $text = $this->utilityBroker->internalTag->replace($this->queryString, function ($matches) {
            if (in_array($matches[InternalTag::TAG_TYPE_MATCH_ID], self::INTERNAL_TAGS_NOT_TO_COUNT, true)) {
                return '';
            }

            return $matches[0];
        });
        $text = $this->utilityBroker->internalTag->restore($text);
        $text = $this->contentProtector->unprotect($text, true);
        $text = $this->segment->stripTags($text);
        //average words in East Asian languages by language
        //Chinese (all forms): 2.8
        //Japanese: 3.0
        //Korean: 3.3
        //Thai: 6.0
        switch (strtolower($this->rfcLanguage)) {
            case 'zh':
            case 'zh-hk':
            case 'zh-mo':
            case 'zh-sg':
            case 'zh-cn':
            case 'zh-tw':
                return $this->getGraphemeCount($text, 2.8);
            case 'th':
            case 'th-th':
                return $this->getGraphemeCount($text, 6.0);
            case 'ja':
            case 'ja-jp':
                return $this->getGraphemeCount($text, 3.0);
            case 'ko':
            case 'ko-kr':
                return $this->getGraphemeCount($text, 3.3);
            default:
                return $this->getWordsCount($text);
        }
    }
}
