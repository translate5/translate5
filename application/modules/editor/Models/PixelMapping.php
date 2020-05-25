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
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(int $customerId)
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
     * Just an customer instance to load the missing customer IDs
     * @var editor_Models_Customer
     */
    protected $bareCustomerInstance = null;
    
    /**
     * Cache for 
     * @var array
     */
    protected $cachedCustomers = [];
    
    public function __construct() {
        parent::__construct();
        $this->bareCustomerInstance = ZfExtended_Factory::get('editor_Models_Customer');
    }
    
    /**
     * insert or update PixelMapping for Unicode-Character as given in pixel-mapping.xlsx
     * (order of columns must not be changed; see Confluence!).
     * @param array $values
     */
    public function insertPixelMappingRow($values) {
        $values = array_slice($values, 0, 5);
        $dataToBind = array_combine(['customerId', 'font', 'fontsize', 'unicodeChar', 'pixelWidth'], $values);
        $dataToBind['font'] = strtolower($dataToBind['font']);
        
        $sql= 'INSERT INTO LEK_pixel_mapping (`customerId`,`font`,`fontsize`,`unicodeChar`,`pixelWidth`)
                                VALUES (:customerId, :font, :fontsize, :unicodeChar, :pixelWidth)
                                ON DUPLICATE KEY UPDATE
                                    `customerId` = :customerId,
                                    `font` = :font,
                                    `fontsize` = :fontsize,
                                    `unicodeChar` = :unicodeChar,
                                    `pixelWidth` = :pixelWidth';
        
        try {
            //customerId is filled with the customer number first:
            $dataToBind['customerId'] = $this->getCustomerId($dataToBind['customerId']);
            
            $this->db->getAdapter()->query($sql, $dataToBind);
            return;
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->handleIntegrityConstraintException($e);
        }
    }
    
    /**
     * Gets the customer id to a customer number (in a cached way)
     * @param string $customerNumber
     */
    protected function getCustomerId($customerNumber) {
        if(empty($this->cachedCustomers[$customerNumber])) {
            $this->bareCustomerInstance->loadByNumber($customerNumber);
            $this->cachedCustomers[$customerNumber] = $this->bareCustomerInstance->getId();
        }
        return $this->cachedCustomers[$customerNumber];
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
     * Returns the pixelMapping-data from the database by customer, font-family and fontSize.
     * [unicodeChar] => length
     * @param int $customerId
     * @param string $fontFamily
     * @param int $fontSize
     * @return array
     */
    public function getPixelMappingByFont(int $customerId, string $fontFamily, int $fontSize) {
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
        $pixelMappingForTask = array();
        foreach ($allFontsInTask as $font) {
            $fontFamily = strtolower($font['font']);
            $fontSize = intval($font['fontSize']);
            $pixelMappingForTask[$fontFamily][$fontSize] = $this->getPixelMappingByFont($customerId, $fontFamily, $fontSize);
            $pixelMappingForTask[$fontFamily][$fontSize]['default'] = $this->getDefaultPixelWidth($fontSize);
        }
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
