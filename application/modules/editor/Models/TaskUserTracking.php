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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * TaskUserTracking Object Instance as needed in the application
 * 
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $guid)
 * @method integer getTaskOpenerNumber() getTaskOpenerNumber()
 * @method void setTaskOpenerNumber() setTaskOpenerNumber(int $id)
 * @method string getFirstName() getFirstName()
 * @method void setFirstName() setFirstName(string $guid)
 * @method string getSurName() getSurName()
 * @method void setSurName() setSurName(string $guid)
 * @method string getRole() getRole()
 * @method void setRole() setRole(string $guid)
 * 
 */
class editor_Models_TaskUserTracking extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_TaskUserTracking';
    protected $validatorInstanceClass = 'editor_Models_Validator_TaskUserTracking';
    
    /**
     * load all TaskUserTracking entries to one task
     * @param int $id
     * @return array|null
     */
    public function loadByTaskGuid($taskGuid) {
        try {
            $s = $this->db->select()->where('taskGuid = ?', $taskGuid);
            return $this->db->fetchAll($s)->toArray();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Anonymize all user-related data by keys in $data (= User1, User2, ...etc) 
     * @param array $data
     * @return array
     */
    public function anonymizeUserdata (array $data) {
        $keysToAnonymize = ['firstName','lockingUser','lockingUsername','login','userGuid','userName','surName'];
        array_walk($data, function( &$value, $key) use ($keysToAnonymize) {
            if (in_array($key, $keysToAnonymize)) {
                // TODO: get data from tracking-table
                $value = 'xyz';
            }
        });
        return $data;
    }
}