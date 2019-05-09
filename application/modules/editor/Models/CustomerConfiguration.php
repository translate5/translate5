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
 * CustomerConfiguration Entity Objekt
 * 
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(int $id)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method boolean getConfirmed() getConfirmed()
 * @method void setConfirmed() setConfirmed(bool $confirmed)
 * @method string getModule() getModule()
 * @method void setModule() setModule(string $module)
 * @method string getCategory() getCategory()
 * @method void setCategory() setCategory(string $category)
 * @method string getValue() getValue()
 * @method void setValue() setValue(string $value)
 * @method string getDefault() getDefault()
 * @method void setDefault() setDefault(string $default)
 * @method string getDefaults() getDefaults()
 * @method void setDefaults() setDefaults(string $defaults) comma seperated values!
 * @method string getType() getType()
 * @method void setType() setType(string $type)
 * @method string getDescription() getDescription()
 * @method void setDescription() setDescription(string $desc)
 * 
 * 
*/
class editor_Models_CustomerConfiguration extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_CustomerConfiguration';
    protected $validatorInstanceClass   = 'editor_Models_Validator_CustomerConfiguration';
    
    /**
     * Load a customerConfiguration by customerId and name
     * @param int $customerId
     * @param string $name
     */
    protected function loadByCustomerIdAndName(int $customerId, $name){
        try {
            $s = $this->db->select()
                ->where('customerId = ?', $customerId)
                ->where('name = ?', $name);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            $this->notFound('NotFound after other Error', $e);
        }
        if ($row) {
            //load implies loading one Row, so use only the first row
            $this->row = $row;
        }
    }
    
    /**
     * Returns the value of a customerConfiguration by customerId and name.
     * @param int $customerId
     * @param string $name
     * @return string|null
     */
    public function getValueForCustomerIdAndName(int $customerId, $name){
        try {
            $this->loadByCustomerIdAndName($customerId, $name);
        } catch (Exception $e) {
            return null;
        }
        return $this->getValue();
    }
}
