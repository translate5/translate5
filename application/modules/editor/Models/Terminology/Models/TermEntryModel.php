<?php

use Doctrine\DBAL\Exception;

/**
 * Class editor_Models_Terms_Term_Entry
 * TermsTermEntry Instance
 *
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method integer getCollectionId() getCollectionId()
 * @method integer setCollectionId() setCollectionId(integer $collectionId)
 * @method string getTermEntryId() getTermEntryId()
 * @method string setTermEntryId() setTermEntryId(string $termEntryId)
 * @method string getIsProposal() getIsProposal()
 * @method string setIsProposal() setIsProposal(string $isProposal)
 * @method string getDescrip() getDescrip()
 * @method string setDescrip() setDescrip(string $descrip)
 * @method string getEntryGuid() getEntryGuid()
 * @method string setEntryGuid() setEntryGuid(string $uniqueId)
 */
class editor_Models_Terminology_Models_TermEntryModel extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_TermEntry';

    /**
     * editor_Models_Terms_Term_Entry constructor.
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * groupId = termEntryId
     * collectionId = LEK_languageresources->id
     * @param $collectionId
     * @return array
     */
    public function getAllTermEntryAndCollection($collectionId): array
    {
        $query = "SELECT id, collectionId, termEntryId, descrip, isProposal, entryGuid FROM terms_term_entry WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        $simpleResult = [];
        foreach ($queryResults as $key => $termEntry) {
            $simpleResult[$termEntry['collectionId'].'-'.$termEntry['termEntryId']] =
                $termEntry['id'].'-'.$termEntry['descrip'].'-'.$termEntry['entryGuid'];
        }

        return $simpleResult;
    }

    /***
     * Create a term entry record in the database, for the current collection and the
     * actual termEntryId
     * @param array $termEntry
     */
    public function updateTermEntryRecord(array $termEntry)
    {
        $this->load($termEntry['id']);
        $this->setDescrip($termEntry['descrip']);
        $this->setEntryGuid($termEntry['entryGuid']);
        $id = $this->save();
    }
}
