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
 * TermsTransacgrp Instance
 *
 * @method string getId()
 * @method void setId(integer $id)
 * @method string getName()
 * @method void setName(string $name)
 */
class editor_Models_Terminology_Models_TransacgrpPersonModel extends editor_Models_Terminology_Models_Abstract
{
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_TransacgrpPerson';

    /**
     * @return $this
     */
    public function loadOrCreateByName(string $name, $collectionId)
    {
        // Build select
        $s = $this->db->select()->from($this->db)->where('name = ?', $name)->where('collectionId = ?', $collectionId);

        // If found
        if ($row = $this->db->fetchRow($s)) {
            // Set row
            $this->row = $row;

            // Else
        } else {
            // Init
            $this->init([
                'name' => $name,
                'collectionId' => $collectionId,
            ]);

            // Save
            $this->save();
        }

        // Return self
        return $this;
    }

    /**
     * Load transacgrp persons by $collectionIds
     *
     * @return array
     */
    public function loadByCollectionIds(array $collectionIds)
    {
        if (! $collectionIds) {
            $collectionIds = [0];
        }
        $s = $this->db->select()->where('collectionId IN (?)', $collectionIds);

        return $this->loadFilterdCustom($s);
    }

    /**
     * Drop terms_transacgrp_person-records if not used anymore within `terms_term`.`tbx(Created|Updated)By` column
     */
    public function dropIfNotUsedAnymore(array $personIds)
    {
        // Foreach person id
        foreach ($personIds as $personId) {
            // Check if still used
            $isStillUsedPerson = $this->db->getAdapter()->query('
                SELECT `id` 
                FROM `terms_term` 
                WHERE ? IN (`tbxCreatedBy`, `tbxUpdatedBy`) 
                LIMIT 1
            ', $personId)->fetchColumn() ? true : false;

            // If still used - skip to next person id
            if ($isStillUsedPerson) {
                continue;
            }

            // Else delete it
            $this->load($personId);
            $this->delete();
        }
    }

    /**
     * Get array, containing lists of transacgrp-persons
     *
     * @throws Zend_Db_Statement_Exception
     */
    public function getDistinctStores(array $collectionIds): array
    {
        // Build WHERE clause for collectionId column
        $where = $this->db->getAdapter()->quoteInto('`collectionId` IN (?)', $collectionIds);

        // Get transacgrp person dictionary
        $tbxPersonA = $this->db->getAdapter()->query("
            SELECT GROUP_CONCAT(`id`) AS `ids`, `name` 
            FROM `terms_transacgrp_person` 
            WHERE $where
            GROUP BY `name`
        ")->fetchAll();

        // Setup combobox-recognizable data for tbxCreatedBy and tbxUpdatedBy filterWindow filters
        foreach (['tbxCreatedBy', 'tbxUpdatedBy'] as $prop) {
            $data[$prop] = $tbxPersonA;
        }

        // Return data
        return $data;
    }
}
