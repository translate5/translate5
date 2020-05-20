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
     * Insert or update PixelMapping for Unicode-Character as given in pixel-mapping.xlsx or in xlf-file.
     * @param array $values
     */
    public function insertPixelMappingRow($values) {
        $sql= 'INSERT INTO LEK_pixel_mapping (`taskGuid`,`fileId`,`font`,`fontsize`,`unicodeChar`,`pixelWidth`)
               VALUES (?,?,?,?,?,?)
               ON DUPLICATE KEY UPDATE `taskGuid` = ?,`fileId` = ?,`font` = ?,`fontsize` = ?,`unicodeChar` = ?,`pixelWidth` = ?';
        $values['font'] = strtolower($values['font']);
        $bindings = array($values['taskGuid'], $values['fileId'], $values['font'], $values['fontsize'], $values['unicodeChar'], $values['pixelWidth'],
                          $values['taskGuid'], $values['fileId'], $values['font'], $values['fontsize'], $values['unicodeChar'], $values['pixelWidth']);
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
        $pixelMappingForFont = array();
        $sql = $this->db->select()
        ->from($this->db, array('unicodeChar','pixelWidth'))
        ->where('taskGuid = ?', $taskGuid)
        ->where('font LIKE ?', $fontFamily)
        ->where('fontSize = ?', $fontSize);
        // TODO handle files!
        $allPixelMappingRows = $this->db->fetchAll($sql);
        foreach ($allPixelMappingRows->toArray() as $row) {
            $pixelMappingForFont[$row['unicodeChar']] = $row['pixelWidth'];
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
            $pixelMappingForTask[$fontFamily][$fontSize] = $this->getPixelMappingByFont($taskGuid, $fontFamily, $fontSize);
            $pixelMappingForTask[$fontFamily][$fontSize]['default'] = $this->getDefaultPixelWidth($fontSize);
        }
        // TODO handle files!
        return $pixelMappingForTask;
        /*
         Array
            (
                [verdana] => Array
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
}
