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
 * Initial Class of Plugin "Customer"
 */
class editor_Plugins_Customer_Init extends ZfExtended_Plugin_Abstract {
    
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
            'pluginCustomerCustomer' => 'Editor.plugins.Customer.controller.Customer'
    );
            
    protected $localePath = 'locales';
            
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'Customer')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $this->log = ZfExtended_Factory::get('ZfExtended_Log', array(false));
        $this->initEvents();
        $this->addController('CustomerController');
        $this->initRoutes();
    }
    
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_customer_customer',),
        ));
        $r->addRoute('plugins_customer_restdefault', $restRoute);
        
    }
}