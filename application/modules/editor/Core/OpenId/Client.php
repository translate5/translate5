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

namespace Core\OpenId;

use Bootstrap;
use editor_Models_Customer_Customer;
use Jumbojett\OpenIDConnectClient;
use Jumbojett\OpenIDConnectClientException;
use stdClass;
use Zend_Application_Bootstrap_Exception;
use Zend_Controller_Front;
use Zend_Controller_Request_Abstract;
use Zend_Controller_Request_Http;
use Zend_Registry;
use ZfExtended_Acl;
use ZfExtended_Factory;
use ZfExtended_Logger;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User;
use ZfExtended_Resource_Session;
use ZfExtended_Utils;

class Client{

    /***
     *
     * @var Zend_Controller_Request_Http
     */
    protected Zend_Controller_Request_Http $request;

    /***
     * Current customer used in the request domain
     * @var editor_Models_Customer_Customer
     */
    protected editor_Models_Customer_Customer $customer;

    /***
     * Open id client instance
     * @var OpenIDConnectClient
     */
    protected OpenIDConnectClient $openIdClient;

    /***
     * @var
     */
    protected mixed $config;

    /***
     * User verified openid claims
     *
     * @var stdClass
     */
    protected $openIdUserClaims;


    /***
     * Additional user information from the openid enpoind
     * @var stdClass
     */
    protected $openIdUserInfo;

    /**
     * @var ZfExtended_Logger
     */
    protected ZfExtended_Logger $log;

    public function __construct(Zend_Controller_Request_Abstract $request) {
        $this->openIdClient = new OpenIDConnectClient();
        $this->config = Zend_Registry::get('config');
        $this->setRequest($request);
        $this->initOpenIdData();
        $this->log = Zend_Registry::get('logger')->cloneMe('core.openidconnect');
    }



    public function setRequest(Zend_Controller_Request_Abstract $request){
       $this->request=$request;
    }

    /***
     * Init openid required data from the request and session.
     */
    protected function initOpenIdData(){
        $this->initCustomerFromDomain();
        //if the openid fields for the customer are not set, stop the init
        if(!$this->isOpenIdCustomerSet()){
            return;
        }
        $this->openIdClient->setClientID($this->customer->getOpenIdClientId());
        $this->openIdClient->setClientSecret($this->customer->getOpenIdClientSecret());
        $this->openIdClient->setProviderURL($this->customer->getOpenIdServer());
        $this->openIdClient->setIssuer($this->customer->getOpenIdIssuer());
        $this->openIdClient->setRedirectURL($this->getRedirectDomainUrl());
        //set the ssl certificate file path if it is configured
        if(!empty($this->config->runtimeOptions->openid->sslCertificatePath)){
            $this->openIdClient->setCertPath($this->config->runtimeOptions->openid->sslCertificatePath);
        }
    }

    /**
     * @throws ClientException
     * @throws Zend_Application_Bootstrap_Exception
     */
    public function authenticate(): bool
    {

        //if the openid fields for the customer are not set, ignore the auth call
        if(!$this->isOpenIdCustomerSet()){
            return false;
        }

        
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        /* @var $bootstrap Bootstrap */
        $session = $bootstrap->getPluginResource('ZfExtended_Resource_Session');
        /* @var $session ZfExtended_Resource_Session */
        if(!$session->isHttpsRequest()) {
            throw new ClientException("E1328", $this->getExceptionData());
        }
        
        $isAuthRequest=!empty($this->request->getParam('code')) || !empty($this->request->getParam('id_token'));
        $isLoginRequest=!empty($this->request->getParam('login')) && !empty($this->request->getParam('passwd'));
        $isRedirectRequest=$this->request->getParam('openidredirect')=='openid';
        if(!$isAuthRequest && !$isRedirectRequest && !$isLoginRequest){
            return false;
        }

        $this->openIdClient->setVerifyHost(true);
        $this->openIdClient->setVerifyPeer(true);
        $this->openIdClient->addScope(array('openid','profile','email'));
        $this->openIdClient->setAllowImplicitFlow(true);
        $this->openIdClient->addAuthParam(array('response_mode' => 'form_post'));
        $this->openIdClient->setResponseTypes('id_token');
        $this->openIdClient->setResponseTypes('code');
        try {
            return $this->openIdClient->authenticate();
        } catch (OpenIDConnectClientException $e) {
            throw new ClientException("E1165", $this->getExceptionData(), $e);
        }
    }

