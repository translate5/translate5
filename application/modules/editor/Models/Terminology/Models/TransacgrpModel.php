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
 * @method string getTermEntryId() getTermEntryId()
 * @method string setTermEntryId() setTermEntryId(string $TermEntryId)
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

    public function getTransacGrpCollectionByEntryId($collectionId, $termEntryId): array
    {
        $transacGrpByKey = [];

        $query = "SELECT * FROM terms_transacgrp WHERE collectionId = :collectionId AND termEntryId = :termEntryId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId, 'termEntryId' => $termEntryId]);

        foreach ($queryResults as $key => $transacGrp) {
            $transacGrpByKey[$transacGrp['elementName'].'-'.$transacGrp['transac'].'-'.$transacGrp['ifDescripgrp'].'-'.$transacGrp['termId']] = $transacGrp;
        }

        return $transacGrpByKey;
    }

    public function getTransacGrpByCollectionId(int $collectionId): array
    {
        $transacGrpByKey = [];

        $query = "SELECT * FROM terms_transacgrp WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        foreach ($queryResults as $key => $transacGrp) {
            $transacGrpByKey[$transacGrp['elementName'].'-'.$transacGrp['transac'].'-'.$transacGrp['ifDescripgrp'].'-'.$transacGrp['termId']] = $transacGrp;
        }

        return $transacGrpByKey;
    }

    public function createImportTbx(string $sqlParam, string $sqlFields, array $sqlValue)
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
    public function updateImportTbx(array $transacGrps): bool
    {
        foreach ($transacGrps as $transacGrp) {
            $this->db->update($transacGrp, ['id=?'=> $transacGrp['id']]);
        }

        return true;
    }


    /***
     * Handle transac attributes group. If no transac group attributes exist for the entity, new one will be created.
     *
     * @param editor_Models_Terminology_Models_TermModel|editor_Models_Terminology_Models_TermEntryModel $entity
     * @return bool
     */
    public function handleTransacGroup($entity): bool
    {
        if ($entity->getId() === null) {
            return false;
        }
        $ret = $this->getTransacGroup($entity);
        //if the transac group exist, do nothing
        if (!empty($ret)) {
            return false;
        }

        return true;
    }

    /***
     * Get transac attribute for the entity and type
     *
     * @param editor_Models_Terminology_Models_TermModel|editor_Models_Terminology_Models_TermEntryModel $entity
     * @param array $types
     * @return array
     */
    public function getTransacGroup($entity): array
    {
        $s = $this->db->select();
        if ($entity instanceof editor_Models_Terminology_Models_TermModel){
            $s->where('termId=?', $entity->getTermId());
        }

        if ($entity instanceof editor_Models_Terminology_Models_TermEntryModel){
            $s->where('id=?', $entity->getId());
        }

        return $this->db->fetchAll($s)->toArray();
    }
    /***
     * Create transac group attributes with its values. The type can be creation or modification
     * Depending on what kind of entity is passed, the appropriate attribute will be created(term attribute or term entry attribute)
     *
     * @param editor_Models_Terminology_Models_TermModel|editor_Models_Terminology_Models_TermEntryModel $entity
     * @param string $type
     * @return bool
     */
    public function createTransacGroup($entity, string $type): bool
    {

        return true;
    }


    /***
     * Update the term transac group attributes from the proposal attributes
     * @param editor_Models_Terminology_Models_TermModel $term
     * @param editor_Models_Term_Proposal $proposal
     * @return boolean
     */
    public function updateTermTransacGroupFromProposal(editor_Models_Terminology_Models_TermModel $term,editor_Models_Term_Proposal $proposal): bool
    {

        return true;
    }

}
