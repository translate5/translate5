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
 * TODO: config validator. It needs to check if the field is requeired or not 
 *
 */
class editor_Models_Config extends ZfExtended_Models_Config {

    const CONFIG_SOURCE_DB   = "db";
    const CONFIG_SOURCE_INI  = "ini";
    const CONFIG_SOURCE_USER = "user";
    
    const CONFIG_LEVEL_SYSTEM   = 1;
    const CONFIG_LEVEL_INSTANCE = 2;
    const CONFIG_LEVEL_CUSTOMER = 4;
    //const CONFIG_LEVEL_TASK_IMPORT = 8;//TODO: 2 different levels for task. When the task is in import, this config can be modefied, After the task is imported, this config can not be changed,
    //const CONFIG_LEVEL_TASK = 16;//TODO: this config can be modefied any time on task stage
    const CONFIG_LEVEL_TASK     = 8;
    const CONFIG_LEVEL_USER     = 16;
    
    // system 1 (default), instance 2, customer 4, task 8 , user 16
    protected $configLabel=[
        self::CONFIG_LEVEL_SYSTEM   => 'system',//TODO: the system confi should go as constant in the code and not overritable at all or listed in zf configuration
        self::CONFIG_LEVEL_INSTANCE => 'instance',
        self::CONFIG_LEVEL_CUSTOMER => 'customer',
        self::CONFIG_LEVEL_TASK     => 'task',
        self::CONFIG_LEVEL_USER     => 'user'
    ];
    
    /***
     * Load configs fron the database by given level and merge those configs with .ini overrides.
     * @param int $level
     * @throws ZfExtended_ErrorCodeException
     * @return array[]
     */
    public function loadByLevel(int $level) {
        
//TODO: this will validate the level agains the user alowed level. But do we need to do this ?
//since i think this is only needed when we try to save config. But for loading everyone should be able to read the 
//customer/user/client specific configs

//         $userSession = new Zend_Session_Namespace('user');
        
//         $user = ZfExtended_Factory::get('ZfExtended_Models_User');
//         /* @var $user ZfExtended_Models_User */
//         $user->load($userSession->data->id);
//         $userLevelStrings = $user->getApplicationConfigLevel();
//         $userLevelInt = array_unique(array_map([$this, 'convertStringLevelToInt'], $userLevelStrings));
//         if(!in_array($level, $userLevelInt)){
//             //TODO: the user is not alowed to load this kind of level
//             throw new ZfExtended_ErrorCodeException();
//         }
        
        $s = $this->db->select()
        ->from('Zf_configuration')
        ->where('level = ? ', $level);
        $dbResults = $this->loadFilterdCustom($s);
        
        //merge the ini with zfconfig values
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();
        
        $dbResultsNamed = [];
        foreach($dbResults as &$row) {
            $this->mergeWithIni($iniOptions, explode('.', $row['name']), $row);
            $dbResultsNamed[$row['name']] = $row;
        }
        return $dbResultsNamed;
    }
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
     * @param string $nameFilter optional config name filter, applied with like (% must be provided in $nameFilter as desired)
     * @return array
     */
    //TODO: fix this function. This should load all configs merged with ini based on $userLevelInt and filter as name
    //remove the user merge from bellow, and check if the user merge is required for this function in one of the calls
    public function loadAllMerged(string $nameFilter = null){
        
        $userSession = new Zend_Session_Namespace('user');
        
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->load($userSession->data->id);
        
        //get all application config level for the user
        $userLevelStrings = $user->getApplicationConfigLevel();
        
        $userLevelInt = array_sum(array_unique(array_map([$this, 'convertStringLevelToInt'], $userLevelStrings)));
        
        $s = $this->db->select()
        ->from('Zf_configuration')
        ->where('level & ? > 0', $userLevelInt);
        if(!empty($nameFilter)) {
            $s->where('name like ?', $nameFilter);
        }
        $dbResults = $this->loadFilterdCustom($s);

        //merge the ini with zfconfig values
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();
        
        $dbResultsNamed = [];
        foreach($dbResults as &$row) {
            $this->mergeWithIni($iniOptions, explode('.', $row['name']), $row);
            $dbResultsNamed[$row['name']] = $row;
        }
        return array_values($this->mergeUserValues($user->getUserGuid(), $dbResultsNamed));
    }
    
