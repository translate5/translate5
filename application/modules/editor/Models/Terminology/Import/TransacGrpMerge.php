<?php

class editor_Models_Terminology_Import_TransacGrpMerge
{
    /** @var editor_Models_Terminology_Models_TransacgrpModel */
    protected editor_Models_Terminology_Models_TransacgrpModel $transacGrpModel;

    public function __construct()
    {
        $this->transacGrpModel = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');
    }

    /**
     * @param array $preparedSql
     * @return array[]
     */
    public function createOrUpdateTransacGrp(array $preparedSql): array
    {
        $sqlUpdate = $preparedSql['sqlUpdate'];
        $sqlInsert = $preparedSql['sqlInsert'];
        $sqlParam = $preparedSql['sqlParam'];

        if ($sqlUpdate) {
            $this->transacGrpModel->updateTransacGrp($sqlUpdate);
        }

        if ($sqlInsert) {
            $this->transacGrpModel->createTransacGrp($sqlParam, $sqlInsert['tableFields'], $sqlInsert['tableValue']);
        }

        return [$sqlUpdate];
    }
}