    /**
     * It calls the end-session endpoint of the OpenID Connect provider to notify the OpenID
     * Connect provider that the end-user has logged out of the relying party site
     * (the client application).
     *
     * @param string $accessToken ID token (obtained at login)
     * @param string $redirect URL to which the RP is requesting that the End-User's User Agent
     * be redirected after a logout has been performed. The value MUST have been previously
     * registered with the OP. Value can be null.
     *
     * @throws OpenIDConnectClientException
     */
    public function signOut($accessToken, $redirect) {
        $this->openIdClient->signOut($accessToken, $redirect);
    }

    /***
     * Create user from the OAuth verified user claims
     * FIXME should be renamed to createOrMergeUser
     * @return NULL|ZfExtended_Models_User
     */
    public function createUser(): ?ZfExtended_Models_User
    {
        $emailClaims = $this->getEmailClaim();
        $user = $this->initOrLoadUser($emailClaims);

        //down here update user with data from SSO
        $user->setEmail($emailClaims);
        $user->setFirstName($this->getOpenIdUserData('given_name'));
        $user->setSurName($this->getOpenIdUserData('family_name'));

        //the gender is required in translate5, and in the response can be empty or larger than 1 character
        $gender=!empty($this->getOpenIdUserData('gender',false)) ? substr($this->getOpenIdUserData('gender'),0,1) : 'n';
        $user->setGender($gender);

        $user->setEditable(1);

        //find the default locale from the config
        $localeConfig = $this->config->runtimeOptions->translation;
        $appLocale=!empty($localeConfig->applicationLocale) ? $localeConfig->applicationLocale : null;
        $fallbackLocale=!empty($localeConfig->fallbackLocale) ? $localeConfig->fallbackLocale : null;

        $defaultLocale=empty($appLocale) ? (empty($fallbackLocale) ? 'en' : $fallbackLocale) : $appLocale;


        $claimLocale=$this->getOpenIdUserData('locale',false);

        //if the claim locale is empty, use the default user locale
        if(empty($claimLocale)){
            $claimLocale=$defaultLocale;
        }else{
            $claimLocale=explode('-', $claimLocale);
            $claimLocale=$claimLocale[0];
        }
        $user->setLocale($claimLocale);

        $user->setCustomers(','.$this->customer->getId().',');

        //find and set the roles, depending of the openid server config, this can be defined as roles or role
        //and it can exist either in the verified claims or in the user info
        $roles=$this->getOpenIdUserData('roles',false);
        if(empty($roles)){
            $roles=$this->getOpenIdUserData('role',false);
        }
        if(empty($roles)){
            $this->log->info('E1174', 'No roles are provided by the OpenID Server to translate5. The default roles that are set in the configuration for the customer are used.');
        }
        $user->setRoles($this->mergeUserRoles($roles));
        return $user->save()>0? $user : null;
    }

    /**
     * Inits either an empty user with SSO data, or loads an existing one to be updated with the SSO data
     * @param string $emailClaims
     */
    protected function initOrLoadUser($emailClaims): ZfExtended_Models_User
    {
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        $issuer = $this->getOpenIdUserData('iss');
        $subject = $this->getOpenIdUserData('sub');

        //check if the sso user already exist for the issuer and subject, if yes use it and update other data outside
        if($user->loadByIssuerAndSubject($issuer,$subject)){
            return $user;
        }

        $userGuid = ZfExtended_Utils::guid(true);

        //check if the user with email as login exist
        try {
            $user->loadByLogin($emailClaims);
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e) {
            //the user with email as login does not exist, this is a new user, so set the login as email and set the sso info
            $user->setOpenIdIssuer($issuer);
            $user->setOpenIdSubject($subject);
            $user->setUserGuid($userGuid);
            $user->setLogin($emailClaims);
            return $user;
        }

        //the user with same email exist, now try to check if it is another sso user
        //another sso user is when the user has values in issuer and subject fields
        //this can only happen if 2 different sso user has same email address
        if(!empty($user->getOpenIdIssuer()) && !empty($user->getOpenIdSubject())){
            // the loaded $user is already an SSO user with same email.
            // we want to create a new one then (init to throw away the above loaded data)
            $user->init();

            $user->setUserGuid($userGuid);
            $user->setLogin($userGuid);
            $userId=$user->save();
            //update the login with the openid as prefix
            $user->setLogin('OID-'.$userId);
        }

        $user->setOpenIdIssuer($issuer);
        $user->setOpenIdSubject($subject);
        return $user;
    }

