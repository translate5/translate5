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
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method void getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(void $taskGuid)
 * @method integer getFileId() getFileId()
 * @method void setFileId() setFileId(int $fileId)
 * @method string getFont() getFont()
 * @method void setFont() setFont(string $font)
 * @method integer getFontsize() getFontsize()
 * @method void setFontsize() setFontsize(int $fontsize)
 * @method string getUnicodeChar() getUnicodeChar()
 * @method void setUnicodeChar() setUnicodeChar(string $unicodeChar)
 * @method integer getPixelWidth() getPixelWidth()
 * @method void setPixelWidth() setPixelWidth(int $pixelWidth)
*/
class editor_Models_PixelMapping extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_PixelMapping';
    protected $validatorInstanceClass   = 'editor_Models_Validator_PixelMapping';
    
    /**
     * Insert or update PixelMapping for Unicode-Character as given in pixel-mapping.xlsx or in import file
     *
     * @param string $taskGuid
     * @param int $fileId
     * @param string $font
     * @param int $fontSize
     * @param string $char
     * @param int $pixelWidth
     */
    public function insertPixelMappingRow(string $taskGuid, ?int $fileId, string $font, int $fontSize, string $char, int $pixelWidth) {
        $sql= 'INSERT INTO LEK_pixel_mapping (`taskGuid`,`fileId`,`font`,`fontsize`,`unicodeChar`,`pixelWidth`)
               VALUES (?,?,?,?,?,?)
               ON DUPLICATE KEY UPDATE `taskGuid` = ?,`fileId` = ?,`font` = ?,`fontsize` = ?,`unicodeChar` = ?,`pixelWidth` = ?';
        $font = strtolower($font);
        $bindings = [
            $taskGuid, $fileId, $font, $fontSize, $char, $pixelWidth,
            $taskGuid, $fileId, $font, $fontSize, $char, $pixelWidth
        ];
        try {
            $this->db->getAdapter()->query($sql, $bindings);
            return;
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e);
        }
    }
    
    /**
     * Return the default pixel-width as set in the Zf-config.
     * @param int $fontSize
     */
    public function getDefaultPixelWidth($fontSize) {
        if (!isset($this->defaultPixelWidths)) {
            $config = Zend_Registry::get('config');
            $pixelMapping = $config->runtimeOptions->lengthRestriction->pixelMapping ?? null;
            $this->defaultPixelWidths = (is_null($pixelMapping)) ?  [] : $pixelMapping->toArray();
        }
        if (array_key_exists($fontSize, $this->defaultPixelWidths)) {
            return $this->defaultPixelWidths[$fontSize];
        }
        throw new editor_Models_Import_MetaData_Exception('E1054',[
            'fontSize' => $fontSize
        ]);
    }
    
    /**
     * Returns the pixelMapping-data from the database by taskGuid, font-family and fontSize.
     * [unicodeChar] => length
     * @param string $taskGuid
     * @param string $fontFamily
     * @param int $fontSize
     * @return array
     */
    public function getPixelMappingByFont(string $taskGuid, string $fontFamily, int $fontSize) {
        $pixelMappingForFont = [];
        $sql = $this->db->select()
        ->from($this->db, array('fileId','unicodeChar','pixelWidth'))
        ->where('taskGuid = ?', $taskGuid)
        ->where('font LIKE ?', $fontFamily)
        ->where('fontSize = ?', $fontSize);
        $allPixelMappingRows = $this->db->fetchAll($sql);
        foreach ($allPixelMappingRows->toArray() as $row) {
            $fileId = $row['fileId'] ?? 'default';
            $pixelMappingForFont[$row['unicodeChar']][$fileId] = $row['pixelWidth'];
        }
        return $pixelMappingForFont;
    }
    
    /**
     * Return all pixelMapping-data as set for all fonts used in a task considering different files if given.
     * @param string $taskGuid
     * @param array $allFontsInTask
     * @return array
     */
    public function getPixelMappingForTask(string $taskGuid, array $allFontsInTask) {
        $pixelMappingForTask = array();
        foreach ($allFontsInTask as $font) {
            $fontFamily = strtolower($font['font']);
            $fontSize = intval($font['fontSize']);
            $this->addPixelMappingForFont($taskGuid, $pixelMappingForTask, $fontFamily, $fontSize);
        }
        return $pixelMappingForTask;
        /*
         Array
            (
                [arial] => Array
                    (
                        [13] => Array
                            (
                                [80] => Array
                                    (
                                        [default] => 11  // = no fileId given => from pixel-mapping.xls
                                        [918] => 18      // = as set in import-file with fileId 918
                                    )
                                [83] => Array
                                    (
                                        [default] => 11
                                    )
            
                                [default] => 12
                            )
            
                    )
            )
         */
    }
    
    /**
     * Add the pixelMapping for a specific font to the given pixelMappingForTask.
     * @param string $taskGuid
     * @param array $pixelMappingForTask
     * @param string $fontFamily
     * @param int $fontSize
     * @return array $pixelMappingForTask
     */
    protected function addPixelMappingForFont(string $taskGuid, array &$pixelMappingForTask, string $fontFamily, int $fontSize) {
        $fontFamily = strtolower($fontFamily);
        settype($pixelMappingForTask[$fontFamily], 'array');
        settype($pixelMappingForTask[$fontFamily][$fontSize], 'array');
        // If there is anything set in the database, add it:
        $pixelMappingForFont = $this->getPixelMappingByFont($taskGuid, $fontFamily, $fontSize);
        if (!empty($pixelMappingForFont)) {
            $pixelMappingForTask[$fontFamily][$fontSize] = $pixelMappingForFont;
        }
        // If a default value is set (and not already set for this font-size), add it, too:
        if (!array_key_exists('default',$pixelMappingForTask[$fontFamily][$fontSize])) {
            $pixelMappingForTask[$fontFamily][$fontSize]['default'] = $this->getDefaultPixelWidth($fontSize);
        }
    }
    
    /**
     * Return the pixelMapping for the font and font-size as already loaded for the task
     * (= the item from the array with all fonts for the task that matches the segment's
     * font-family and font-size; don't start db-selects again!).
     * @param string $taskGuid
     * @param array $pixelMappingForTask
     * @param string $fontFamily
     * @param int $fontSize
     * @return array
     */
    public function getPixelMappingForFontAndSize($taskGuid, array &$pixelMappingForTask, string $fontFamily, int $fontSize) {
        $fontFamily = strtolower($fontFamily);
        if (!isset($pixelMappingForTask[$fontFamily][$fontSize])) {
            // eg on import, the task's font as set in the segment's are unknown (= the segments don't exist yet).
            // In this case we check the pixelMapping for the missing combination of font-family and font-size and add it (if found).
            $this->addPixelMappingForFont($taskGuid, $pixelMappingForTask, $fontFamily, $fontSize);
        }
        return $pixelMappingForTask[$fontFamily][$fontSize] ?? [];
    }
    
    /**
     * Returns the width to be used for the character according to the given pixel-mapping-data.
     * If a character has no specific pixel-mapping set and the general default must be used,
     * it gets added to the list of characters that are not set.
     * @param string $char
     * @param array $pixelMappingForSegment
     * @param int $fileId
     * @param array $charsNotSet
     * @return int|NULL
     */
    public function getCharWidth ($char, $pixelMappingForSegment, $fileId, &$charsNotSet): ?int {
        $unicodeCharNumeric = $this->getNumericValueOfUnicodeChar($char);
        if (array_key_exists($unicodeCharNumeric, $pixelMappingForSegment)) {
            $pixelMappingForCharacter = $pixelMappingForSegment[$unicodeCharNumeric];
            if (array_key_exists($fileId, $pixelMappingForCharacter)) {
                return $pixelMappingForCharacter[$fileId];
            }
            if (array_key_exists('default', $pixelMappingForCharacter)) {
                return $pixelMappingForCharacter['default'];
            }
        }
        $charsNotSet[] = $unicodeCharNumeric . ' (' . $char. ')';
        
        if (array_key_exists('default', $pixelMappingForSegment)) {
            $default = $pixelMappingForSegment['default'];
            if(is_array($default) && array_key_exists($fileId, $default)) {
                return $default[$fileId];
            }
            return $default;
        }
        return null;
    }
    
    // ---------------------------------------------------------------------------------------
    // Unicode-Helpers
    // ---------------------------------------------------------------------------------------
    
    /**
     * Returns the numeric value of the given unicode-character.
     * @param string $char
     * @return mixed
     */
    public function getNumericValueOfUnicodeChar($char) {
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
