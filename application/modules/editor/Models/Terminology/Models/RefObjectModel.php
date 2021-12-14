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
 * @method string getKey() getKey()
 * @method void setKey() setKey(string $key)
 */
class editor_Models_Terminology_Models_RefObjectModel extends editor_Models_Terminology_Models_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_Terminology_RefObject';

    public function setData(stdClass $data) {
        $this->set('data', json_encode($data));
    }

    public function getData(): string {
        return json_decode($this->get('data'));
    }

    /**
     * Get export data
     *
     * @param int $collectionId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getExportData(int $collectionId) {

        // Get distinct transacgrp's targets (some of them may be userGuid-s)
        $targets = '"' . join('","', $this->db->getAdapter()->query('
            SELECT DISTINCT `target` FROM `terms_transacgrp` WHERE `collectionId` = ?        
        ', $collectionId)->fetchAll(PDO::FETCH_COLUMN)) . '"';

        // Get refObject data lists saved during the tbx import
        $refObjectListA = $this->db->getAdapter()->query('
            SELECT `listType`, `key`, `data` 
            FROM `terms_ref_object` 
            WHERE TRUE 
              AND `collectionId` = ?
              AND (`listType` != "respPerson" OR `key` IN (' . $targets . '))
        ', $collectionId)->fetchAll(PDO::FETCH_GROUP);

        // Get respPerson-data logged during termportal usage (after tbx import)
        $respPerson = $this->db->getAdapter()->query('
            SELECT 
               `userGuid` AS `key`, 
               JSON_OBJECT("fn", CONCAT(`firstName`, " ", `surName`), "email", `email`, "role", `roles`) AS `data` 
            FROM `Zf_users`
            WHERE `userGuid` IN (' . $targets . ')
        ')->fetchAll();

        // Append data to the respPerson-list
        $refObjectListA['respPerson'] = array_merge($refObjectListA['respPerson'] ?? [], $respPerson);

        // Return ref object lists
        return $refObjectListA;
    }

    /**
     * Get emails for excel export by collectionId
     *
     * @param $collectionId
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function getEmailsByCollectionId($collectionId) {

        // Fetch emails from `terms_ref_object` table
        $emails = $this->db->getAdapter()->query('
            SELECT `key`, JSON_UNQUOTE(JSON_EXTRACT(`data`, "$.email")) FROM `terms_ref_object` WHERE `collectionId` = ?
        ', $collectionId)->fetchAll(PDO::FETCH_KEY_PAIR);

        // Append [userGuid => email] pairs from Zf_users
        $emails += $this->db->getAdapter()->query('
            SELECT `userGuid`, `email` FROM `Zf_users`
        ')->fetchAll(PDO::FETCH_KEY_PAIR);

        // Return emails
        return $emails;
    }
}
