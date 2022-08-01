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
        if($this->checkTaskUsage($value) === false){
            $errorStr.= 'Unable to remove the server. It is already used by one of the tasks.';
            return false;
        }

        $this->updateServerUsedDefaults($value);
        $this->cleanUpNotUsed($value);

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
     * Remove non existing server values from client overwrites
     * @param string $value
     * @return void
     */
    private function cleanUpNotUsed(string $value = ''): void
    {
        /** @var editor_Models_Customer_CustomerConfig $config */
        $config = ZfExtended_Factory::get('editor_Models_Customer_CustomerConfig');
        $db = $config->db;

        $where = ['name = ? ' => 'runtimeOptions.plugins.Okapi.serverUsed'];

        $names = json_decode($value,true);

        if( !empty($names)){
            $where['value NOT IN (?)'] = array_keys($names);
        }
        // remove all serverUsed configs with non existing server values
        $db->delete($where);


        /** @var editor_Models_Config $config */
        $config = ZfExtended_Factory::get('editor_Models_Config');
        $config->loadByName('runtimeOptions.plugins.Okapi.serverUsed');

        // if the new values are not valid for serverUsed config (instance level) -> remove the current value from there
        if( !in_array($config->getValue(), array_keys($names))){
            $config->setValue('');
            $config->save();
        }
    }

    /***
     * Check if the removed config is used from the tasks. If yes, this action is not allowed. We can not remove
     * used config name/server.
     * @return false
     */
    private function checkTaskUsage(string $value = ''){
        /** @var editor_Models_TaskConfig $config */
        $config = ZfExtended_Factory::get('editor_Models_TaskConfig');
        $db = $config->db;

        $names = json_decode($value,true);

        $s = $db->select()
            ->where('name = ?','runtimeOptions.plugins.Okapi.serverUsed');

        if( !empty($names)){
            $s->where('value NOT IN (?)',array_keys($names));
        }

        // if result has values this means the removed config is used for one of the existing tasks
        $result = $db->getAdapter()->fetchAll($s);

        return empty($result);
    }

}