    /**
     * overrides the DB config values from the user config
     * @param ZfExtended_Models_User $user
     * @param array $dbResults
     * @return array
     */
    public function mergeUserValues(string $userGuid, array $dbResults=[]): array {
        if(empty($dbResults)){
            $dbResults = $this->loadByLevel(self::CONFIG_LEVEL_USER);
        }
        array_walk($dbResults, function(&$r) use($userGuid){
            $r['userGuid'] = $userGuid;
        });
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from('LEK_user_config')
        ->where('userGuid = ?',$userGuid);
        $userResults = $this->db->getAdapter()->fetchAll($s);
        return array_values($this->mergeConfig($userResults, $dbResults));
    }
    
    /**
     * overrides the DB config values from the task config
     * @param string $taskGuid
     * @param array $dbResults
     * @return array
     */
    public function mergeTaskValues(string $taskGuid, array $dbResults=[]):array {
        if(empty($dbResults)){
            $dbResults = $this->loadByLevel(self::CONFIG_LEVEL_TASK);
        }
        array_walk($dbResults, function(&$r) use($taskGuid){
            $r['taskGuid'] = $taskGuid;
        });
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from('LEK_task_config')
        ->where('taskGuid = ?',$taskGuid);
        $userResults = $this->db->getAdapter()->fetchAll($s);
        return array_values($this->mergeConfig($userResults, $dbResults));
    }
    
    /***
     * overrides the DB config values from the customer config
     * @param int $customerId
     * @param array $dbResults
     * @return array
     */
    public function mergeCustomerValues(int $customerId, array $dbResults=[]):array {
        if(empty($dbResults)){
            $dbResults = $this->loadByLevel(self::CONFIG_LEVEL_CUSTOMER);
        }
        array_walk($dbResults, function(&$r) use($customerId){
            $r['customerId'] = $customerId;
        });
        $s = $this->db->select()
        ->setIntegrityCheck(false)
        ->from('LEK_customer_config')
        ->where('customerId = ?',$customerId);
        $userResults = $this->db->getAdapter()->fetchAll($s);
        return array_values($this->mergeConfig($userResults, $dbResults));
    }
    
    /***
     * Merge the input array into the result array. Values will be merged only if the config from 
     * the input array exisit in the result array
     * @param array $input
     * @param array $result
     * @return string
     */
    protected function mergeConfig(array $input,array $result){
        foreach($input as $row) {
            if(!empty($result[$row['name']])) {
                $row['overwritten'] = $row['value'];
                $result[$row['name']]['overwritten'] = $result[$row['name']]['value'];
                $result[$row['name']]['value'] = $row['value'];
                $result[$row['name']]['origin'] = self::CONFIG_SOURCE_USER;
            }
        }
        return $result;
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
    
    public function updateConfig(string $configName, string $configValue, int $configLevel) {
        
        $userSession = new Zend_Session_Namespace('user');
        
        $user=ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->load($userSession->data->id);
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        if($acl->isInAllowedRoles($user->getRoles(),'stateconfig',$this->configLabel[$configLevel])){
            throw new ZfExtended_ErrorCodeException("");//TODO: new error code: The user is not alowed to modefy config of this level
        }
        
        switch ($configLevel) {
            case self::CONFIG_LEVEL_USER:
                $userConfig=ZfExtended_Factory::get('editor_Models_UserConfig');
                /* @var $userConfig editor_Models_UserConfig */
                $userConfig->updateInsertConfig($user->getUserGuid(),$configName,$configValue);
            break;
            case self::CONFIG_LEVEL_TASK:
                break;
            case self::CONFIG_LEVEL_CUSTOMER:
                break;
            case self::CONFIG_LEVEL_SYSTEM:
                break;
        }
    }
    
    /***
     * Update the user config and the default zf_config value for given user and config name
     * @param ZfExtended_Models_User $user
     * @param string $configName
     * @param string $configValue
     */
    public function xxxupdateConfig(ZfExtended_Models_User $user,string $configName,string $configValue){
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
    
    public function getConfigLevelLabel(int $level){
        return $this->configLabel[$level] ?? null;
    }
}
