<?php

class editor_Models_Terminology_Import_AttributeMerge
{
    /** @var editor_Models_Terminology_Models_AttributeModel */
    protected editor_Models_Terminology_Models_AttributeModel $attributeModel;

    public function __construct()
    {
        $this->attributeModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
    }

    /**
     * @param array $preparedSql
     * @return array[]
     */
    public function createOrUpdateAttribute(array $preparedSql): array
    {
        $sqlUpdate = $preparedSql['sqlUpdate'];
        $sqlInsert = $preparedSql['sqlInsert'];
        $sqlParam = $preparedSql['sqlParam'];

        if ($sqlUpdate) {
            $this->attributeModel->updateAttributes($sqlUpdate);
        }

        if ($sqlInsert) {
            $this->attributeModel->createAttributes($sqlParam, $sqlInsert['tableFields'], $sqlInsert['tableValue']);
        }

        return [$sqlUpdate];
    }
}
