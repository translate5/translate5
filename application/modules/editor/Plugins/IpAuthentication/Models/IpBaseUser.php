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

use MittagQI\Translate5\Plugins\IpAuthentication\AclResource;
use MittagQI\Translate5\Tools\IpMatcher;
use MittagQI\ZfExtended\Session\SessionInternalUniqueId;

/***
 * Check if the current client request is configured as ip based in the zf_configuration.
 * Create/load ip based temp user.
 */
class editor_Plugins_IpAuthentication_Models_IpBaseUser
{
    /***
     * String wich will be applied as prefix to the user login
     *
     * @var string
     */
    public const IP_BASED_USER_LOGIN_PREFIX = 'tmp-ip-based-user';

    /**
     * Current client ip address
     */
    protected string $ip;

    protected Zend_Config $config;

    protected Zend_Session_Namespace $session;

    private ?editor_Models_Customer_Customer $customerForIp = null;

    private ZfExtended_Models_User $user;

    private bool $isIpInRange = false;

    private bool $hasIpConfiguration = false;

    /**
     * @throws Zend_Exception|ReflectionException
     */
    public function __construct()
    {
        $this->config = Zend_Registry::get('config');
        $this->ip = $this->resolveIp();
        $this->user = new ZfExtended_Models_User();
        $this->session = new Zend_Session_Namespace();

        $map = $this->config->runtimeOptions?->authentication?->ipbased?->IpCustomerMap?->toArray() ?? [];

        if (empty($map)) {
            return;
        }

        $this->hasIpConfiguration = true;
        $this->customerForIp = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);

        foreach ($map as $ipRange => $customerId) {
            if ($this->isIpInRange($this->ip, $ipRange)) {
                $this->isIpInRange = true;
                $this->initCustomer((string) $customerId);

                return;
            }
        }

