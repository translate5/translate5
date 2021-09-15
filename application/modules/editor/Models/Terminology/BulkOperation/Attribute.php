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
class editor_Models_Terminology_BulkOperation_Attribute extends editor_Models_Terminology_BulkOperation_Abstract {


    /**
     * @var editor_Models_Terminology_Models_AttributeModel
     */
    protected $model;
    /**
     * @var editor_Models_Terminology_TbxObjects_Attribute
     */
    protected $importObject;

    public function __construct() {
        $this->model = new editor_Models_Terminology_Models_AttributeModel();
        $this->importObject = new editor_Models_Terminology_TbxObjects_Attribute();
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    public function loadExisting(int $entryId) {
        $db = $this->model->db;
        $conn = $db->getAdapter()->getConnection();
        //this saves a lot of RAM:
        $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $stmt = $db->select()->from($db, $this->getFieldsToLoad())->where('termEntryId = ?', $entryId)->query(Zend_Db::FETCH_ASSOC);
        $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        // empty the existing attributes because this is loaded for each term entry
        $this->existing = [];
        /* @var $attribute editor_Models_Terminology_TbxObjects_Attribute */
        while($row = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            $this->processOneExistingRow($row['id'], new $this->importObject($row));
        }
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Attribute $elementObject
     */
    protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject)
    {
        $elementObject->termEntryId = $elementObject->parentEntry->id;
        $elementObject->termEntryGuid = $elementObject->parentEntry->entryGuid;
        $elementObject->langSetGuid = $elementObject->parentLangset->langSetGuid ?? null;
        $elementObject->termId = $elementObject->parentTerm->id ?? null;
        $elementObject->termTbxId = $elementObject->parentTerm->termTbxId ?? null;
        $elementObject->termGuid = $elementObject->parentTerm->guid ?? null;
    }
}
