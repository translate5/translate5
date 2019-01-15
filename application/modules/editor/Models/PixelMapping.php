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
 * @method string getMappingId() getMappingId()
 * @method void setMappingId() setMappingId(string $mappingId)
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
     * insert or update PixelMapping for Unicode-Character as given in pixel-mapping.xlsx
     * (order of columns must not be changed; see Confluence!).
     * @param array $values
     */
    public function importPixelMappingRow($values) {
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
     * Returns the pixelWidth-data according to customer, font(-family) and fontSize.
     * [unicodeChar] => length
     * @return array
     */
    public function getPixelWidthData($customerId, $font, $fontSize) {
        // AT WORK
    }
}
