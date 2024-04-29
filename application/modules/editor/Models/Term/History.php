<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getTermId()
 * @method void setTermId(integer $termId)
 * @method string getCollectionId()
 * @method void setCollectionId(integer $collectionId)
 * @method string getTermEntryId()
 * @method void setTermEntryId(integer $termEntryId)
 * @method string getLanguageId()
 * @method void setLanguageId(integer $languageId)
 * @method string getLanguage()
 * @method void setLanguage(string $language)
 * @method string getTerm()
 * @method void setTerm(string $term)
 * @method string getProposal()
 * @method void setProposal(string $proposal)
 * @method string getStatus()
 * @method void setStatus(string $status)
 * @method string getProcessStatus()
 * @method void setProcessStatus(string $processStatus)
 * @method string getUpdatedBy()
 * @method void setUpdatedBy(integer $updatedBy)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 * @method string getDefinition()
 * @method void setDefinition(string $term)
 * @method string getTermEntryTbxId()
 * @method void setTermEntryTbxId(string $termEntryTbxId)
 * @method string getTermTbxId()
 * @method void setTermTbxId(string $termTbxId)
 * @method string getTermEntryGuid()
 * @method void setTermEntryGuid(string $termEntryGuid)
 * @method string getLangSetGuid()
 * @method void setLangSetGuid(string $langSetGuid)
 * @method string getGuid()
 * @method void setGuid(string $guid)
 */
class editor_Models_Term_History extends ZfExtended_Models_Entity_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Term_History';

    /**
     * Get array of history-records for a given term id
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getByTermId(int $termId): array
    {
        // Fetch history
        $history = $this->db->getAdapter()->query('
            SELECT 
              `h`.`id`, 
              `h`.`language`,
              `h`.`term`,
              `h`.`proposal`, 
              `h`.`status`, 
              IF(LENGTH(`h`.`proposal`), "unprocessed", `h`.`processStatus`) AS `processStatus`, 
              `h`.`updatedAt`, 
              CONCAT(`u`.`firstName`, " ", `u`.`surName`) AS `updatedBy`
            FROM 
              `terms_term_history` `h`
              LEFT JOIN `Zf_users` `u` ON `h`.`updatedBy` = `u`.`id`
            WHERE `h`.`termId` = ?
            ORDER BY `h`.`updatedAt` DESC
        ', $termId)->fetchAll();

        // Load term model instance
        $term = ZfExtended_Factory::get(editor_Models_Terminology_Models_TermModel::class);
        $term->load($termId);

        // Get user who last updated that attribute
        try {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->load($term->getUpdatedBy());
            $updatedBy = $user->getFirstName() . ' ' . $user->getSurName();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $updatedBy = null;
        }

        // Prepend current state as a most recent record into the history
        array_unshift($history, [
            'id' => 0,
            'language' => $term->getLanguage(),
            'term' => $term->getTerm(),
            'proposal' => $term->getProposal(),
            'status' => $term->getStatus(),
            'processStatus' => $term->getProposal() ? 'unprocessed' : $term->getProcessStatus(),
            'updatedAt' => $term->getUpdatedAt(),
            'updatedBy' => $updatedBy,
        ]);

        // Return history including current state
        return $history;
    }
}
