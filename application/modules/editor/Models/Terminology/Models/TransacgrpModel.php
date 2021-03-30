<?php

use Doctrine\DBAL\Exception;

/**
 * Class editor_Models_Terminology_Models_Transacgrp
 * TermsTransacgrp Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method string getTransac() getTransac()
 * @method string setTransac() setTransac(string $transac)
 * @method string getDate() getDate()
 * @method string setDate() setDate(string $admin)
 * @method string getAdminType() getAdminType()
 * @method string setAdminType() setAdminType(string $adminType)
 * @method string getAdminValue() getAdminValue()
 * @method string setAdminValue() setAdminValue(string $adminValue)
 * @method string getTransacNote() getTransacNote()
 * @method string setTransacNote() setTransacNote(string $transacNote)
 * @method string getTransacType() getTransacType()
 * @method string setTransacType() setTransacType(string $transacType)
 * @method string getIfDescripgrp() getIfDescripgrp()
 * @method string setIfDescripgrp() setIfDescripgrp(string $ifDescripgrp)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getEntryId() getEntryId()
 * @method string setEntryId() setEntryId(string $entryId)
 * @method string getTermId() getTermId()
 * @method string setTermId() setTermId(string $termId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 * @method string getElementName() getElementName()
 * @method string setElementName() setElementName(string $elementName)
 */
class editor_Models_Terminology_Models_TransacgrpModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Transacgrp';

    /**
     * editor_Models_Terms_Transacgrp constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    public function getTransacGrpCollectionByEntryId($collectionId, $entryId): array
    {
        $transacGrpByKey = [];

        $query = "SELECT * FROM terms_transacgrp WHERE collectionId = :collectionId AND entryId = :entryId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId, 'entryId' => $entryId]);

        foreach ($queryResults as $key => $transacGrp) {
            $transacGrpByKey[$transacGrp['elementName'].'-'.$transacGrp['transac'].'-'.$transacGrp['ifDescripgrp'].'-'.$transacGrp['termId']] = $transacGrp;
        }

        return $transacGrpByKey;
    }

    public function createTransacGrp(string $sqlParam, string $sqlFields, array $sqlValue)
    {
        $this->init();
        $insertTerms = rtrim($sqlParam, ',');
        $query = "INSERT INTO terms_transacgrp ($sqlFields) VALUES $insertTerms";

        return $this->db->getAdapter()->query($query, $sqlValue);
    }
    /**
     * @param array $transacGrps
     * @return bool
     */
    public function updateTransacGrp(array $transacGrps): bool
    {
        foreach ($transacGrps as $transacGrp) {
            $this->db->update($transacGrp, ['id=?'=> $transacGrp['id']]);
        }

        return true;
    }
}
