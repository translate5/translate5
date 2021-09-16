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
 */
class editor_Models_Terminology_Models_Abstract extends ZfExtended_Models_Entity_Abstract
{
    /**
     * @param string $bindings
     * @param array $fields
     * @param array $data
     * @return Zend_Db_Statement
     * @throws Zend_Db_Table_Exception
     */
    public function createImportTbx(string $bindings, array $fields, array $data): Zend_Db_Statement
    {
        $query = 'INSERT INTO '.$this->db->info($this->db::NAME).' ('.join(',', $fields).') VALUES '.$bindings;
        return $this->db->getAdapter()->query($query, $data);
    }

    /**
     * @param array $attributes
     * @return bool
     */
    public function updateImportTbx(array $attributes): bool
    {
        //TODO: Question for Thomas, why not building the sql and exec in one query ?
        foreach ($attributes as $attribute) {
            $this->db->update($attribute, ['id=?'=> $attribute['id']]);
        }
        return true;
    }
}
