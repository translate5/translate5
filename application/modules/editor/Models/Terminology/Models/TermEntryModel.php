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
 * @method string getTermEntryTbxId() getTermEntryTbxId()
 * @method string setTermEntryTbxId() setTermEntryTbxId(string $termEntryTbxId)
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
        $query = "SELECT id, collectionId, termEntryTbxId, descrip, isProposal, entryGuid FROM terms_term_entry WHERE collectionId = :collectionId";
        $queryResults = $this->db->getAdapter()->query($query, ['collectionId' => $collectionId]);

        $simpleResult = [];
        foreach ($queryResults as $key => $termEntry) {
            $simpleResult[$termEntry['collectionId'].'-'.$termEntry['termEntryTbxId']] =
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

    /***
     * Remove empty term entries (term entries without any term in it).
     * Only the empty term entries from the same term collection will be removed.
     * @param array $collectionIds
     * @return boolean
     */
    public function removeEmptyFromCollection(array $collectionIds){
        $collectionIds = join(',', array_map(function($i){
            return (int) $i;
        }, $collectionIds));
        $sql='SELECT id FROM LEK_term_entry WHERE LEK_term_entry.groupId NOT IN (
                SELECT LEK_term_entry.groupId from LEK_term_entry
                JOIN LEK_terms USING(groupId)
                WHERE LEK_terms.collectionId=LEK_term_entry.collectionId
                      AND LEK_terms.collectionId in (?)
                GROUP BY LEK_term_entry.groupId
            ) AND collectionId in (?)';
        $toRemove=$this->db->getAdapter()->query($sql, [$collectionIds, $collectionIds])->fetchAll();

        if(empty($toRemove)){
            return false;
        }

        return $this->db->delete([
                'id IN (?)'=>$toRemove,
                'collectionId IN (?)'=>$collectionIds,
            ])>0;
    }

    /***
     * Remove empty term entry from the database. Empty term entry is term entry without terms in it.
     * @param int $termEntryId
     */
    public function deleteEmptyTermEntry(int $termEntryId)
    {
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from(['te'=>'terms_term_entry'])
            ->join(['t'=>'terms_term'],'t.termEntryId = te.id')
            ->where('te.id = ?',$termEntryId);
        $result=$this->db->fetchAll($s)->toArray();

        if (!empty($result)) {
            return;
        }
        $this->db->delete([
            'id IN (?)' => $termEntryId
        ]);
    }

    /**
     * Remove term entry older than $olderThan date from a specific term collection
     * The date format should be equivalent to mysql date format 'YYYY-MM-DD HH:MM:SS'
     *
     * @param int $collectionId
     * @param string $olderThan
     * @param boolean $removeProposal
     * @return boolean : true if rows are removed
     */
    public function removeOlderThan($collectionId, $olderThan, $removeProposal = false)
    {
        //find all modefied entries older than $olderThan date
        //the query will find the lates modefied term entry attribute, if the term entry attribute update date is older than $olderThan, remove the termEntry
        $collectionId = (int) $collectionId;
        $entryType=[0];
        if ($removeProposal) {
            $entryType[] = 1;
        }
        //FIXME testen ob die methode das macht was sie soll
        return $this->db->delete([' isProposal IN('.implode(',', $entryType).') AND id IN (SELECT t.termEntryId
            	FROM terms_attributes t
            	INNER JOIN (SELECT termEntryId, MAX(updated) as MaxDate FROM terms_attributes WHERE termId is null AND collectionId = '.$collectionId.' GROUP BY termEntryId)
            	tm ON t.termEntryId = tm.termEntryId AND t.updated = tm.MaxDate
            	WHERE t.termId is null AND t.collectionId = '.$collectionId.' AND t.updated < ?
            	GROUP BY t.termEntryId)'=>$olderThan])>0;
    }
}
