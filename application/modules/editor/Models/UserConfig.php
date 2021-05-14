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
 * @method void setId() setId(int $id)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $userGuid)
 * @method string getName() getName()
 * @method void setName() setName(string $name)
 * @method string getValue() getValue()
 * @method void setValue() setValue(string $value)
 */
class editor_Models_UserConfig extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_UserConfig';
    protected $validatorInstanceClass = 'editor_Models_Validator_UserConfig';
    
    /***
     * Update or insert new config for given user
     *
     * @param string $userGuid
     * @param string $name
     * @param string $value
     * @return number
     */
    public function updateInsertConfig(string $userGuid,string $name,string $value) {
        $sql="INSERT INTO LEK_user_config(userGuid,name,value) ".
        " VALUES (?,?,?) ".
        " ON DUPLICATE KEY UPDATE value = ? ";
        return $this->db->getAdapter()->query($sql,[$userGuid,$name,$value,$value]);
    }
    
    /**
     * returns a specific config value for a specific user
     * @param string $userGuid
     * @param string $name
     * @return string|NULL
     */
    public function getCurrentValue(string $userGuid, string $name): ?string {
        try {
            $s = $this->db->select()
            ->where('userGuid = ?', $userGuid)
            ->where('name = ?', $name);
            $row = $this->db->fetchRow($s);
        } catch (Exception $e) {
            return null;
        }
        if (!$row) {
            return null;
        }
        return $row['value'];
    }
}