        $this->isIpInRange = false; //not needed but for explicit declaration of not logged in
    }

    /**
     * @throws Zend_Exception
     */
    private function initCustomer(?string $configuredCustomerId): void
    {
        try {
            $this->customerForIp->loadByNumber($configuredCustomerId);
            $this->config = $this->customerForIp->getConfig();
        } catch (Exception) {
            $logger = Zend_Registry::get('logger')->cloneMe('authentication.ipbased');
            $logger->warn(
                'E1289',
                'Ip based authentication: Customer with number ({number}) doesn\'t exist.',
                [
                    'number' => $configuredCustomerId,
                ]
            );
        }
    }

    /**
     * Find all ip based users with expired session
     * @throws Zend_Db_Statement_Exception
     */
    public function findAllExpired(): array
    {
        $sql = 'SELECT u.* FROM Zf_users u LEFT JOIN session s ON u.login = CONCAT(\''
            . self::IP_BASED_USER_LOGIN_PREFIX . '\',s.internalSessionUniqId) WHERE login like \''
            . self::IP_BASED_USER_LOGIN_PREFIX . '%\' AND s.internalSessionUniqId is null';
        $res = $this->user->db->getAdapter()->query($sql);

        return $res->fetchAll();
    }

    /**
     * Update or create ip based user with ip based configuration data
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws editor_Plugins_IpAuthentication_Models_IpBaseException
     * @throws Exception
     */
    public function createOrLoadUser(): ZfExtended_Models_User
    {
        //init the ipbased user and update the ip based specific data
        $this->initIpBasedUser();
        //if the id of the model is set then the user exist and no need to update other stuff
        if ($this->user->getId() !== null) {
            return $this->user;
        }

        $this->user->setEmail($this->config->resources->mail->defaultFrom->email);
        $this->user->setUserGuid(ZfExtended_Utils::guid(true));
        $this->user->setFirstName('Ip');
        $this->user->setSurName('Based User');

        //after the user is authenticated, the login will be updated to ipbased user
        // prefix+sessionId (see: generateIpBasedLogin)
        $this->user->setLogin($this->generateIpBasedLogin());
        //the gender is required in translate5, and in the response can be empty or larger than 1 character
        $this->user->setGender('n');

        $this->user->setEditable(1);

        //find the default locale from the config
        $localeConfig = $this->config->runtimeOptions->translation ?? null;
        $appLocale = $localeConfig->applicationLocale ?? null;
        $fallbackLocale = $localeConfig->fallbackLocale ?? 'en';
        $locale = ! empty($appLocale) ? $appLocale : $fallbackLocale;

        $this->user->setLocale($locale);

        $this->user->save();

        return $this->user;
    }

    /**
     * Init ip based user model and set the ip based specific user data from the config
     *
     * @throws editor_Plugins_IpAuthentication_Models_IpBaseException
     */
    protected function initIpBasedUser(): void
    {
        try {
            $this->user->loadByLogin($this->generateIpBasedLogin());
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            //reset to empty model
            $this->user->init([]);
        }

        //no customer configuration found for the client ip. Use the default customer
        if ($this->customerForIp->getId() === null) {
            $this->customerForIp->loadByDefaultCustomer();
        }

        $this->user->setCustomers(',' . $this->customerForIp->getId() . ',');

        //load all roles wich the ip based user will have after login
        $roles = $this->config->runtimeOptions->authentication->ipbased->userRoles->toArray();

        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        $allowedRoles = [];
        //check if the configured ib based use roles are allowed for ip authentication
        foreach ($roles as $role) {
            try {
                if ($acl->isAllowed($role, AclResource::ID, AclResource::IP_BASED_AUTHENTICATION)) {
                    $allowedRoles[] = $role;
                }
            } catch (Throwable) {
                // do nothing
            }
        }

        if (empty($allowedRoles)) {
            throw new editor_Plugins_IpAuthentication_Models_IpBaseException('E1290', [
                'configuredRoles' => implode(',', $roles),
            ]);
        }

        $this->user->setRoles($allowedRoles);
    }

    /**
     * @return boolean
     */
    public function isIpBasedUser(string $userGuid): bool
    {
        try {
            $this->user->loadByGuid($userGuid);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            //the user does not exist
            return false;
        }
        if ($this->user->getId() == null) {
            return false;
        }

        return $this->user->getLogin() == $this->generateIpBasedLogin();
    }

    public function hasIpAuthConfiguration(): bool
    {
        return $this->hasIpConfiguration;
    }

    /**
     * Check if the current request ip is allowed/configured for ip based authentication
     */
    public function isIpBasedRequest(): bool
    {
        return $this->isIpInRange;
    }

    /**
     * Generate user login from the current ip address and the current session id
     */
    public function generateIpBasedLogin(): string
    {
        return self::IP_BASED_USER_LOGIN_PREFIX . SessionInternalUniqueId::getInstance()->get();
    }

    /**
     * returns the currently used IP Adress
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * Checks if particular ip address is within the provided range
     */
    private function isIpInRange(string $ipToCheck, string $range): bool
    {
        return (new IpMatcher())->isIpInRange($ipToCheck, $range);
    }

    /**
     * @throws ReflectionException
     */
    private function resolveIp(): string
    {
        $remoteAddress = ZfExtended_Factory::get(ZfExtended_RemoteAddress::class);

        $localProxies = $this->config->runtimeOptions->authentication?->ipbased?->useLocalProxy?->toArray() ?? [];
        if (! empty($localProxies)) {
            //if we have local proxies, we have to use real_ip added by them, since forwared_for is spoofable and
            // also reflects remote proxies, which should be the sender IPs configured in IpCustomerMap!
            // see also
            // https://stackoverflow.com/questions/72557636/difference-between-x-forwarded-for-and-x-real-ip-headers
            $remoteAddress->setProxyHeader('HTTP_X_REAL_IP');
            //get all IPs of all configured local proxies and allow them
            $remoteAddress->setTrustedProxies(array_merge(...array_map('gethostbynamel', $localProxies)));
            $remoteAddress->setUseProxy();
        }

        return $remoteAddress->getIpAddress();
    }

    public function getConfig(): Zend_Config
    {
        return $this->config;
    }
}
