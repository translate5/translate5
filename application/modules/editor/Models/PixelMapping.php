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
 * @method string getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(integer $customerId)
 * @method string getFont() getFont()
 * @method void setFont() setFont(string $font)
 * @method integer getFontsize() getFontsize()
 * @method void setFontsize() setFontsize(integer $fontsize)
 * @method string getUnicodeChar() getUnicodeChar()
 * @method void setUnicodeChar() setUnicodeChar(string $unicodeChar)
 * @method integer getPixelWidth() getPixelWidth()
 * @method void setPixelWidth() setPixelWidth(integer $pixelWidth)
*/
class editor_Models_PixelMapping extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_PixelMapping';
    protected $validatorInstanceClass   = 'editor_Models_Validator_PixelMapping';
    
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
     * Default pixel-width in general from ZfConfig
     * @var integer
     */
    protected $defaultPixelWidthGeneral;
    
    /**
     * Default pixel-widths for font_sizes from ZfConfig
     * @var array
     */
    protected $defaultPixelWidths;
    
    /**
     * insert or update PixelMapping for Unicode-Character as given in pixel-mapping.xlsx
     * (order of columns must not be changed; see Confluence!).
     * @param array $values
     */
    public function insertPixelMappingRow($values) {
        $customerId = $values[1];
        $font = $values[2];
        $fontsize = $values[3];
        $unicodeChar = $values[4];
        $pixelWidth = $values[5];
        $sql= 'INSERT INTO LEK_pixel_mapping (`customerId`,`font`,`fontsize`,`unicodeChar`,`pixelWidth`)
                                VALUES ('.$customerId.', \''. $font .'\', '.$fontsize.', \''.$unicodeChar.'\', '.$pixelWidth.')
                                ON DUPLICATE KEY UPDATE
                                    `customerId` = '. $customerId .',
                                    `font` = \''. $font .'\',
                                    `fontsize` = '. $fontsize .',
                                    `unicodeChar` = \''. $unicodeChar .'\',
                                    `pixelWidth` = '. $pixelWidth .';
                                ';
        try {
            $this->db->getAdapter()->query($sql);
        }
        catch(Zend_Db_Statement_Exception $e) {
            throw new ZfExtended_Exception('Pixel-Mapping: Import failed.');
        }
    }
    
    /**
     * Return the default pixel-width as set in the Zf-config.
     * @param int $fontSize
     */
    protected function getDefaultPixelWidth($fontSize) {
        if (!isset($this->defaultPixelWidthGeneral) || !isset($this->defaultPixelWidths)) {
            $config = Zend_Registry::get('config');
            $this->defaultPixelWidthGeneral = $config->runtimeOptions->pixelMapping->defaultPixelWidthGeneral;
            $this->defaultPixelWidths = $config->runtimeOptions->pixelMapping->defaultPixelWidths->toArray();
        }
        if (array_key_exists($fontSize, $this->defaultPixelWidths)) {
            return $this->defaultPixelWidths[$fontSize];
        }
        if (!empty($this->defaultPixelWidthGeneral)) {
            return $this->defaultPixelWidthGeneral;
            
        }
        throw new ZfExtended_NotFoundException('pixelMapping cannot continue due to missing default-values for pixel-width.');
    }
    
    /**
     * Returns the pixelMapping-data from the database by customer, font-family and fontSize.
     * [unicodeChar] => length
     * @param int $customerId
     * @param string $fontFamily
     * @param int $fontSize
     * @return array
     */
    protected function getPixelMappingByFont(int $customerId, string $fontFamily, int $fontSize) {
        $pixelMappingForFont = array();
        $sql = $this->db->select()
        ->from($this->db, array('unicodeChar','pixelWidth'))
        ->where('customerId = ?', $customerId)
        ->where('font LIKE ?', $fontFamily)
        ->where('fontSize = ?', $fontSize);
        $allPixelMappingRows = $this->db->fetchAll($sql);
        foreach ($allPixelMappingRows->toArray() as $row) {
            $pixelMappingForFont[$row['unicodeChar']] = $row['pixelWidth'];
        }
        return $pixelMappingForFont;
    }
    
    /**
     * Return all pixelMapping-data as set for the customer for all fonts used in a task.
     * @param int $customerId
     * @param array $allFontsInTask
     * @return array
     */
    public function getPixelMappingForTask(int $customerId, array $allFontsInTask) {
        $pixelMapping = array();
        foreach ($allFontsInTask as $font) {
            $fontFamily = $font['font'];
            $fontSize = $font['fontSize'];
            $pixelMapping[$fontFamily][$fontSize] = $this->getPixelMappingByFont($customerId, $fontFamily, intval($fontSize));
            $pixelMapping[$fontFamily][$fontSize]['default'] = $this->getDefaultPixelWidth( intval($fontSize));
        }
        return $pixelMapping;
        /*
         Array
            (
                [Verdana] => Array
                    (
                        [13] => Array
                            (
                                [1593] => 12
                                [default] => 4
                            )
            
                    )
            
            )
         */
    }
    
    /**
     * Return the pixelMapping for a specific segment as already loaded for the task
     * (= the item from the array with all fonts for the task that matches the segment's
     * font-family and font-size; don't start db-selects again!).
     * @param string $taskGuid
     * @param string $fontFamily
     * @param int $fontSize
     * @return array
     */
    protected function getPixelMappingForSegment(string $taskGuid, string $fontFamily, int $fontSize) {
        $task = ZfExtended_Factory::get('editor_Models_Task');
        /* @var $task editor_Models_Task */
        $task->loadByTaskGuid($taskGuid);
        $taskData = $task->getDataObject();
        $pixelMapping = $taskData->pixelMapping;
        return isset($pixelMapping[$fontFamily][$fontSize]) ? $pixelMapping[$fontFamily][$fontSize] : [];
    }
    
    /**
     * What's the length of a segment's content according to the pixelMapping?
     * @param string $segmentContent
     * @param string $taskGuid
     * @param string $fontFamily
     * @param int $fontSize
     * @return integer
     */
    public function pixelLength(string $segmentContent, string $taskGuid, string $fontFamily, int $fontSize) {
        
        $pixelLength = 0;
        $pixelMapping = $this->getPixelMappingForSegment($taskGuid, $fontFamily, $fontSize);
        $charsNotSet = array();
        $charsNotSetMsg = '';
        
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segmentContent = $segment->prepareForPixelBasedLengthCount($segmentContent);
        
        $allCharsInSegment = $this->segmentContentAsCharacters($segmentContent);
        foreach ($allCharsInSegment as $key => $char) {
            $unicodeCharNumeric = $this->getNumericValueOfUnicodeChar($char);
            if (array_key_exists($unicodeCharNumeric, $pixelMapping)) {
                $charWidth = $pixelMapping[$unicodeCharNumeric];
            } else {
                $charWidth = $pixelMapping['default'];
                if (!in_array($char, $charsNotSet)) {
                    $charsNotSet[] = $char;
                    $charsNotSetMsg .= '- ' . $unicodeCharNumeric . ' (' . $char. ')'."\n";
                }
            }
            error_log('[' . $key . '] ' . $char . ' ('. $unicodeCharNumeric . '): '.$charWidth. ') => pixelLength: ' . $pixelLength);
            $pixelLength += $charWidth;
        }
        
        if (!empty($charsNotSet)) {
            sort($charsNotSet);
            $task = ZfExtended_Factory::get('editor_Models_Task');
            /* @var $task editor_Models_Task */
            $task->loadByTaskGuid($taskGuid);
            $customerId = $task->getCustomerId();
            $log = ZfExtended_Factory::get('ZfExtended_Log');
            /* @var $log ZfExtended_Log */
            $logMsg = 'No pixel-width set for ('.$fontFamily . ', font-size '. $fontSize .', customerId ' . $customerId.'):' . "\n" . $charsNotSetMsg;
            $log->logError($logMsg);
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
