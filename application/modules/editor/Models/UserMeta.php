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
 * User meta Entity Object
 * @method integer getId() getId()
 * @method void setId() setId(int $id)
 * @method integer getUserId() getUserId()
 * @method void setUserId() setUserId(int $userId)
 * @method integer getSourceLangDefault() getSourceLangDefault()
 * @method void setSourceLangDefault() setSourceLangDefault(int $sourceLangDefault)
 * @method integer getTargetLangDefault() getTargetLangDefault()
 * @method void setTargetLangDefault() setTargetLangDefault(int $targetLangDefault)
 * @method string getLastUsedApp() getLastUsedApp()
 * @method void setLastUsedApp() setLastUsedApp(string $lastUsedApp)
 */

class editor_Models_UserMeta extends ZfExtended_Models_Entity_Abstract {
    protected $dbInstanceClass = 'editor_Models_Db_UserMeta';
    protected $validatorInstanceClass = 'editor_Models_Validator_UserMeta';
    
    
    
    /***
     * Load or init user meta model
     * @param int $userId
     */
    public  function loadOrSet(int $userId){
        try {
            $this->loadByUser($userId);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            parent::init();
            $this->setUserId($userId);
        }
    }
    
    /***
     * Load model for the given user
     * @param int $userId
     */
    public function loadByUser($userId){
        $this->loadRow('userId=?',$userId);
    }
    
    
    /***
     * Save the default languages for the given user.
     * When the record for the user exist, it will be update with the new values.
     * 
     * @param int $userId
     * @param int $source
     * @param int $target
     * @return mixed|array
     */
    public function saveDefaultLanguages($userId,$source,$target){
        $this->loadOrSet($userId);
        $this->setSourceLangDefault($source);
        $this->setTargetLangDefault($target);
        return $this->save();
    }
    
    /***
     * Save last used app for the given user
     * @param int $userId
     * @param string $appName
     * @return mixed|array
     */
    public function saveLastUsedApp(int $userId,string $appName){
        $this->loadOrSet($userId);
        $this->setLastUsedApp($appName);
        return $this->save();
    }
    
}