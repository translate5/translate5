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

        $configuredCustomerId = $this->resolveCustomerIdByIp($this->ip);

        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        
        //is customer configured for the client ip
        if($configuredCustomerId !== null){
            try {
                $customer->loadByNumber($configuredCustomerId);
            } catch (Exception $e) {
                $logger = Zend_Registry::get('logger')->cloneMe('authentication.ipbased');
                $logger->warn('E1289',str_replace("{number}", $configuredCustomerId,"Ip based authentication: Customer with number ({number}) does't exist."), [
                    'number' => $configuredCustomerId
                ]);
            }
        }

        //no customer configuration found for the client ip. Use the default customer
        if($customer->getId() === null){
            $customer->loadByDefaultCustomer();
        }
        
        $this->setCustomers(','.$customer->getId().',');
        
        //load all roles wich the ip based user will have after login
        $roles = $this->config->runtimeOptions->authentication->ipbased->userRoles->toArray();
        
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
    public function isIpBasedUser(string $userGuid): bool{
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
     * Get the configured ip addresses in zf configuration
     * @return array
     */
    public function getConfiguredIps(): array{
        return $this->config->runtimeOptions?->authentication?->ipbased?->IpAddresses?->toArray() ?? [];
    }


    /***
     * Check if the current request ip is allowed/configured for ip based authentication
     * @return boolean
     */
    public function isIpBasedRequest(): bool{
        $ipConfig = $this->getConfiguredIps();

        foreach ($ipConfig as $ipRange) {
            if ($this->isIpInRange($this->ip, $ipRange)) {
                return true;
            }
        }

        return false;
    }
    
    /***
     * Generate user login from the current ip address and the current session id
     * @return string
     */
    public function generateIpBasedLogin(): string{
        return self::IP_BASED_USER_LOGIN_PREFIX.$this->_session->internalSessionUniqId;
    }
    
    /**
     * returns the currently used IP Adress
     * @return string
     */
    public function getIp(): string{
        return $this->ip;
    }

    /**
     * Resolve customer id based on particular ip if exists in configuration, otherwise return null
     *
     * @param string $ip - IP in dot notation
     *
     * @return int|null
     */
    private function resolveCustomerIdByIp(string $ip): ?int
    {
        $customersMap = $this->config->runtimeOptions->authentication->ipbased->IpCustomerMap->toArray();

        foreach ($customersMap as $ipRange => $customerId) {
            if ($this->isIpInRange($ip, $ipRange)) {
                return $customerId;
            }
        }

        return null;
    }

    /**
     * Checks if particular ip address is within the provided range
     *
     * @param string $ipToCheck - ip address in dot notation to check against the particular range
     * @param string $range - ip range in dot notation with subnet mask e.g. (192.168.0.1/32)
     *
     * @return bool
     */
    private function isIpInRange(string $ipToCheck, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);

        // In case $range doesn't contain a subnet
        if ($bits === null) {
            $bits = 32;
        }

        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
        $ip = ip2long($ipToCheck);

        return ($ip & $mask) === $subnet;
    }
}
