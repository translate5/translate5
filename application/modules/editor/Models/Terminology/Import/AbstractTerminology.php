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
 * Class to create or update TBX elements in database, called after each termEntry in TbxFileImport.php for termEntries
 * and after parse <back> element
 *
 * METHODS:
 * - createOrUpdateElement()
 * prepare and check elements is for update or insert
 * - prepareElement()
 * get ClassName from object and first/last table fields to handle element as object
 * - checkIsForUpdate()
 * chek TBX element is for update or insert
 * - prepareSqlInsert()
 * prepare elements for multiple sql insert will call createTableParamString method to create string
 * - prepareSqlUpdate()
 * prepare elements as array for multiple sql update
 * - createTableParamString()
 * create sql params and values for sql insert into
 * - prepareDiffArrayToCheck()
 * prepare two arrays with same keys to check is element for update
 *
 * Class editor_Models_Terminology_Import_AbstractTerminology
 */
abstract class editor_Models_Terminology_Import_AbstractTerminology
{
    public array $tableValues;
    protected string $elementClass; // get Class from $elementObject
    protected ?string $firstTableField; // first field from array
    protected ?string $lastTableField; // last field from array

    /**
     * Iterate over $element from given element and check if merge is set and than check if element to update.
     * @param object $termsModel
     * @param array $parsedElements
     * @param array $elementCollection
     * @param bool $mergeTerms
     * @return array[]
     */
    public function createOrUpdateElement(object $termsModel, array $parsedElements, array $elementCollection, bool $mergeTerms): array
    {
        $sqlUpdate = [];
        $sqlInsert = [];
        $sqlParam = '';

        $this->prepareElement($parsedElements[0]);

        $count = 0;
        foreach ($parsedElements as $element) {

            // reset the binding values array on each new element update/create chunk
            if ($count === 0) {
                $this->tableValues = [];
            }

            if ($mergeTerms) {
                $collectionKey = $element->getCollectionKey($element); // getCollectionKey will get ArrayKey for each element to check if exist
                $checked = $this->checkIsForUpdate($element, $elementCollection, $collectionKey);

                if ($checked['isUpdate']) {
                    $sqlUpdate[] = $this->prepareSqlUpdate($element, $elementCollection[$collectionKey]['id']);
                }
                if ($checked['isCreate']) {
                    $sqlInsert = $this->prepareSqlInsert($element, $count);
                    $sqlParam .= $sqlInsert['tableParam'];
                }

            } else {
                $sqlInsert = $this->prepareSqlInsert($element, $count);
                $sqlParam .= $sqlInsert['tableParam'];
            }
            $count++;
        }

        if ($sqlUpdate) {
            $termsModel->updateImportTbx($sqlUpdate);
        }

        if ($sqlInsert) {
            $termsModel->createImportTbx($sqlParam, $sqlInsert['tableFields'], $sqlInsert['tableValue']);
        }

        return ['sqlUpdate' => $sqlUpdate, 'sqlInsert' => $sqlInsert, 'sqlParam' => $sqlParam];
    }

    /**
     * @param object $element
     */
    private function prepareElement(object $element)
    {
        $this->elementClass = get_class($element); // get Class from $elementObject
        $this->firstTableField = array_key_first($this->elementClass::TABLE_FIELDS); // first field from array
        $this->lastTableField = array_key_last($this->elementClass::TABLE_FIELDS); // last field from array
    }

    /**
     * check if element isset in elementCollection if true, call prepareDiffArrayToCheck() method to get two arrays to check.
     * if $result from array_diff is true set '$isUpdate = true'
     * if isset elementCollection is false do nothing.
     *
     * @param object $elementObject
     * @param array $elementCollection
     * @param string $collectionKey
     * @return array
     */
    public function checkIsForUpdate(object $elementObject, array $elementCollection, string $collectionKey): array
    {

        // if it is not found in the cache, create new element
        if(!isset($elementCollection[$collectionKey])){
            return [
                'isUpdate' => false,
                'isCreate' => true
            ];
        }

        $preparedArrayForDiff = $this->prepareDiffArrayToCheck($elementObject, $elementCollection[$collectionKey]);
        $result = array_diff($preparedArrayForDiff[0], $preparedArrayForDiff[1]);

        return [
            'isUpdate' => !empty($result),
            'isCreate' => false
        ];
    }

    /**
     * @param object $element
     * @param int $count
     * @return array
     */
    public function prepareSqlInsert(object $element, int $count): array
    {
        $tableParam = ''; // string contents generated param names for sql insert

        // iterate over fields and set $table param and transacGrpInsertParams for multiple sql insert
        foreach ($element::TABLE_FIELDS as $field => $isUpdate) {
            $tableParam .= $this->createTableParamString($field, $count);
            $this->tableValues[$field.$count] = $this->getElementMethod($element, $field);
        }

        $tableFields =  implode(",", array_keys($element::TABLE_FIELDS));

        return['tableFields' => $tableFields, 'tableParam' => $tableParam, 'tableValue' => $this->tableValues];
    }

    /**
     * Prepare update sqlValues.
     * Iterate over TABLE_FIELDS and get only fields that value is true for update
     * @param object $element
     * @param int $id
     * @return array
     */
    public function prepareSqlUpdate(object $element, int $id): array
    {
        $fieldsToUpdate = [];
        $fieldsToUpdate['id'] = $id;

        foreach ($element::TABLE_FIELDS as $field => $isUpdate) {
            if ($isUpdate) {
                $fieldsToUpdate[$field] = $this->getElementMethod($element, $field);
            }
        }

        return $fieldsToUpdate;
    }

    /**
     * Create sql string with params for insert statement, increase each param in string with $count
     * (:term0, :status0, :definition0),
     * the next will be
     * (:term1, :status1, :definition1),
     *
     * @param string $field
     * @param int $count
     * @return string
     */
    private function createTableParamString(string $field, int $count): string
    {
        $tableParam = '';
        if ($field === $this->firstTableField) {
            $tableParam .= '(:' . $field . $count .',';
        }

        if ($field !== $this->firstTableField && $field !== $this->lastTableField ) {
            $tableParam .= ':' . $field . $count .',';
        }

        if ($field === $this->lastTableField) {
            $tableParam .= ':' . $field . $count .'),';
        }

        return $tableParam;
    }

    /**
     * Prepare arrays for array_diff compare
     * Remove id and all GUID keys from object and model
     * @param object $tbxElement
     * @param array $elementModel
     * @return array[]
     */
    public function prepareDiffArrayToCheck(object $tbxElement, array $elementModel): array
    {
        $compareTbx = [];
        $compareModel = [];
        foreach ($tbxElement::TABLE_FIELDS as $field => $isUpdate) {
            if ($field !== 'id' && $isUpdate === true) {
                $compareTbx[$field] = $this->getElementMethod($tbxElement, $field);
                $compareModel[$field] = $elementModel[$field];
            }
        }

        return [$compareTbx, $compareModel];
    }

    /**
     * @param object $element
     * @param string $field
     * @return mixed
     */
    public function getElementMethod(object $element, string $field)
    {
        $getField = 'get'.ucfirst($field);

        return $element->$getField();
    }
}
