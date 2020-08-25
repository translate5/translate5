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
class editor_Models_Config extends ZfExtended_Models_Config {

    const CONFIG_SOURCE_DB   = "db";
    const CONFIG_SOURCE_INI  = "ini";
    const CONFIG_SOURCE_USER = "user";
    
    const CONFIG_LEVEL_SYSTEM   = 1;
    const CONFIG_LEVEL_INSTANCE = 2;
    const CONFIG_LEVEL_CUSTOMER = 4;
    const CONFIG_LEVEL_TASK     = 8;
    const CONFIG_LEVEL_USER     = 16;
    
    // system 1 (default), instance 2, customer 4, task 8 , user 16
    protected $configLabel=[
        self::CONFIG_LEVEL_SYSTEM   => 'system',
        self::CONFIG_LEVEL_INSTANCE => 'instance',
        self::CONFIG_LEVEL_CUSTOMER => 'customer',
        self::CONFIG_LEVEL_TASK     => 'task',
        self::CONFIG_LEVEL_USER     => 'user'
    ];
    
    /***
     * Load all zf configuration values merged with the user config values and installation.ini vaues. The user config value will
     * override the zf confuguration/ini (default) values.
     * Config level and user role map:
     *
     *  CONFIG_LEVEL_SYSTEM=1;    //system configuration.
     *  CONFIG_LEVEL_INSTANCE=2;  //(zf_configuration properties) API and ADMIN user ↓
     *  CONFIG_LEVEL_CUSTOMER=4;  //customer configuration
     *  CONFIG_LEVEL_TASK=8;      //task configuration PM Users           ↓
     *  CONFIG_LEVEL_USER=16;     //user configuration. State fields and user custom configuration. ALL other Users   ↓
     *
     * @param ZfExtended_Models_User $user
     * @return array
     */
    public function loadAllMerged(ZfExtended_Models_User $user){
        //get all application config level for the user
        $userLevelStrings = $user->getApplicationConfigLevel();
        
        $userLevelInt = array_sum(array_unique(array_map([$this, 'convertStringLevelToInt'], $userLevelStrings)));
        
        $s = $this->db->select()
        ->from('Zf_configuration')
        ->where('level & ? > 0', $userLevelInt);
        $dbResults = $this->loadFilterdCustom($s);

        //merge the ini with zfconfig values
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();
        $dbResultsNamed = [];
        foreach($dbResults as &$row) {
            $this->mergeWithIni($iniOptions, explode('.', $row['name']), $row);
            $dbResultsNamed[$row['name']] = $row;
        }
        
        //TODO enable possibility so that a admin is able to load only the DB values, without the merged user config
        //     since the same is needed for saving, we can use here the same parameter, for example targetLevel
        //merge user config and ensure that result is an array and not a object map
        return array_values($this->mergeUserValues($user, $dbResultsNamed));
    }
    
    /**
     * overrides the DB config values from the user config
     * @param ZfExtended_Models_User $user
     * @param array $dbResults
     * @return array
     */
    protected function mergeUserValues(ZfExtended_Models_User $user, array $dbResults): array {
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from('LEK_user_config')
        ->where('userGuid = ?',$user->getUserGuid());
        $userResults = $this->db->getAdapter()->fetchAll($s);
        foreach($userResults as $row) {
            if(!empty($dbResults[$row['name']])) {
                $row['overwritten'] = $row['value'];
                $dbResults[$row['name']]['overwritten'] = $dbResults[$row['name']]['value'];
                $dbResults[$row['name']]['value'] = $row['value'];
                $dbResults[$row['name']]['origin'] = self::CONFIG_SOURCE_USER;
            }
        }
        return $dbResults;
    }
    
    /**
     * Returns the level integer value to a named level value
     * @param string $level
     * @return int
     */
    protected function convertStringLevelToInt(string $level): int {
        $const = 'self::CONFIG_LEVEL_'.strtoupper($level);
        if(defined($const)) {
            return constant($const);
        }
        return 0;
    }
    
    /***
     * Update the user config and the default zf_config value for given user and config name
     * @param ZfExtended_Models_User $user
     * @param string $configName
     * @param string $configValue
     */
    public function updateConfig(ZfExtended_Models_User $user,string $configName,string $configValue){
        /* @var $acl ZfExtended_Acl */
        //Info: uncomment this when the frontend config managment will be available.
        //from the frontend, an level is required also as additional param, so the decision here can be made
        //where and for who the config will be saved
//         if($acl->isInAllowedRoles($user->getRoles(),'stateconfig',$this->configLabel[self::CONFIG_LEVEL_SYSTEM])){
//             //$this->update($configName, $configValue);
//         }
        
        $acl = ZfExtended_Acl::getInstance();
        //update the user config if the current user is allowed
        if($acl->isInAllowedRoles($user->getRoles(),ZfExtended_Models_User::APPLICATION_CONFIG_LEVEL,$this->configLabel[self::CONFIG_LEVEL_USER])){
            $userConfig=ZfExtended_Factory::get('editor_Models_UserConfig');
            /* @var $userConfig editor_Models_UserConfig */
            $userConfig->updateInsertConfig($user->getUserGuid(),$configName,$configValue);
        }
    }
    
    
    /**
     * Merges the ini config values into the DB result
     * @param array $root
     * @param array $path
     * @param array $row given as reference, the ini values are set in here
     */
    protected function mergeWithIni(array $root, array $path, array &$row) {
        $row['origin'] = $row['origin'] ?? editor_Models_Config::CONFIG_SOURCE_DB;
        $part = array_shift($path);
        if(!isset($root[$part])) {
            return;
        }
        if(!empty($path)){
            $this->mergeWithIni($root[$part], $path, $row);
            return;
        }
        $row['origin'] = editor_Models_Config::CONFIG_SOURCE_INI;
        $row['overwritten'] = $row['value'];
        $row['value'] = $root[$part];
        if($row['type'] == ZfExtended_Resource_DbConfig::TYPE_MAP || $row['type'] == ZfExtended_Resource_DbConfig::TYPE_LIST){
            $row['value'] = json_encode($row['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * @param string $filter
     * @return array values are all constant values which names match filter
     */
    public function getFilteredConstants(string $filter){
        $refl = new ReflectionClass($this);
        $consts = $refl->getConstants();
        $filtered = array();
        foreach ($consts as $const => $val) {
            if(strpos($const, $filter)!==FALSE){
                $filtered[$const] = $val;
            }
        }
        return $filtered;
    }
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Models_Config::loadListByNamePart()
     */
    public function loadListByNamePart(string $name) {
        $result = parent::loadListByNamePart($name);
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();
        foreach($result as &$row) {
            $this->mergeWithIni($iniOptions, explode('.', $row['name']), $row);
        }
        return $result;
    }
}
