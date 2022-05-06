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
 * Entity Model for comment meta data
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getCustomerId() getCustomerId()
 * @method void setCustomerId() setCustomerId(integer $id)
 * @method integer getDefaultBconfId() getDefaultBconfId()
 * @method void setDefaultBconfId() setDefaultBconfId(integer $id)
 */
class editor_Models_Customer_Meta extends ZfExtended_Models_Entity_MetaAbstract {
    protected $dbInstanceClass = 'editor_Models_Db_CustomerMeta';

    /**
     * @param int $customerId
     * @return Zend_Db_Table_Row_Abstract
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByCustomerId(int $customerId): Zend_Db_Table_Row_Abstract {
        return $this->loadRow('customerId = ?', $customerId);
    }
    
    /**
     * Adds an empty meta data rowset to the DB.
     */
    public function initEmptyRowset(){
        $db = new $this->dbInstanceClass;
        /* @var $db Zend_Db_Table_Abstract */
        try {
            $db->insert(array('customerId' => $this->getCustomerId()));
        }
        catch(Zend_Db_Statement_Exception $e) {
            try {
                $this->handleIntegrityConstraintException($e);
            }
            catch(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
                //"duplicate entry" errors are ignored. 
                return;
            }
        }
    }
}