    /**
     * return the emailClaim from email or upn or preferred_username, null if nothing was a valid email
     * @return string|NULL
     */
    protected function getEmailClaim(): ?string
    {
        $emailClaims = $this->getOpenIdUserData('email');

        if(!empty($emailClaims)){
            return $emailClaims;
        }
        //if the email is not found from the standard claims, try to get it from 'upn'
        $emailClaims=$this->getOpenIdUserData('upn');
        //the upn is defined, check if it is valid email
        if(!empty($emailClaims) && filter_var($emailClaims, FILTER_VALIDATE_EMAIL) !== false){
            return $emailClaims;
        }

        //if the email is empty again, try to find if it is defined as preferred_username claim
        $emailClaims=$this->getOpenIdUserData('preferred_username');
        //the preferred_username is defined, chech if it is valid email
        if(!empty($emailClaims) && filter_var($emailClaims, FILTER_VALIDATE_EMAIL) !== false){
            return $emailClaims;
        }
        //FIXME throw an exception here???
        return null;
    }

    /***
     * Merge the verified role claims from the openid client server and from the customer for the user.
     * @param array|string $claimsRoles
     * @return array
     * @throws ClientException
     */
    protected function mergeUserRoles($claimsRoles): array {
        $customerRoles=$this->customer->getOpenIdServerRoles();
        $openIdDefaultServerRoles=$this->customer->getOpenIdDefaultServerRoles();

        if(empty($openIdDefaultServerRoles) && empty($claimsRoles)){
            throw new ClientException("E1329", $this->getExceptionData());
        }

        //no claim roles, use the default roles
        if(empty($claimsRoles)){
            return explode(',', $openIdDefaultServerRoles);
        }

        //if there is no customer roles, log the info message and throw an exception
        if(empty($customerRoles)){
            throw new ClientException("E1330", $this->getExceptionData());
        }

        $customerRoles=explode(',',$customerRoles);

        if(is_string($claimsRoles)){
            $claimsRoles=explode(',', $claimsRoles);
        }

        //if users are in more than 1 group with rights for translate5, it can happen that the IDP server delivers
        //an array structure like such
        //array("instantTranslate,termCustomerSearch,termProposer","instantTranslate,termCustomerSearch");
        //this is merged below
        //$claimsRoles containing comma separated strings with roles
        $claimsRoles = array_unique(call_user_func_array('array_merge', array_map(function($item){
            //explode each item
            return explode(',', $item);
        },$claimsRoles)));

        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */

        $allRoles = $acl->getAllRoles();
        $roles = array();
        foreach($allRoles as $role) {
            if($role == 'noRights' || $role == 'basic') {
                continue;
            }
            //the role exist in the translate5 and the role is valid for the customer
            if(in_array($role, $claimsRoles) && in_array($role, $customerRoles)){
                $roles[]=$role;
            }
        }
        //check if the claims roles are allowed by the server customer roles
        if(empty($roles)){
            throw new ClientException("E1331", $this->getExceptionData());
        }

        // add the autoset roles to the calculated user roles
        return $this->mergeAutoSetRoles($roles);
    }

