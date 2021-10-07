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

/**#@+
 * @author Marc Mittag
 * @package translate5
 * @version 1.0
 *
 */
/**
 * Klasse der Nutzermethoden
 */
class LoginController extends ZfExtended_Controllers_Login {
    public function init(){
        parent::init();
        $this->_form   = new ZfExtended_Zendoverwrites_Form('loginIndex.ini');
    }
    

    public function indexAction() {
        require_once 'default/Controllers/helpers/BrowserDetection.php';
        // Internet Explorer is not supported anymore! redirect IE 11 or below users to a specific error page
        if(BrowserDetection::isInternetExplorer()){
            header('Location: '.APPLICATION_RUNDIR.'/index/internetexplorer');
            exit;
        }
     
        $this->appendCustomScript();

        $lock = ZfExtended_Factory::get('ZfExtended_Models_Db_SessionUserLock');
        /* @var $lock ZfExtended_Models_Db_SessionUserLock */
        $this->view->lockedUsers = $lock->getLocked();
       
        //set the redirecthash value in the session if it is provided by the login form submit
        //Info: bacause of the multiple redirects (sso authentication), we save the hash in the session, and reaply it when
        //we redirect to the editor module
        $redirecthash = $this->getRequest()->getParam('redirecthash','');
        if(!empty($redirecthash)){
            $this->_session->redirecthash = $redirecthash;
        }
        parent::indexAction();
        //if the login status is required, try to authenticate with openid connect
        if($this->view->loginStatus==ZfExtended_Models_SessionUserInterface::LOGIN_STATUS_OPENID){
            $this->handleOpenIdRequest();
        }
    }
    
    public function doOnLogout() {
        //INFO: disable the openid logout, enable only if requested
        //$this->handleOpenIdLogout();
        //init editor module on logout, so that specific logout handling can be triggered via events
        $base = ZfExtended_BaseIndex::getInstance();
        $base->setModule('editor');
        $bootstrap = Zend_Registry::get('bootstrap');
        require_once 'editor/Bootstrap.php';
        $module = new Editor_Bootstrap($bootstrap->getApplication());
        $module->bootstrap();
    }
    
    protected function initDataAndRedirect() {
        //@todo do this with events
        if(class_exists('editor_Models_Segment_MaterializedView')) {
            $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
            /* @var $mv editor_Models_Segment_MaterializedView */
            $mv->cleanUp();
        }
        
        $this->localeSetup();
        
        $sessionUser = new Zend_Session_Namespace('user');
        $acl = ZfExtended_Acl::getInstance();
        /* @var $acl ZfExtended_Acl */
        $roles=$sessionUser->data->roles;

        $isTermPortalAllowed=$acl->isInAllowedRoles($roles, 'initial_page', 'termPortal');
        $isInstantTranslateAllowed=$acl->isInAllowedRoles($roles, 'initial_page', 'instantTranslatePortal');

        $hash = $this->handleRedirectHash();

        // If user was not logged in during the attempt to load termportal, but now is logged and allowed to do that
        //TODO: after itranslate route is changed to itranslate to instanttranslate here
        if (preg_match('~^#(termportal|itranslate)~', $hash) && $isTermPortalAllowed) {
            // Do redirect
            $this->applicationRedirect(substr($hash, 1), true);
        }

        if($acl->isInAllowedRoles($roles, 'initial_page','editor')) {
            $this->editorRedirect();
        }

        //the user has termportal and instantranslate roles
        if($isTermPortalAllowed && $isInstantTranslateAllowed){
            //find the last used app, if none use the instantranslate as default
            $meta=ZfExtended_Factory::get('editor_Models_UserMeta');
            /* @var $meta editor_Models_UserMeta */
            $meta->loadOrSet($sessionUser->data->id);
            $rdr='instanttranslate';
            if($meta->getId()!=null && $meta->getLastUsedApp()!=''){
                $rdr=$meta->getLastUsedApp();
            }
            $this->applicationRedirect($rdr, $isTermPortalAllowed);
        }
        
        //is instanttranslate allowed
        if($isInstantTranslateAllowed){
            $this->applicationRedirect('instanttranslate');
        }
        
        //is termportal allowed
        if($isTermPortalAllowed){
            $this->applicationRedirect('termportal');
        }
        
        if($sessionUser->data->login == Zfextended_Models_User::SYSTEM_LOGIN) {
            $this->logoutAction();
        }
        
        throw new ZfExtended_NoAccessException("No initial_page resource is found.");
        exit;
    }

