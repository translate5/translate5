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

/**#@+
 * @author Marc Mittag
 * @package translate5
 * @version 1.0
 *
 */
require_once'ControllerMixIns.php';
/**
 * Klasse der Nutzermethoden
 */
class LoginController extends ZfExtended_Controllers_Login {
    use ControllerMixIns;
    public function init(){
        parent::init();
        $this->view->languageSelector();
        $this->_form   = new ZfExtended_Zendoverwrites_Form('loginIndex.ini');
    }
    
    public function indexAction() {
        $lock = ZfExtended_Factory::get('ZfExtended_Models_Db_SessionUserLock');
        /* @var $lock ZfExtended_Models_Db_SessionUserLock */
        $this->view->lockedUsers = $lock->getLocked();
        
        parent::indexAction();
        //if the login status is required, try to authenticate with openid connect
        if($this->view->loginStatus==ZfExtended_Models_SessionUserInterface::LOGIN_STATUS_REQUIRED){
            $this->handleOpenIdRequest();
        }
    }
    
    public function doOnLogout() {
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
        if($acl->isInAllowedRoles($roles, 'initial_page','editor')) {
            header ('HTTP/1.1 302 Moved Temporarily');
            header ('Location: '.APPLICATION_RUNDIR.'/editor');
        }
        //check if the user contains the termportal right
        elseif($acl->isInAllowedRoles($roles, 'initial_page', 'termPortal')) {
            header ('HTTP/1.1 302 Moved Temporarily');
            
            //redirect the term portal via apps route
            $apiUrl=APPLICATION_RUNDIR.'/editor/termportal';
            $appName='termportal';
            $url=APPLICATION_RUNDIR.'/editor/apps?name='.$appName.'&apiUrl='.$apiUrl;
            
            //same check for new term search page
            header ('Location: '.$url);
        }//check if the user contains the instanttranslate right
        elseif($acl->isInAllowedRoles($roles, 'initial_page', 'instantTranslatePortal')) {
            header ('HTTP/1.1 302 Moved Temporarily');
            
            //redirect the instanttranslate portal via apps route
            $apiUrl=APPLICATION_RUNDIR.'/editor/instanttranslate';
            $appName='instanttranslate';
            $url=APPLICATION_RUNDIR.'/editor/apps?name='.$appName.'&apiUrl='.$apiUrl;
            
            header ('Location: '.$url);
        }else {
            throw new ZfExtended_NoAccessException("No initial_page resource is found.");
        }
        exit;
    }
    
    /***
     * Check if the current request is valid for the openid. If it is a valid openid request, the user
     * login will be redirected to the openid client server
     */
    protected function handleOpenIdRequest() {
        $oidc = new ZfExtended_OpenIDConnectClient($this->getRequest());
        //the openid authentication is valid
        
        //set the redirect label if the customer exist
        if($oidc->isOpenIdCustomerSet() && !$oidc->getCustomer()->getOpenIdRedirectCheckbox()){
            
            //add form hidden field, which is used when redirec to openid is needed
            $redirect = new Zend_Form_Element_Hidden('redirect');
            $this->_form->addElement($redirect);
            
            //create the link field which needs to redirect the login via the configured openid server
            $link=new Zend_Form_Element_Note(array(
                'name' => 'openidredirect',
                'value' => $oidc->getCustomer()->getOpenIdRedirectLabel(),
                'decorators' => array(
                    array('ViewHelper'),
                    array('HtmlTag', array(
                        'tag' => 'a',
                        'href' => 'javascript:void(0);',
                        'onclick'=>'document.getElementById("redirect").value="openid"; document.getElementById("submit").click();'
                    )),
                    
                )
            ));
            $link->setOrder(0);
            $this->_form->addElement($link);
        }
        //authenticate with the configured openid client
        if($oidc->authenticate()){
            //create the user in the translate5 system or update if the user already exist
            $user = $oidc->createUser();
            if(!$user){
                return;
            }
            //init the user session and redirect to the editor
            
            $invalidLoginCounter = ZfExtended_Factory::get('ZfExtended_Models_Invalidlogin',array($user->getLogin()));
            /* @var $invalidLoginCounter ZfExtended_Models_Invalidlogin */
            $invalidLoginCounter->resetCounter(); // bei erfolgreichem login den counter zurücksetzen
            $this->_userModel->setUserSessionNamespaceWithoutPwCheck($user->getLogin());
            $this->getFrontController()->getPlugin('ZfExtended_Controllers_Plugins_SessionRegenerate')->updateSession(true);
            $this->initDataAndRedirect();
        }
    }
}