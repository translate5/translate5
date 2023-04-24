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

use MittagQI\Translate5\Plugins\Okapi\ConfigMaintenance;
use MittagQI\Translate5\Plugins\Okapi\Service;

/**
 * Contains the config handler for core types
 */
class editor_Plugins_Okapi_DbConfig_OkapiConfigType extends ZfExtended_DbConfig_Type_CoreTypes
{
    /**
     * returns the GUI view class to be used or null for default handling
     * @return string|null
     */
    public function getGuiViewCls(): ?string
    {
        return 'Editor.plugins.Okapi.view.UrlConfig';
    }

    /**
     * @throws Zend_Db_Select_Exception
     */
    public function validateValue(editor_Models_Config $config, &$newvalue, ?string &$errorStr): bool
    {
        $rawType = parent::validateValue($config, $newvalue, $errorStr);

        // if the raw type is not correct fail validation
        if (!$rawType) {
            return false;
        }
        $okapiConfig = ZfExtended_Factory::get(ConfigMaintenance::class);
        $newServerList = $okapiConfig->serverListFromJson($newvalue);
        $oldServerList = $okapiConfig->serverListFromJson($config->getValue());
        $serverList = [];

        // validate the sent servers
        foreach($newServerList as $serverKey => $serverUrl){
            // if an entry is not identically in the old list is has changed
            if(array_key_exists($serverKey, $oldServerList) && $oldServerList[$serverKey] === $serverUrl){
                // unchanged keys will be accepted
                $serverList[$serverKey] = $serverUrl;
            } else {
                // new entries will be checked. If the version cannot be fetched, the service is not reachable ...
                $versionString = Service::fetchServerVersion($serverUrl);
                if ($versionString === null) {
                    $errorStr .= ' Url "' . $serverUrl . '" of entry "' . $serverKey . '" is not valid.';
                    return false;
                }
                $serverList[Service::createServerKey($versionString)] = $serverUrl;
            }
        }

        $removedServers = array_diff(array_keys($oldServerList), array_keys($serverList));

        if (($count = $okapiConfig->countTaskUsageSum($removedServers)) > 0) {
            $errorStr .= ' Unable to remove the server. It is already used by '.$count.' task(s).';
            return false;
        }

        try {
            $okapiConfig->updateServerUsedDefaults($serverList);
            $okapiConfig->cleanUpNotUsed($serverList);
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint | Zend_Db_Statement_Exception) {
            return false; //if config could not be updated, assume false here
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey) {
            //do nothing, and return below true since all is fine, if config exists already
        }

        // crucial: the new value must be overwritten in the reference
        $newvalue = json_encode($serverList);

        return true;
    }
}
