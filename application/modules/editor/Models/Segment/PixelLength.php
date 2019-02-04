<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/**
 * Calculating the length of a text in a segment based on pixelMapping.
*/
class editor_Models_Segment_PixelLength {
    
    /**
     * In xlf, the default size-unit of a trans-unit is 'pixel' if size-unit is not set.
     * - http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#size-unit
     * - http://docs.oasis-open.org/xliff/v1.2/os/xliff-core.html#maxwidth
     * @var string
     */
    const SIZE_UNIT_XLF_DEFAULT = 'pixel';
    
    /**
     * Size-unit used for pixel-mapping.
     * @var string
     */
    const SIZE_UNIT_FOR_PIXELMAPPING = 'pixel';
    
    /**
     * Default pixel-widths for font_sizes from ZfConfig
     * @var array
     */
    protected $defaultPixelWidths;
    
    /**
     * @var editor_Models_Segment
     */
    protected $segment;
    
    /**
     * This class must be linked to a task, otherwise we might use wrong data!
     * @var string
     */
    protected $taskGuid;
    
    /**
     * PixelMapping in the DB is stored according to customers.
     * @var integer
     */
    protected $customerId;
    
    /**
     * @var editor_Models_PixelMapping
     */
    protected $pixelMapping;
    
    /**
     * PixelMapping for current task
     * @var array
     */
    protected $pixelMappingForTask;
    
    public function __construct(string $taskGuid)
    {
        $this->segment = ZfExtended_Factory::get('editor_Models_Segment');
        
        $this->taskGuid = $taskGuid;
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $this->customerId = intval($task->getCustomerId());
        $this->pixelMapping = ZfExtended_Factory::get('editor_Models_PixelMapping');
        /* @var $pixelMapping editor_Models_PixelMapping */
        $this->pixelMappingForTask = $this->pixelMapping->getPixelMappingForTask(intval($task->getCustomerId()), $task->getAllFontsInTask());
    }
    
    /**
     * Return the taskGuid the pixelLength is currently attached to.
     * @return string
     */
    public function getTaskGuid() {
        return $this->taskGuid;
    }
    
    /**
     * Return the pixelMapping for a specific segment as already loaded for the task
     * (= the item from the array with all fonts for the task that matches the segment's
     * font-family and font-size; don't start db-selects again!).
     * @param string $fontFamily
     * @param int $fontSize
     * @return array
     */
    public function getPixelMappingForSegment(string $fontFamily, int $fontSize) {
        $pixelMappingForTask = $this->pixelMappingForTask;
        $fontFamily = strtolower($fontFamily);
        if (!isset($pixelMappingForTask[$fontFamily][$fontSize])) {
            // eg on import, the task's font as set in the segment's are unknown (= the segments don't exist yet).
            // In this case we check the pixelMapping for the missing combination of font-family and font-size and add it (if found).
            $this->addPixelMappingForFont($fontFamily, $fontSize);
            $pixelMappingForTask = $this->pixelMappingForTask;
        }
        return isset($pixelMappingForTask[$fontFamily][$fontSize]) ? $pixelMappingForTask[$fontFamily][$fontSize] : [];
    }
    
    /**
     * Add the pixelMapping for a specific font to the pixelMappingForTask.
     * @param string $fontFamily
     * @param int $fontSize
     */
    protected function addPixelMappingForFont(string $fontFamily, int $fontSize) {
        $fontFamily = strtolower($fontFamily);
        // If there is anything set in the database, add it:
        $pixelMappingForFont = $this->pixelMapping->getPixelMappingByFont($this->customerId, $fontFamily, $fontSize);
        if (!empty($pixelMappingForFont)) {
            $this->pixelMappingForTask[$fontFamily][$fontSize] = $pixelMappingForFont;
        }
        // If a default value is set (and not already set for this font-size), add it, too:
        if (!array_key_exists('default', $this->pixelMappingForTask[$fontFamily][$fontSize])) {
            $defaultPixelWidthForFont = $this->pixelMapping->getDefaultPixelWidth($fontSize);
            if (!empty($defaultPixelWidthForFont)) {
                $this->pixelMappingForTask[$fontFamily][$fontSize]['default'] = $defaultPixelWidthForFont;
            }
        }
        /*
         [verdana] => Array
                    (
                        [13] => Array
                            (
                                [1593] => 12
                                [default] => 4
                             )
                     )
         */
    }
    
