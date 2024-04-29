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
 * CustomerConfiguration Entity Objekt
 *
 * @method string getId()
 * @method void setId(int $id)
 * @method string getCustomerId()
 * @method void setCustomerId(int $id)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getConfirmed()
 * @method void setConfirmed(bool $confirmed)
 * @method string getModule()
 * @method void setModule(string $module)
 * @method string getCategory()
 * @method void setCategory(string $category)
 * @method string getValue()
 * @method void setValue(string $value)
 * @method string getDefault()
 * @method void setDefault(string $default)
 * @method string getDefaults()
 * @method void setDefaults(string $defaults) comma seperated values!
 * @method string getType()
 * @method void setType(string $type)
 * @method string getDescription()
 * @method void setDescription(string $desc)
 */
class editor_Models_Customer_CustomerConfiguration extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_CustomerConfiguration';

    protected $validatorInstanceClass = 'editor_Models_Validator_CustomerConfiguration';

    /**
     * Load a customerConfiguration by customerId and name
     * @param string $name
     */
    protected function loadByCustomerIdAndName(int $customerId, $name)
    {
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
     * @param string $name
     * @return string|null
     */
    public function getValueForCustomerIdAndName(int $customerId, $name)
    {
        try {
            $this->loadByCustomerIdAndName($customerId, $name);
        } catch (Exception $e) {
            return null;
        }

        return $this->getValue();
    }
}
