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

    /***
     * @var bool
     */
    protected bool $mergeTerms;

    /**
     * Collection of attributes (note, ref, xref, descrip...) as object prepared for insert or update.
     * @var array
     */
    protected array $attributes = [];
    /**
     * Collection of term as object prepared for insert or update.
     * @var array
     */
    protected array $terms;
    /**
     * Collection of transacGrp as object prepared for insert or update.
     * @var array
     */
    protected array $transacGrps;

    public array $tableValues;
    protected string $elementClass; // get Class from $elementObject
    protected ?string $firstTableField; // first field from array
    protected ?string $lastTableField; // last field from array

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
     * Iterate over $element from given element and check if merge is set and than check if element to update.
     * @param object $model
     * @param array $parsedElements
     * @param array $elementCollection
     * @param bool $mergeTerms
     * @return array[]
     */
    public function createOrUpdateElement(object $model, array $parsedElements, array $elementCollection, bool $mergeTerms): array
    {

        $sqlUpdate = [];
        $sqlInsert = [];
        $sqlParam = '';

        $this->prepareElement($parsedElements[0]);


        // validate the term entries for all parsed terms
        $this->termEntryMergeUpdate($parsedElements,$elementCollection);

        $count = 0;
        foreach ($parsedElements as $element) {

            // reset the binding values array on each new element update/create chunk
            if ($count === 0) {
                $this->tableValues = [];
            }

            $checked = $this->checkIsForUpdate($element, $elementCollection);

            // it is direct match, update the object
            if ($checked['isUpdate']) {
                $sqlUpdate[] = $this->prepareSqlUpdate($element, $checked['match']['id']);
            }elseif ($checked['isCreate']){
                $sqlInsert = $this->prepareSqlInsert($element, $count);
                $sqlParam .= $sqlInsert['tableParam'];
            }
            $count++;
        }

        if ($sqlUpdate) {
            $model->updateImportTbx($sqlUpdate);
        }

        if ($sqlInsert) {
            $model->createImportTbx($sqlParam, $sqlInsert['tableFields'], $sqlInsert['tableValue']);
        }

        return ['sqlUpdate' => $sqlUpdate, 'sqlInsert' => $sqlInsert, 'sqlParam' => $sqlParam];
    }

    /**
     * check if element isset in elementCollection if true, call prepareDiffArrayToCheck() method to get two arrays to check.
     * if $result from array_diff is true set '$isUpdate = true'
     * if isset elementCollection is false do nothing.
     *
     * @param editor_Models_Terminology_TbxObjects_Abstract $elementObject
     * @param array $elementCollection
     * @return array
     */
    public function checkIsForUpdate(editor_Models_Terminology_TbxObjects_Abstract $elementObject, array $elementCollection): array
    {

        $match = $elementObject->findInArray($elementCollection,$this->mergeTerms);

        // if it is not found in the cache, create new element
        if(empty($match)){
            return [
                'isCreate' => true,
                'isUpdate' => false,
                'match' => $match
            ];
        }

        $preparedArrayForDiff = $this->prepareDiffArrayToCheck($elementObject, $match);
        $result = array_diff($preparedArrayForDiff[0], $preparedArrayForDiff[1]);

        return [
            'isUpdate' => !empty($result),
            'isCreate' => false,
            'match' => $match
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

    /***
     * All the terms which are not in the current parsed term-entry, must be put in the first merge-term match.
     * This check is only valid when mergeTerm is active.
     *
     * @param array $parsedTerms
     * @param array $elementCollection
     */
    protected function termEntryMergeUpdate(array $parsedTerms, array $elementCollection){

        if(!$this->mergeTerms || !$this->isTermElementSave()){
            return;
        }

        $mergeEntryMatch = null;
        $mergeEntryDbMatch = null;
        foreach ($parsedTerms as $term) {
            $checked = $this->checkIsForUpdate($term, $elementCollection);
            if (!empty($checked['match']) && $term->getTermEntryTbxId() !== $checked['match']['termEntryTbxId'] && !isset($mergeEntryMatch)) {
                $mergeEntryMatch = $checked['match']['termEntryTbxId'];
                $mergeEntryDbMatch = (int) $checked['match']['termEntryId'];
                // break after the first match is found. Always the termEntryTbx from the first match will be used
                break;
            }
        }

        if(!isset($mergeEntryMatch)){
            return;
        }


        foreach ($parsedTerms as &$term) {

            $oldTermEntryTbxId = $term->getTermEntryTbxId();
            $oldTermEntryId = $term->getTermEntryId();

            $checked = $this->checkIsForUpdate($term, $elementCollection);

            if($checked['isCreate'] !== true){
                continue;
            }

            $term->setTermEntryTbxId($mergeEntryMatch);
            $term->setTermEntryId($mergeEntryDbMatch);

            $checked = $this->checkIsForUpdate($term, $elementCollection);

            if($checked['isUpdate'] === true){
                // update the transac for all term attributes and term transac groups
                $this->updateParsedTermAttributeValues($term->getGuid(),[
                    'termEntryId' => $mergeEntryDbMatch
                ]);
            }elseif ($checked['isCreate'] === true){
                $term->setTermEntryTbxId($oldTermEntryTbxId);
                $term->setTermEntryId($oldTermEntryId);
            }
        }
    }

    /***
     * Update the term attribute and transac group attribute with given field-value map
     * @param string $termGuid
     * @param array $fieldValue
     */
    protected function updateParsedTermAttributeValues(string $termGuid, array $fieldValue = []){
        foreach ($fieldValue as $field=>$value) {
            $setter = 'set'.ucfirst($field);
            foreach ($this->attributes as &$attribute) {
                // if the attribute term guid is matching the current term guid, force merge this attribute
                if($attribute->getTermGuid() === $termGuid){
                    $attribute->$setter($value);
                }
            }

            foreach ($this->transacGrps as &$transac) {
                // if the transac group term guid is matching the current term guid, force merge this transac group
                if($transac->getTermGuid() === $termGuid){
                    $transac->$setter($value);
                }
            }
        }
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

    /***
     * Is the current update/insert call for term objects
     * @return bool
     */
    protected function isTermElementSave(){
        return $this->elementClass === 'editor_Models_Terminology_TbxObjects_Term';
    }
}
