<?php

use Doctrine\DBAL\Exception;

/**
 * Class editor_Models_Terms_Term
 * Term Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getLanguageId() getLanguageId()
 * @method integer setLanguageId() setLanguageId(integer $languageId)
 * @method string getLanguage() getLanguage()
 * @method string setLanguage() setLanguage(string $language)
 * @method string getTermId() getTermId()
 * @method string setTermId() setTermId(string $termId)
 * @method string getTerm() getTerm()
 * @method string setTerm() setTerm(string $term)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method integer getEntryId() getEntryId()
 * @method integer setEntryId() setEntryId(integer $termEntryId)
 * @method string getTermEntryGuid() getTermEntryGuid()
 * @method string setTermEntryGuid() setTermEntryGuid(string $entryGuid)
 * @method string getDescrip() getDescrip()
 * @method string setDescrip() setDescrip(string $descrip)
 * @method string getDescripType() getDescripType()
 * @method string setDescripType() setDescripType(string $descripType)
 * @method string getDescripTarget() getDescripTarget()
 * @method string setDescripTarget() setDescripTarget(string $descripTarget)
 * @method string getStatus() getStatus()
 * @method string setStatus() setStatus(string $Status)
 * @method string getProcessStatus() getProcessStatus()
 * @method string setProcessStatus() setProcessStatus(string $processStatus)
 * @method string getDefinition() getDefinition()
 * @method string setDefinition() setDefinition(string $definition)
 * @method string getLangSetGuid() getLangSetGuid()
 * @method string setLangSetGuid() setLangSetGuid(string $langSetGuid)
 * @method string getGuid() getGuid()
 * @method string setGuid() setGuid(string $guid)
 * @method string getUserGuid() getUserGuid()
 * @method string setUserGuid() setUserGuid(string $userGuid)
 * @method string getUserName() getUserName()
 * @method string setUserName() setUserName(string $userName)
 */
class editor_Models_Terminology_Models_TermModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_Term';

    /**
     * editor_Models_Terms_Term constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * $fullResult[$term['mid'].'-'.$term['groupId'].'-'.$term['collectionId']]
     * $fullResult['termId-termEntryId-collectionId'] = TERM
     *
     * $simpleResult[$term['term']]
     * $simpleResult['term'] = termId
     * @param int $collectionId
     * @return array[]
     */
    public function getAllTermsByCollectionId(int $collectionId): array
    {
        $simpleResult = [];
        $fullResult = [];

        $query = "SELECT * FROM terms_term WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        foreach ($queryResults as $key => $term) {
            $fullResult[$term['entryId'].'-'.$term['language'].'-'.$term['termId']] = $term;
        }

        return $fullResult;
    }

    /***
     * check if the term with the same termEntry,collection but different termId exist
     *
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getRestTermsOfTermEntry($groupId, $mid, $collectionId)
    {
        $s = $this->db->select()
            ->where('termEntryId = ?', $groupId)
            ->where('termId != ?', $mid)
            ->where('collectionId = ?',$collectionId);

        return $this->db->fetchAll($s);
    }

    public function createImportTbx(string $sqlParam, string $sqlFields, array $sqlValue)
    {
        $this->init();
        $insertTerms = rtrim($sqlParam, ',');

        $query = "INSERT INTO terms_term ($sqlFields) VALUES $insertTerms";

        return $this->db->getAdapter()->query($query, $sqlValue);
    }
    /**
     * @param array $terms
     * @return bool
     */
    public function updateImportTbx(array $terms): bool
    {
        foreach ($terms as $term) {
            $this->db->update($term, ['id=?'=> $term['id']]);
        }

        return true;
    }
}