    /***
     * Redirect to one of the existing applications (termportal or instanttranslate)
     *
     * @param string $applicationName application name
     * @param null $isTermPortalAllowed is the user allowed to see termportal by acl
     * @throws Zend_Exception
     */
    protected function applicationRedirect(string $applicationName, $isTermPortalAllowed = null){

        $pluginmanager = Zend_Registry::get('PluginManager');
        /* @var $pluginmanager ZfExtended_Plugin_Manager */
        $plugins = array_keys($pluginmanager->getAvailable());
        $termPortalEnabled = in_array('TermPortal',$plugins);


        // is term portal allowed when the user has termportal rights and the termportal plugin is enabled
        $isTermPortalAllowed = $isTermPortalAllowed && $termPortalEnabled;

        header ('HTTP/1.1 302 Moved Temporarily');
        $apiUrl=APPLICATION_RUNDIR.'/editor/'.$applicationName;
        $url = $applicationName == 'termportal' || $isTermPortalAllowed
            ? APPLICATION_RUNDIR.'/editor/termportal#'.$applicationName
            : APPLICATION_RUNDIR.'/editor/apps?name='.$applicationName.'&apiUrl='.$apiUrl;
        header ('Location: '.$url);
        exit;
    }

    /***
     * Redirect to the editor module (append the hash if exist).
     */
    protected function editorRedirect(){
        $redirecthash = isset($this->_session->redirecthash) ? $this->_session->redirecthash : null;
        $redirectHeader = 'Location: '.APPLICATION_RUNDIR.'/editor';
        if(!empty($redirecthash)){
            //remove the redirect hash from the session. The rout handling is done by extjs
            unset($this->_session->redirecthash);
            $redirectHeader.=$redirecthash;
        }
        header ('HTTP/1.1 302 Moved Temporarily');
        header ($redirectHeader);
        exit;
    }
    
    /***
     * Check if the current request is valid for the openid. If it is a valid openid request, the user
     * login will be redirected to the openid client server
     */
    protected function handleOpenIdRequest() {
        $oidc = new ZfExtended_OpenIDConnectClient($this->getRequest());
        //the openid authentication is valid
        
        $isCustomerSet=$oidc->isOpenIdCustomerSet();
        if(!$isCustomerSet){
            return;
        }

        //add form hidden field, which is used when redirec to openid is needed
        $redirect = new Zend_Form_Element_Hidden([
            'name' => 'openidredirect',
            'value' => $oidc->getCustomer()->getOpenIdRedirectCheckbox()
        ]);
        $this->_form->addElement($redirect);

        //set the redirect label if the customer exist
        if(!$oidc->getCustomer()->getOpenIdRedirectCheckbox()){
            
            //create the link field which needs to redirect the login via the configured openid server
            $link = new Zend_Form_Element_Note(array(
                'name' => 'openidSubmitButton',
                'value' => $oidc->getCustomer()->getOpenIdRedirectLabel(),
                'decorators' => array(
                    array('ViewHelper'),
                    array('HtmlTag', array(
                        'tag' => 'button',
                        'id' => 'openid-login',
                        'href' => 'javascript:void(0);',
                        'onclick'=>'document.getElementById("openidredirect").value="openid"; document.getElementById("btnSubmit").click();'
                    )),
                    
                )
            ));
            $link->setOrder(4);
            $this->_form->addElement($link);
        }
        
        try {
            //authenticate with the configured openid client
            if($oidc->authenticate()){
                //INFO: disable the openid logout, enable only if requested
                //if($this->_request->getParam('id_token')!=null){
                //    $userSession = new Zend_Session_Namespace('openId');
                //    $userSession->data->idToken = $this->_request->getParam('id_token');
                //}
                //create the user in the translate5 system or update if the user already exist
                $user = $oidc->createUser();
                if(!$user){
                    return;
                }
                //init the user session and redirect to the editor
                
                $invalidLoginCounter = ZfExtended_Factory::get('ZfExtended_Models_Invalidlogin',array($user->getLogin()));
                /* @var $invalidLoginCounter ZfExtended_Models_Invalidlogin */
                $invalidLoginCounter->resetCounter(); // bei erfolgreichem login den counter zurücksetzen
                ZfExtended_Models_LoginLog::addSuccess($user, "openid");

                $this->_userModel->setUserSessionNamespaceWithoutPwCheck($user->getLogin());

                ZfExtended_Session::updateSession(true,true);

                $this->initDataAndRedirect();
            }
        } catch (ZfExtended_OpenIDConnectClientException $e) {
            ZfExtended_Models_LoginLog::addFailed(empty($user) ? 'No User given' : $user->getLogin(), "openid");

            $this->view->errors = true;
            //when an openid exceptions happens so send the user simplified info message, more should be found in the error log
            $this->_form->addError($this->_translate->_('Anmeldung mit Single Sign On schlug fehl, bitte versuchen Sie es erneut.'));
            $log = Zend_Registry::get('logger');
            /* @var $log ZfExtended_Logger */
            
            switch ($e->getErrorCode()) {
                case 'E1165':
                    $log->exception($e, ['level' => $log::LEVEL_INFO]);
                    return;
                case 'E1328':
                    $this->_form->addError($e->getMessage());
                default:
                    $log->exception($e);
                    return;
            }
        }
    }
    
