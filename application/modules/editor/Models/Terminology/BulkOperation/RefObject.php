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
class editor_Models_Terminology_BulkOperation_RefObject extends editor_Models_Terminology_BulkOperation_Abstract {


    /**
     * @var editor_Models_Terminology_Models_RefObjectModel
     */
    protected $model;

    /**
     * @var editor_Models_Terminology_TbxObjects_RefObject
     */
    protected $importObject;

    /**
     * @var Zend_Db_Statement
     */
    protected Zend_Db_Statement $preparedStmt;

    protected int $collectionId;

    public function __construct() {
        $this->model = new editor_Models_Terminology_Models_RefObjectModel();
        $this->importObject = new editor_Models_Terminology_TbxObjects_RefObject();
    }

    /**
     * returns the fields to be loaded by loadExisting
     * @return array
     */
    protected function getFieldsToLoad(): array {
        $fields = $this->importObject->getUpdateableFields();
        $fields[] = 'id';
        $fields[] = 'key';
        return $fields;
    }

    public function loadExisting(int $id)
    {
        parent::loadExisting($id);
        $this->collectionId = $id;
        $this->preparedStmt = $this->model->db->getAdapter()->prepare('INSERT INTO terms_ref_object (`collectionId`, `listType`, `key`, `data`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE data = VALUES(data)');
    }

    public function createOrUpdateElement()
    {
        throw new BadMethodCallException('Use createOrUpdatePerson instead!');
    }

    public function createOrUpdateRefObject($listType, $key, $data) {
        $obj = $this->getNewImportObject();
        /* @var $obj editor_Models_Terminology_TbxObjects_RespPerson */
        $obj->key = $key;
        $obj->listType = $listType;
        $obj->data = json_encode($data);
        $existing = $this->findExisting($obj, $payload);

        $upsert = true;
        if(is_null($existing)) {
            $this->processedCount['created']++;
        }
        else {
            $hash = array_shift($payload);
            if($obj->getDataHash() !== $hash) {
                $this->processedCount['updated']++;
            }
            else {
                $upsert = false;
                $this->processedCount['unchanged']++;
            }
        }
        if($upsert) {
            $this->preparedStmt->execute([$this->collectionId, $listType, $key, $obj->data]);
        }
    }

    /**
     * @param editor_Models_Terminology_TbxObjects_Attribute $elementObject
     */
    protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject)
    {
        //do nothing
    }
}
