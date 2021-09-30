<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class editor_Models_Terminology_Models_CollectionAttributeDataType extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_CollectionAttributeDataType';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Term_CollectionAttributeDataType';

    /***
     * Update the attribute data-type associations for the given term collection.
     * If the attribute data type exist for the collection, no row will be inserted.
     *
     * @param int $collectionId
     */
    public function updateCollectionAttributeAssoc(int $collectionId){
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->query("INSERT INTO `terms_collection_attribute_datatype` (collectionId,dataTypeId) (SELECT collectionId,dataTypeId FROM `terms_attributes` WHERE collectionId = ? GROUP BY collectionId,dataTypeId) ON DUPLICATE KEY UPDATE id = id",[$collectionId]);
    }
}
