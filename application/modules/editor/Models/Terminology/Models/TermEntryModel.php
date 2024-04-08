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
 * Class editor_Models_Terms_Term_Entry
 * TermsTermEntry Instance
 *
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getCollectionId()
 * @method void setCollectionId(integer $collectionId)
 * @method string getTermEntryTbxId()
 * @method void setTermEntryTbxId(string $termEntryTbxId)
 * @method string getIsCreatedLocally()
 * @method void setIsCreatedLocally(string $isCreatedLocally)
 * @method string getEntryGuid()
 * @method void setEntryGuid(string $uniqueId)
 */
class editor_Models_Terminology_Models_TermEntryModel extends editor_Models_Terminology_Models_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_TermEntry';

    public function insert($misc = []) {

        // Save and get insert id
        $termEntryId = $this->save();

        // Create 'origination' and 'modification' `terms_transacgroup`-entries
        foreach (['origination', 'modification'] as $type) {

            // Create `terms_transacgrp` model instance
            $t = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel');

            // Setup data
            $t->init([
                'elementName' => 'termEntry',
                'transac' => $type,
                'date' => date('Y-m-d H:i:s'),
                'transacNote' => $misc['userName'],
                'transacType' => 'responsibility',
                'collectionId' => $this->getCollectionId(),
                'termEntryId' => $termEntryId,
                'termEntryGuid' => $this->getEntryGuid(),
                'guid' => ZfExtended_Utils::uuid(),
            ]);

            // Save `terms_transacgrp` entry
            $t->save();
        }

        // Return
        return $termEntryId;
    }

    /**
     * Delete termEntry and refresh collection's languages
     */
    public function delete() {

        // Remember collectionId
        $collectionId = $this->getCollectionId();

        // Call parent
        parent::delete();

        // Remove old language assocs
        ZfExtended_Factory
            ::get('editor_Models_LanguageResources_Languages')
            ->removeByResourceId([$collectionId]);

        // Add the new language assocs
        ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_TermModel')
            ->updateAssocLanguages([$collectionId]);
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


        /*$sql='SELECT id FROM LEK_term_entry WHERE LEK_term_entry.groupId NOT IN (
                SELECT LEK_term_entry.groupId from LEK_term_entry
                JOIN LEK_terms USING(groupId)
                WHERE LEK_terms.collectionId=LEK_term_entry.collectionId
                      AND LEK_terms.collectionId in (?)
                GROUP BY LEK_term_entry.groupId
            ) AND collectionId in (?)';*/

        $sql = '
            SELECT 
              `te`.`id`,
              COUNT(`t`.`id`) AS `qty`
            FROM
              `terms_term_entry` `te`
              LEFT JOIN `terms_term` `t` ON (`te`.`id` = `t`.`termEntryId`)
            WHERE `te`.`collectionId` IN (' . $collectionIds . ')
            GROUP BY `te`.`id`
            HAVING `qty` = "0"';

        $toRemove = $this->db->getAdapter()->query($sql)->fetchAll(PDO::FETCH_COLUMN);

        if(empty($toRemove)){
            return false;
        }

        return $this->db->delete([
            'id IN (?)' => $toRemove,
            'collectionId IN (?)' => explode(',', $collectionIds),
        ]) > 0;
    }

    /**
     * Get termEntry-recors quantity per given collectionId
     *
     * @param int $collectionId
     * @return string
     * @throws Zend_Db_Statement_Exception
     */
    public function getQtyByCollectionId(int $collectionId) {
        return $this->db->getAdapter()->query('
            SELECT COUNT(*) FROM `terms_term_entry` WHERE `collectionId` = ?'
        , $collectionId)->fetchColumn();
    }
}
