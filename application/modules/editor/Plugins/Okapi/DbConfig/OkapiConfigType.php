<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
			 https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Contains the config handler for core types
 */
class editor_Plugins_Okapi_DbConfig_OkapiConfigType extends ZfExtended_DbConfig_Type_CoreTypes {
    /**
     * returns the GUI view class to be used or null for default handling
     * @return string|null
     */
    public function getGuiViewCls(): ?string {
        return 'Editor.plugins.Okapi.view.UrlConfig';
    }

    public function validateValue(string $type, &$value, ?string &$errorStr): bool
    {
        $rawType = parent::validateValue($type, $value, $errorStr);

        // if the raw type is not correct fail validation
        if(!$rawType) {
            return false;
        }
        if($this->checkTaskUsage() === false){
            //TODO: better messge
            $errorStr.= 'Unable to remove the server. It is already used by one of the tasks.';
            return false;
        }

        $this->updateServerUsedDefaults($value);

        return true;
    }

    /***
     * Update server used config defaults when new server is added (runtimeOptions.plugins.Okapi.server)
     * @param string $value
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function updateServerUsedDefaults(string $value = ''){
        if( empty($value)){
            return;
        }
        $configUrls = json_decode($value,true);
        $defaults = implode(',',array_keys($configUrls));

        /** @var editor_Models_Config $config */
        $config = ZfExtended_Factory::get('editor_Models_Config');
        $config->loadByName('runtimeOptions.plugins.Okapi.serverUsed');

        $config->setDefaults($defaults);
        $config->save();
    }

    /***
     * Check if the removed config is used from the tasks. If yes, this action is not allowed. We can not remove
     * used config name/server.
     * @return false
     */
    private function checkTaskUsage(){
        //TODO: validate if there is removed url/route from the config and if the removed one is in use.
        // If it is in use (one of the tasks uses the removed okapi url/route as import),
        // throw exception so the user knows what is t
        return false;
    }

}
