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

class editor_Models_TermCollection_TermEntry extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TermCollection_TermEntry';
    protected $validatorInstanceClass   = 'editor_Models_Validator_TermCollection_TermEntry';
    
    
    /***
     * Get term entry by given collectionId and groupId (termEntry tbx id) 
     * @param integer $termEntryId
     * @param integer $collectionId
     * @return Zend_Db_Table_Row_Abstract|NULL
     */
    public function getTermEntryByIdAndCollection($termEntryId,$collectionId){
        $s = $this->db->select()
        ->where('groupId = ?', $termEntryId)
        ->where('collectionId = ?', $collectionId);
        return $this->db->fetchRow($s);
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
    
    
    /**
     * Remove term entry older than $olderThan date from a specific term collection
     * The date format should be equivalent to mysql date format 'YYYY-MM-DD HH:MM:SS'
     * 
     * @param integer $collectionId
     * @param string $olderThan
     * @return boolean : true if rows are removed
     */
    public function removeOlderThan($collectionId, $olderThan){
        //find all modefied entries older than $olderThan date
        //the query will find the lates modefied term entry attribute, if the term entry attribute update date is older than $olderThan, remove the termEntry
        $collectionId = (int) $collectionId;
        return $this->db->delete(['id IN (SELECT t.termEntryId
            	FROM LEK_term_entry_attributes t
            	INNER JOIN (SELECT termEntryId, MAX(updated) as MaxDate FROM LEK_term_entry_attributes WHERE collectionId = '.$collectionId.' GROUP BY termEntryId)
            	tm ON t.termEntryId = tm.termEntryId AND t.updated = tm.MaxDate
            	WHERE t.collectionId = '.$collectionId.' AND t.updated < ?
            	GROUP BY t.termEntryId)'=>$olderThan])>0;
    }
}