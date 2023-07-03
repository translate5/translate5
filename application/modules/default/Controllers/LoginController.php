<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
use MittagQI\Translate5\Authentication\OpenId\{
    Client as OpenIdClient,
    ClientException as OpenIdClientException
};

use ZfExtended_Authentication as Auth;

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
       
        //add the redirecthash from loginform to the stored origin
        $this->getHelper('Access')->addHashToOrigin($this->getRequest()->getParam('redirecthash',''));

        parent::indexAction();
        //if the login status is required, try to authenticate with openid connect
        if($this->view->loginStatus==ZfExtended_Authentication::LOGIN_STATUS_OPENID){
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

        if(Auth::getInstance()->getUser()->getLogin() == Zfextended_Models_User::SYSTEM_LOGIN) {
            $this->logoutAction();
        }

        // Redirect to the stored redirectTo target - if any
        $this->getHelper('Access')->redirectToOrigin();

        //if we are not redirected, then we try to load the possible applet:
        \MittagQI\Translate5\Applet\Dispatcher::getInstance()->dispatch(); //no redirection was given, so dispatch by default

        //if the applet dispatcher is not redirecting us, we trigger NoAccess
        throw new ZfExtended_NoAccessException("No initial_page resource is found.");
    }

    /***
     * Check if the current request is valid for the openid. If it is a valid openid request, the user
     * login will be redirected to the openid client server
     */
    protected function handleOpenIdRequest() {
        $oidc = new OpenIdClient($this->getRequest());
        //the openid authentication is valid
        
        $isCustomerSet=$oidc->isOpenIdCustomerSet();
        if(!$isCustomerSet){
            return;
        }

        //add form hidden field, which is used when redirect to openid is needed
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
        }else{
            // Add overlay to the login page only when the user is automatically redirected to the SSO auth provider
            $overlay = new Zend_Form_Element_Note([
                'name' => 'overlay',
                'value' => '<div style="position: absolute;top: 50%;left: 50%;font-size: 50px;color: white;transform: translate(-50%,-50%);-ms-transform: translate(-50%,-50%);">'.$this->_translate->_('Redirect to login...').'</div>',
                'decorators' => [
                    ['ViewHelper'],
                    ['HtmlTag', [
                        'tag' => 'div',
                        'id' => 'overlay',
                        'style' => 'position: fixed;display: block;width: 100%;height: 100%;top: 0;left: 0;right: 0;bottom: 0;background-color: rgba(175,175,175,1);z-index: 10001;'
                    ]],

                ]
            ]);
            $this->_form->addElement($overlay);
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

                $auth = Auth::getInstance();
                $auth->authenticateUser($user);
                ZfExtended_Models_LoginLog::addSuccess($auth, "openid");

                ZfExtended_Session::updateSession(true,true);

                $this->initDataAndRedirect();
            }
        } catch (OpenIdClientException $e) {
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
        $oidc = new OpenIdClient($this->getRequest());
        //the openid authentication is valid
        if($oidc->isOpenIdCustomerSet()){
            $userSession = new Zend_Session_Namespace('openId');
            $idToken = $userSession->data->idToken ?: null;
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
                    //transmit the hash to the server for proper redirect after login
                    redirecthashField.value=(window.location.hash);
                }

                if(redirectField?.value && !rgx.test(redirectField.value) ){
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
}