    /***
     * Merge the calculated user roles and add the autoset roles to them
     * @param array $roles
     * @return array
     */
    protected function mergeAutoSetRoles(array $roles): array
    {
        // set the module to editor so the correct acl auto set roles are loaded
        Zend_Registry::set('module','editor');
        $acl = ZfExtended_Acl::getInstance(true);
        /* @var $acl ZfExtended_Acl */
        // add the autoset roles to the calculated user roles
        $roles = $acl->mergeAutoSetRoles($roles,[]);
        // reset the module back to default and reset the acl cache
        Zend_Registry::set('module','default');
        ZfExtended_Acl::getInstance()::reset();
        return $roles;
    }

    /***
     * @return string
     */
    public function getRedirectDomainUrl(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ?
            "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .
            $_SERVER['REQUEST_URI'];
    }

    /***
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return $_SERVER['HTTP_HOST'].$this->request->getBaseUrl().'/';
    }

    /***
     * Get the customer from the current used domain.
     * @return editor_Models_Customer_Customer
     */
    protected function initCustomerFromDomain(): editor_Models_Customer_Customer
    {
        $customer = ZfExtended_Factory::get('editor_Models_Customer_Customer');
        $customer->loadByDomain($this->getBaseUrl());
        //the customer for the domain does not exist, load the default customer
        if($customer->getId()==null){
            $customer->loadByDefaultCustomer();
        }
        $this->customer=$customer;
        return $this->customer;
    }

    /***
     * Check if the openid fields are set in the customer
     * @return boolean
     */
    public function isOpenIdCustomerSet(): bool
    {
        if($this->customer->getId()==null){
            return false;
        }
        if($this->customer->getOpenIdServer()==null || $this->customer->getOpenIdServer()==''){
            return false;
        }
        return true;
    }

    public function getCustomer(): editor_Models_Customer_Customer
    {
        return $this->customer;
    }


    /***
     * Get the user info from the openid provider.
     *
     * @param string $attribute
     * @param bool $warnEmpty
     * @return NULL|mixed
     */
    public function getOpenIdUserData(string $attribute,bool $warnEmpty = true): mixed
    {

        //load openid claims from the sso provider
        if(!isset($this->openIdUserClaims)){
            $this->openIdUserClaims=$this->openIdClient->getVerifiedClaims();
        }

        //load the user info endpoint only if it is allowed via configuration
        if(!isset($this->openIdUserInfo) && $this->config->runtimeOptions->openid->requestUserInfo){
            try{
                $this->openIdUserInfo=$this->openIdClient->requestUserInfo();
            } catch(OpenIDConnectClientException $exc){
                //When the user is not allowed to acces the userinfo endpoint, openid connect will throw an exception.
                
                //Basically all required user information can be provided with the openid claims, and if the user info is
                //not accesable via userinfo_endpoint, try to get the information from the main openid claims.
                //Whenever an required user info is not found from the claims, translate5 will write an warning in the log.

                //Info: userinfo_endpoint is deprecated on the newer versions of openid protocol,
                //,because the access_token and id_token that you get from the authenticator is enough to get user attributes.
            }
        }

        //check if the attribute exist in the claims
        if(!empty($this->openIdUserClaims) && property_exists($this->openIdUserClaims, $attribute)) {
            return $this->openIdUserClaims->$attribute;
        }

        //check if the attribute exist in the user info
        if (!empty($this->openIdUserInfo) && property_exists($this->openIdUserInfo, $attribute)) {
            return $this->openIdUserInfo->$attribute;
        }
        if($warnEmpty){
            $this->log->warn('E1173', 'The OpenIdUserData attribute {attribute} was not set by the requested OpenID server.', [
                'attributeToFetch' => $attribute,
                'attribute' => $attribute,
            ]);
        }

        //no attribute was found
        return null;
    }

    /**
     * returns a data array for error code exceptions
     * @return array
     */
    protected function getExceptionData(): array {
        return [
            'request' => print_r($this->request->getParams(),1),
            'session' => print_r($_SESSION,1),
            'openid' => [
                'customerId'    =>  $this->getCustomer()->getId(),
                'customerName'  =>  $this->getCustomer()->getName(),
                'userInfo'      =>  $this->openIdUserInfo,
                'userClaims'    =>  $this->openIdUserClaims
            ]
        ];
    }
}
