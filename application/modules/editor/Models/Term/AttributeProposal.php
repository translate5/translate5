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
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $collectionId)
 * @method integer getAttributeId() getAttributeId()
 * @method void setAttributeId() setAttributeId(integer $attributeId)
 * @method string getValue() getValue()
 * @method void setValue() setValue(string $value)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $userName)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $created)
 */
class editor_Models_Term_AttributeProposal extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Term_AttributeProposal';
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_AttributeProposal';

    /**
     * Loads a proposal by attributeId
     * @param integer $attributeId
     * @return Zend_Db_Table_Row_Abstract
     */
    public function loadByAttributeId(int $attributeId): Zend_Db_Table_Row_Abstract {
        return $this->loadRow('attributeId = ?', $attributeId);
    }


    /***
     * Check if the given attribute value is proposal for the given attributeId
     *
     * @param int $attributeId
     * @param string $value
     * @return boolean
     */
    public function isProposal(int $attributeId,string $value){
        $s=$this->db->select()
        ->where('attributeId=?',$attributeId)
        ->where('value=?',$value);
        $result=$this->db->fetchAll($s)->toArray();
        return !empty($result);
    }

    /***
     * Remove term attribute proposals for given termId. The term attribute proposals from the original
     * attribute table (attributes with processStatus=unprocessed) will be removed to
     *
     * @param int $termId
     * @return boolean
     */
    public function removeAllTermAttributeProposals(int $termId){
        //collect the term attribute proposals for the term
        $s=$this->db->select()
        ->setIntegrityCheck(false)
        ->from($this->db,[])
        ->join('LEK_term_attributes', 'LEK_term_attributes.id = LEK_term_attribute_proposal.attributeId',['LEK_term_attributes.id as id'])
        ->where('LEK_term_attributes.termId=?',$termId);
        $rows=$this->db->fetchAll($s)->toArray();
        $count1=0;
        if (!empty($rows)) {
            //remove the term attribute proposals from the attribute proposals table
            $count1=$this->db->delete(['attributeId IN(?)' => $rows]);
        }
        $attribute = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        /* @var $attribute editor_Models_Terminology_Models_AttributeModel */
        //remove the term attribute proposals from the term attribute table
        $count2 = $attribute->db->delete(['termId=?' => $termId,'processStatus=?' => editor_Models_Terminology_Models_TermModel::PROCESS_STATUS_UNPROCESSED]);
        return $count1 + $count2 > 0;
    }

    /***
     * Remove attribute proposal by attributeId and proposal value
     *
     * @param int $attributeId
     * @param string $value
     * @return boolean
     */
    public function removeAttributeProposal(int $attributeId,string $value): bool
    {
        return $this->db->delete([
            'attributeId=?' => $attributeId,
            'value=?' => $value,
        ])>0;
    }

    /***
     * Remove old attribute proposals from the collection by given date.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeOlderThan(array $collectionIds,string $olderThan): bool
    {
        return $this->db->delete([
            'created < ?' => $olderThan,
            'collectionId in (?)' => $collectionIds,
        ])>0;
    }
}
