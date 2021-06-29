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
 * @method string getTerm() getTerm()
 * @method void setTerm() setTerm(string $term)
 * @method integer getTermId() getTermId()
 * @method void setTermId() setTermId(integer $id)
 * @method integer getCollectionId() getCollectionId()
 * @method void setCollectionId() setCollectionId(integer $id)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $userName)
 * @method string getCreated() getCreated()
 * @method void setCreated() setCreated(string $date)
 */
class editor_Models_Term_Proposal extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Term_Proposal';
    protected $validatorInstanceClass = 'editor_Models_Validator_Term_Proposal';

    /**
     * If $transacgrpData is given, method expects it's an array containing values under 'termEntryId' and 'language' keys,
     * and if so, this method will run UPDATE query to update `date` and `transacNote` for all involved records of
     * `terms_transacgrp` table for entry-, language- and term-level
     *
     * @param bool|array $transacgrpData
     * @return mixed
     */
    public function save($transacgrpData = false) {

        // Call parent
        $return = parent::save();

        // If $transacgrpData arg is given - update 'modification'-records of all levels
        if ($transacgrpData)
            ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels(
                    $this->getUserName(),
                    $transacgrpData['termEntryId'],
                    $transacgrpData['language'],
                    $this->getTermId()
                );

        // Return
        return $return;
    }

    /**
     * Loads a proposal by termId
     * @param integer $termId
     * @return Zend_Db_Table_Row_Abstract
     */
    public function loadByTermId(int $termId): Zend_Db_Table_Row_Abstract {
        return $this->loadRow('termId = ?', $termId);
    }

    /**
     * Find term proposal in collection by given language and term value
     *
     * @param string $termText
     * @param integer $languageId
     * @param integer $termCollection
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function findProposalInCollection(string $termText, int $languageId, int $termCollection){
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from(['p'=>'terms_proposal'],['p.term as termProposalValue','p.created as termProposalCreated','p.termId as termProposalTermId'])
        ->join(['t'=>'terms_term'],'t.id=p.termId')
        ->where('p.term = ?', $termText)
        ->where('t.language = ?', $languageId)
        ->where('t.collectionId = ?',$termCollection);
        return $this->db->fetchAll($s);
    }

    /***
     * Check if the given term value is proposal for the given termId
     *
     * @param int $termId
     * @param string $term
     * @return boolean
     */
    public function isTermProposal(int $termId,string $term){
        $s=$this->db->select()
        ->where('termId=?',$termId)
        ->where('term=?',$term);
        return !empty($this->db->fetchRow($s));
    }

    /***
     * Remove term proposal by termId and proposal value
     *
     * @param int $termId
     * @param string $term
     * @return boolean
     */
    public function removeTermProposal(int $termId,string $term){
        return $this->db->delete([
            'termId=?' => $termId,
            'term=?' => $term
        ])>0;
    }

    /***
     * Remove old term proposals by given date.
     *
     * @param array $collectionIds
     * @param string $olderThan
     * @return boolean
     */
    public function removeOlderThan(array $collectionIds,string $olderThan){
        $term=ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $term editor_Models_Terminology_Models_TermModel */
        //remove proposals from the term table
        $rowsCount=$term->db->delete([
            'updated < ?' => $olderThan,
            'collectionId in (?)' => $collectionIds,
            'processStatus=?'=>$term::PROCESS_STATUS_UNPROCESSED
        ]);
        return ($this->db->delete([
            'created < ?' => $olderThan,
            'collectionId in (?)' => $collectionIds,
        ])+$rowsCount)>0;
    }
}