    /**
     * What's the length of a segment's content according to the pixelMapping?
     * @param string $segmentContent
     * @param string $fontFamily
     * @param int $fontSize
     * @return integer
     */
    public function textLengthByPixel ($segmentContent, $fontFamily, $fontSize) {
        $pixelLength = 0;
        $fontFamily = strtolower($fontFamily);
        $pixelMappingForSegment = $this->getPixelMappingForSegment($fontFamily, $fontSize);
        $charsNotSet = array();
        $charsNotSetMsg = '';
        
        // prepare string for counting
        $segmentContent = $this->segment->prepareForPixelBasedLengthCount($segmentContent);
        
        // get length for string by adding each character's length
        $allCharsInSegment = $this->segmentContentAsCharacters($segmentContent);
        foreach ($allCharsInSegment as $key => $char) {
            $charWidth = null;
            $unicodeCharNumeric = $this->getNumericValueOfUnicodeChar($char);
            if (array_key_exists($unicodeCharNumeric, $pixelMappingForSegment)) {
                $charWidth = $pixelMappingForSegment[$unicodeCharNumeric];
            } else {
                if (array_key_exists('default', $pixelMappingForSegment)) {
                    $charWidth = $pixelMappingForSegment['default'];
                }
                if (!in_array($char, $charsNotSet)) {
                    $charsNotSet[] = $char;
                    $charsNotSetMsg .= '- ' . $unicodeCharNumeric . ' (' . $char. ')'."\n";
                }
            }
            
            if (is_null($charWidth)) {
                $msg = 'textlength by pixel failed; most probably data about the pixelWidth is missing';
                $msg .= ' ('. $fontFamily. ', font-size ' . $fontSize . ')';
                throw new ZfExtended_Exception($msg);
            }
            
            $pixelLength += $charWidth;
            //error_log('[' . $key . '] ' . $char . ' ('. $unicodeCharNumeric . '): '.$charWidth. ') => length now: ' . $pixelLength);
        }
        
        if (!empty($charsNotSet)) {
            sort($charsNotSet);
            $customer = ZfExtended_Factory::get('editor_Models_Customer');
            /* @var $customer editor_Models_Customer */
            $customer->load($this->customerId);
            
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $logMsg = 'No pixel-width set for ('.$fontFamily . ', font-size: '. $fontSize .', Customer: ' . $customer.')' . "\n";
            $logMsg .= 'Default width '.$pixelMappingForSegment['default'].'px for character used. Affected characters: '."\n".$charsNotSetMsg;
            $log->log('Segment length calculation: missing pixel width', $logMsg);
        }
        
        return $pixelLength;
    }
    
    // ---------------------------------------------------------------------------------------
    // Unicode-Helpers
    // ---------------------------------------------------------------------------------------
    
    /**
     * Returns an array with the single (unicode-)characters of the given string.
     * @param string $string
     * @return string
     */
    protected function segmentContentAsCharacters (string $string) {
        $array = [];
        // Break-up a multibyte string into its individual characters.
        // http://php.net/manual/en/function.mb-split.php#80046
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string,0,1,"UTF-8");
            $string = mb_substr($string,1,$strlen,"UTF-8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }
    
    /**
     * Returns the numeric value of the given unicode-character.
     * @param string $char
     * @return mixed
     */
    protected function getNumericValueOfUnicodeChar($char) {
        // PHP >= 7.2.0
        if (version_compare(PHP_VERSION, '7.2.0') >= 0) {
            return mb_ord($char, "utf8");
        }
        // PHP 5 etc.
        return unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1]; // https://stackoverflow.com/a/27444149
    }
    
    /*
     * ---------------------------------------------------------------------
     * |     UNICODE-TABLE     |                RESULTS             | $char 
     * ---------------------------------------------------------------------
     * | DECIMAL   HEXADECIMAL |  [1]      [2]       [3]      [4]   |       
     * | NUMERIC   CODE POINT  |                                    |
     * ---------------------------------------------------------------------
     * |    &#80;      U+0050  |   80  |   &#80;  |    80  |        |  P
     * |  &#1593;      U+0639  | 1593  | &#1593;  |  1593  |        |  ع
     * | &#12103;      U+2F47  | 12103 | &#12103; | 12103  |  12103 |  ⽇
     * ---------------------------------------------------------------------
     * 
     * UNICODE-TABLE
     * - https://www.utf8-zeichentabelle.de/unicode-utf8-table.pl
     *   (see also: http://unicode.scarfboy.com/?s=U%2B0639)
     * 
     * RESULTS:
     * [1] https://stackoverflow.com/a/9361531:
     *     getUniord($char)
     * [2] http://php.net/manual/de/function.mb-encode-numericentity.php#88586:
     *     mb_encode_numericentity ($char, array (0x0, 0xffff, 0, 0xffff), 'UTF-8')
     * [3] https://stackoverflow.com/a/27444149
     *     unpack('V', iconv('UTF-8', 'UCS-4LE', $char))[1]
     * [4] https://stackoverflow.com/a/49097906
     *     mb_ord("⽇", "utf8"); // 12103
     * 
     * MORE INFOS:
     * "The Absolute Minimum Every Software Developer Absolutely, Positively Must Know About Unicode and Character Sets (No Excuses!)":
     * https://www.joelonsoftware.com/2003/10/08/the-absolute-minimum-every-software-developer-absolutely-positively-must-know-about-unicode-and-character-sets-no-excuses/
     * 
     *
     */
}
