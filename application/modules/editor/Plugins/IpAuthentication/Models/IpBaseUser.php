<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/***
 * Check if the current client request is configured as ip based in the zf_configuration.
 * Create/load ip based temp user.
 */
class editor_Plugins_IpAuthentication_Models_IpBaseUser extends ZfExtended_Models_User {
    
    /***
     * String wich will be applied as prefix to the user login
     *
     * @var string
     */
    const IP_BASED_USER_LOGIN_PREFIX = 'tmp-ip-based-user';
    
    /***
     * Current client ip address
     * @var string
     */
    protected $ip;
    
    /***
     *
     * @var
     */
    protected $config;
    
    /**
     * @var Zend_Session_Namespace
     */
    protected  $_session;
    
    public function __construct(){
        parent::__construct();
        $remoteAdress = ZfExtended_Factory::get('ZfExtended_RemoteAddress');
        /* @var $remoteAdress ZfExtended_RemoteAddress */
        $this->ip = $remoteAdress->getIpAddress();
        $this->config = Zend_Registry::get('config');
        $this->_session = new Zend_Session_Namespace();
    }
    
    /***
     * Find all ip based users with expired session
     * @return array
     */
    public function findAllExpired(){
        $sql = " SELECT u.* FROM Zf_users u ".
                " LEFT JOIN sessionMapInternalUniqId s ON u.login = CONCAT('".self::IP_BASED_USER_LOGIN_PREFIX."',s.internalSessionUniqId) ".
                " WHERE login like '".self::IP_BASED_USER_LOGIN_PREFIX."%' ".
                " AND s.internalSessionUniqId is null";
        $res = $this->db->getAdapter()->query($sql);
        return $res->fetchAll();
    }
    
    /***
     * Update or create ip based user with ip based configuration data
     * @return editor_Plugins_IpAuthentication_Models_IpBaseUser
     */
    public function handleIpBasedUser() {
        //init the ipbased user and update the ip based specific data
        $this->initIpBasedUser();
        //if the id of the model is set then the user exist and no need to update other stuff
        if($this->getId()!==null){
            return $this;
        }
        
        $this->setEmail($this->config->resources->mail->defaultFrom->email);
        $this->setUserGuid(ZfExtended_Utils::guid(true));
        $this->setFirstName("Ip");
        $this->setSurName("Based User");

        //after the user is authenticated, the login will be updated to ipbased user prefix+sessionId (see: generateIpBasedLogin)
        $this->setLogin($this->generateIpBasedLogin());
        //the gender is required in translate5, and in the response can be empty or larger than 1 character
        $this->setGender('n');
        
        $this->setEditable(1);
        
        //find the default locale from the config
        $localeConfig = $this->config->runtimeOptions->translation ?? null;
        $appLocale=$localeConfig->applicationLocale ?? null;
        $fallbackLocale=$localeConfig->fallbackLocale ?? 'en';
        $locale=!empty($appLocale) ? $appLocale : $fallbackLocale;

        $this->setLocale($locale);
        
        $this->save();
        return $this;
    }
    
    /***
     * Init ip based user model and set the ip based specific user data from the config
     *
     * @throws editor_Plugins_IpAuthentication_Models_IpBaseException
     * @return editor_Plugins_IpAuthentication_Models_IpBaseUser
     */
    protected function initIpBasedUser(){
        try {
            $this->loadByLogin($this->generateIpBasedLogin());
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //reset to empty model
            $this->init([]);
        }
        
        $rop = $this->config->runtimeOptions;
        
        $customersMap = $rop->authentication->ipbased->IpCustomerMap->toArray();
        
        $customer = ZfExtended_Factory::get('editor_Models_Customer');
        /* @var $customer editor_Models_Customer */
        
        //is customer configured for the client ip
        if(isset($customersMap[$this->ip])){
            try {
                $customer->loadByNumber($customersMap[$this->ip]);
            } catch (Exception $e) {
                $logger = Zend_Registry::get('logger')->cloneMe('authentication.ipbased');
                $logger->warn('E1289',str_replace("{number}", $customersMap[$this->ip],"Ip based authentication: Customer with number ({number}) does't exist."), [
                    'number' => $customersMap[$this->ip]
                ]);
            }
        }
        //no customer configuration found for the client ip. Use the default customer
        if($customer->getId() == null){
            $customer->loadByDefaultCustomer();
        }
        
        $this->setCustomers(','.$customer->getId().',');
        
        //load all roles wich the ip based user will have after login
        $roles = $rop->authentication->ipbased->userRoles->toArray();
        
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        $allowedRoles = [];
        //check if the configured ib based use roles are allowed for ip authentication
        foreach ($roles as $role){
            try {
                if($acl->isAllowed($role,'frontend', 'ipBasedAuthentication')){
                    $allowedRoles[]=$role;
                }
            } catch (Exception $e) {
            }
        }
        
        if(empty($allowedRoles)){
            throw new editor_Plugins_IpAuthentication_Models_IpBaseException("E1290",[
                'configuredRoles'=>implode(',', $roles)
            ]);
        }
        
        $this->setRoles($allowedRoles);
        return $this;
    }
    
    /***
     * @param string $userGuid
     * @return boolean
     */
    public function isIpBasedUser(string $userGuid) {
        try {
            $this->loadByGuid($userGuid);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //the user does not exist
            return false;
        }
        if($this->getId()==null){
            return false;
        }
        return $this->getLogin() == $this->generateIpBasedLogin();
    }
    
    /***
     * Check if the current request ip is alowed/configured for ip based authentication
     * @return boolean
     */
    public function isIpBasedRequest(){
        $ipConfig = $this->config->runtimeOptions->authentication->ipbased->IpAddresses->toArray();
        return !empty($ipConfig) && in_array($this->ip, $ipConfig);
    }
    
    /***
     * Generate user login from the current ip address and the current session id
     * @return string
     */
    public function generateIpBasedLogin(){
        return self::IP_BASED_USER_LOGIN_PREFIX.$this->_session->internalSessionUniqId;
    }
    
    /**
     * returns the currently used IP Adress
     * @return string
     */
    public function getIp() {
        return $this->ip;
    }
}
