<?php

class editor_Models_Terminology_Import_AbstractTerminology
{
    public array $tableValues;
    protected string $elementClass; // get Class from $elementObject
    protected ?string $firstTableField; // first field from array
    protected ?string $lastTableField; // last field from array


    /**
     * Iterate over $element from given element and check if merge is set and than check if element to update.
     * @param array $parsedElements
     * @param array $elementCollection
     * @param bool $mergeTerms
     * @return array[]
     */
    public function createOrUpdateElement(array $parsedElements, array $elementCollection, bool $mergeTerms): array
    {
        $sqlUpdate = [];
        $sqlInsert = [];
        $sqlParam = '';

        $this->prepareElement($parsedElements[0]);

        $count = 0;
        foreach ($parsedElements as $element) {
            if ($mergeTerms) {
                $collectionKey = $element->getCollectionKey($element);
                $checked = $this->checkIsForUpdate($element, $elementCollection, $collectionKey);

                if ($checked['isUpdate']) {
                    $sqlUpdate[] = $this->prepareElementUpdate($element, $elementCollection[$collectionKey]['id']);
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
        $isUpdate = false;
        $isCreate = false;

        if (isset($elementCollection[$collectionKey])) {
            $preparedArrayForDiff = $this->prepareDiffArrayToCheck($elementObject, $elementCollection[$collectionKey]);
            $result = array_diff($preparedArrayForDiff[0], $preparedArrayForDiff[1]);
            if ($result) {
                $isUpdate = true;
            }
        } else {
            $isUpdate = false;
            $isCreate = true;
        }

        return ['isUpdate' => $isUpdate, 'isCreate' => $isCreate];
    }
    /**
     * @param object $element
     * @param int $count
     * @return array
     */
    public function prepareSqlInsert(object $element, int $count): array
    {
        if ($count === 0) {
            $this->tableValues = [];
        }
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
    public function prepareElementUpdate(object $element, int $id): array
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
