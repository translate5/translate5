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

/**
 * Class to create or update multiple collected TBX elements
 */
abstract class editor_Models_Terminology_BulkOperation_Abstract
{

    /**
     * Items to be processed (saved into DB)
     * @var editor_Models_Terminology_TbxObjects_Abstract[]
     */
    protected array $toBeProcessed = [];

    /**
     * contains all element IDs not updated (for bulk post processing)
     * @var array
     */
    protected array $unchangedIds = [];

    /**
     * collection of existing elements in DB of the type to be processed (constructed key => hashvalue)
     * @var string[]
     */
    protected array $existing = [];

    /**
     * @var editor_Models_Terminology_Models_Abstract
     */
    protected $model;

    /**
     * @var editor_Models_Terminology_TbxObjects_Abstract
     */
    protected $importObject;

    /**
     * counters how many elements are processed
     * @var array|int[]
     */
    protected array $processedCount = [
        'created' => 0,
        'updated' => 0,
        'unchanged' => 0,
    ];

    /**
     * @var bool
     */
    protected bool $mergeTerms;

    abstract public function __construct();

    public function getNewImportObject(): editor_Models_Terminology_TbxObjects_Abstract{
        return new $this->importObject;
    }

    public function add(editor_Models_Terminology_TbxObjects_Abstract $importObject) {
        $this->toBeProcessed[] = $importObject;
    }

    /**
     * @throws Zend_Db_Statement_Exception
     */
    public function loadExisting(int $collectionId) {
        $db = $this->model->db;
        $conn = $db->getAdapter()->getConnection();
        //this saves a lot of RAM:
        $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $stmt = $db->select()->from($db, $this->getFieldsToLoad())->where('collectionId = ?', $collectionId)->query(Zend_Db::FETCH_ASSOC);

        /* @var $attribute editor_Models_Terminology_TbxObjects_Attribute */
        while($row = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            $this->processOneExistingRow($row['id'], new ($this->importObject)($row));
        }
        $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     * returns the fields to be loaded by loadExisting
     * @return array
     */
    protected function getFieldsToLoad(): array {
        $fields = $this->importObject->getUpdateableFields();
        $fields[] = 'id';
        $fields[] = $this->importObject::GUID_FIELD; //the elements GUID field must be loaded always
        return $fields;
    }

    /**
     * process one loaded existing element
     * @param int $id
     * @param editor_Models_Terminology_TbxObjects_Abstract $element
     */
    protected function processOneExistingRow(int $id, editor_Models_Terminology_TbxObjects_Abstract $element) {
        $this->existing[$element->getCollectionKey()] = $id.'#'.$element->getDataHash();
    }

    /**
     * Iterate over $element from given element and check if merge is set and than check if element to update.
     * @param bool $mergeTerms
     * @throws Zend_Db_Table_Exception
     */
    public function createOrUpdateElement(bool $mergeTerms)
    {
        if(empty($this->toBeProcessed)) {
            return;
        }
        $this->mergeTerms = $mergeTerms;

        $sqlUpdate = [];
        $sqlInsertData = [];
        $sqlInsertBindings = [];
        $this->unchangedIds = [];

        $count = 0;
        // reset the binding values array on each new element update/create chunk
        foreach ($this->toBeProcessed as $element) {
            $payload = null;
            $this->fillParentIds($element);
            $existing = $this->findExisting($element, $payload);
            if(is_null($existing)) {
                //on creation and only on creation the GUID must be set
                $element->{$element::GUID_FIELD} = ZfExtended_Utils::uuid();
                $sqlInsertBindings[] = $this->prepareSqlInsert($element, $count, $sqlInsertData);
                $this->processedCount['created']++;
            }
            else {
                $hash = array_shift($payload);
                if($element->getDataHash() !== $hash) {
                    $sqlUpdate[] = $this->prepareSqlUpdate($element, $existing);
                    $this->processedCount['updated']++;
                }
                else {
                    $this->unchangedIds[] = $existing;
                    $this->processedCount['unchanged']++;
                }
            }
            $count++;
        }

        if (!empty($sqlUpdate)) {
            $this->model->updateImportTbx($sqlUpdate);
        }

        if (!empty($sqlInsertData)) {
            $this->model->createImportTbx(join(',', $sqlInsertBindings), array_keys($this->importObject::TABLE_FIELDS), $sqlInsertData);
        }
        $this->toBeProcessed = [];
    }

    /**
     * find element in existing elements
     *
     * @param editor_Models_Terminology_TbxObjects_Abstract $elementObject
     * @param array|null $payload
     * @return int|null null if no existing entry found, otherwise the id of the existing element in DB
     */
    protected function findExisting(editor_Models_Terminology_TbxObjects_Abstract $elementObject, ?array &$payload = null): ?int
    {
        $existing = $this->existing[$elementObject->getCollectionKey()] ?? null;
        if(empty($existing)) {
            return null;
        }

        $payload = explode('#', $existing);
        return array_shift($payload); //the first item is the DB ID
    }

    /**
     * Some fields (guid, tbxIds) can only be set if the parent elements were saved to the DB or loaded from DB (on merging)
     * @param editor_Models_Terminology_TbxObjects_Abstract $elementObject
     */
    abstract protected function fillParentIds(editor_Models_Terminology_TbxObjects_Abstract $elementObject);

    /**
     * @param editor_Models_Terminology_TbxObjects_Abstract $element
     * @param int $count
     * @param array $sqlInsert
     * @return string the bindings per element
     */
    protected function prepareSqlInsert(editor_Models_Terminology_TbxObjects_Abstract $element, int $count, array &$sqlInsert): string
    {
        $bindPlaceholders = [];
        // iterate over fields and set $table param and transacGrpInsertParams for multiple sql insert
        foreach ($element::TABLE_FIELDS as $field => $isUpdate) {
            $bindPlaceholders[] = $field.$count;
            $sqlInsert[$field.$count] = $element->$field;
        }

        return '(:'.join(', :', $bindPlaceholders).')';
    }

    /**
     * FIXME update the changed fields only... Not all updateable, since not all may be loaded from DB! like entryGuid or so
     * Prepare update sqlValues.
     * Iterate over TABLE_FIELDS and get only fields that value is true for update
     * @param editor_Models_Terminology_TbxObjects_Abstract $element
     * @param int $id
     * @return array
     */
    protected function prepareSqlUpdate(editor_Models_Terminology_TbxObjects_Abstract $element, int $id): array
    {
        $fieldsToUpdate = [];
        $fieldsToUpdate['id'] = $id;

        foreach ($element::TABLE_FIELDS as $field => $isUpdate) {
            if ($isUpdate) {
                $fieldsToUpdate[$field] = $element->$field;
            }
        }

        return $fieldsToUpdate;
    }

    /**
     * return statistics over the processed elements
     * @return array
     */
    public function getStatistics(): array {
        return $this->processedCount;
    }
}