    /***
     * Sign out if the openid provider supports sign out and the end_session_endpoint is defined in the wellknow config,
     */
    protected function handleOpenIdLogout(){
        $oidc = new ZfExtended_OpenIDConnectClient($this->getRequest());
        //the openid authentication is valid
        if($oidc->isOpenIdCustomerSet()){
            $userSession = new Zend_Session_Namespace('openId');
            $idToken=$userSession->data->idToken ? $userSession->data->idToken : null;
            $userSession=null;
            if($idToken){
                try {
                    $oidc->signOut($idToken,$oidc->getRedirectDomainUrl());
                } catch (Exception $e) {
                    
                }
            }
        }
    }

    /***
     * Inject javascript into the login view
     */
    protected function appendCustomScript(){
        //this is the required openid javascript for the login form.
        //because of the client specific overides we are not able to invoke this script as separate file!
        $openIdScript = "
            function checkHash() {
                var loginForm = document.getElementById('loginForm'),
                    redirectField = document.getElementById('openidredirect'),
                    redirecthashField = document.getElementById('redirecthash'),
                    rgx = /^(?:f(?:alse)?|no?|0+)$/i,
                    hasErrors = document.getElementsByClassName('errors').length>0;
                
                //append the current hash to the form submit action. With this the hash is perserved for the editor.
                loginForm.action+=(window.location.hash);

                if(redirecthashField){
                    //store the hash in field, it is required for the openid
                    redirecthashField.value=(window.location.hash);
                }

                if(redirectField != null && (!rgx.test(redirectField.value) && !!redirectField.value)){
                    //redirect directly to the open id sso. This is needed, since this is the only way to perserve the hash when we redirect directly to the openid sso
                    redirectField.value = 'openid';
                    //if there is no sso error, submit the form. This case is only when we redirect without showing translate5 login form
                    !hasErrors && loginForm.submit()
                }
            }
            if (window.addEventListener)
                window.addEventListener('load', checkHash, false);
            else if (window.attachEvent)
                window.attachEvent('onload', checkHash);
            else window.onload = checkHash;
        ";
        $renderer = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'ViewRenderer'
        );
        $renderer->view->headScript()->appendScript($openIdScript);
    }

    protected function handleRedirectHash(){

        if(!isset($this->_session->redirecthash)){
            return null;
        }

        $hash = $this->_session->redirecthash;
        if(preg_match('~^#name=(termportal|instanttranslate|itranslate)~', $hash, $matches)){
            // Drop redirecthash prop from session
            $this->_session->redirecthash = '';
            $hash = $matches[1];
            //TODO: after itranslate route is changed to instanttranslate this should be removed
            if($hash === 'instanttranslate'){
                $hash = 'itranslate';
            }
            return '#'.$hash;
        }

        return $hash;
    }
}