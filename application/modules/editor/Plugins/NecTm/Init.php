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


class editor_Plugins_NecTm_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * @var editor_Plugins_NecTm_HttpApi
     */
    protected $api;
    
    /**
     * @var editor_Plugins_NecTm_Service
     */
    protected $service;
    
    /**
     * @var string
     */
    protected $serviceType;
    
    /**
     * @var string
     */
    protected $serviceName;
    
    /**
     * @var string
     */
    protected $serviceColor;
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginNecTmMain' => 'Editor.plugins.NecTm.controller.Main',
    );
    
    public function init() {
        if(!$this->validateConfig()) {
            $this->frontendControllers = []; //disable frontend stuff if no valid config
            return;
        }
        
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        $serviceManager->addService('editor_Plugins_NecTm');
        
        $this->service = ZfExtended_Factory::get('editor_Plugins_NecTm_Service');
        /* @var $service editor_Plugins_NecTm_Service */
        
        $this->eventManager->attach('editor_LanguageresourceinstanceController', 'beforeIndexAction', array($this, 'synchronizeNecTmCategories'));
        $this->eventManager->attach('Editor_CategoryController', 'afterIndexAction', array($this, 'filterToNECCategories'));
        $this->eventManager->attach('editor_LanguageresourceinstanceController', 'beforePostAction', array($this, 'validateParams'));
        
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->Php2JsVars()->set('plugins.NecTm.topLevelCategories', $this->service->getTopLevelCategoriesIds());
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * This init code may throw exceptions which are then handled by the calling place
     */
    protected function initThrowable() {
        $this->serviceType         = $this->service->getServiceNamespace();
        $this->serviceName         = $this->service->getName();
        $this->serviceColor        = $this->service->getDefaultColor();
    }
    
    protected function validateConfig() {
        $config = $this->getConfig()->toArray();
        $logger = Zend_Registry::get('logger');
        /* @var $logger ZfExtended_Logger */
        if(empty($config)) {
            //$logger->error('E1180', 'NEC-TM: No config given');
            return false;
        }
        if(empty($config['server'])) {
            //$logger->error('E1180', 'NEC-TM: No server config given');
            return false;
        }
        if(empty($config['credentials']) || !is_array($config['credentials'])) {
            //$logger->error('E1180', 'NEC-TM: No credentials config given or is no array');
            return false;
        }
        return true;
    }
    
    /**
     * For result-list in view:
     * - use NEC-TM-Categories only
     * - sort by label
    * @param Zend_EventManager_Event $event
     */
    public function filterToNECCategories(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $allCategories = $view->rows;
        foreach ($allCategories as $key => $category) {
            if ($category['origin'] != $this->service::CATEGORY_ORIGIN) {
                unset($allCategories[$key]);
            }
        }
        usort($allCategories, function ($a, $b) {
            return strtolower($a['label']) <=> strtolower($b['label']);
        });
        //INFO: rebuild the array keys, array keys like: [0],[1],[5],[7] messed up the extjs resource store
            $allCategories = array_values($allCategories);
            $view->rows = $allCategories;
            $view->total= count($allCategories);
    }
    
    /**
     * Queries NEC TM for all categories that can be accessed with the system credentials in NEC TM.
     * The existing categories are saved in the translate5 DB. Categories that already exist in translate5 DB,
     * but do not exist any more in NEC TM, are removed from the DB and from all language resource associations.
     */
    public function synchronizeNecTmCategories() {
        // Run the snych as worker to not block other processes, especially if the api-server is slow or even down.
        $worker = ZfExtended_Factory::get('editor_Plugins_NecTm_Worker');
        /* @var $worker editor_Plugins_NecTm_Worker */
        if (!$worker->init()) {
            $logger = Zend_Registry::get('logger');
            /* @var $logger ZfExtended_Logger */
            $logger->error('E1180', 'NEC-TM: Worker could not be initialized');
            return;
        }
        $worker->queue();
    }
    
    /**
     * Validate params as needed for NEC-TM.
     * @param Zend_EventManager_Event $event
     */
    public function validateParams(Zend_EventManager_Event $event) {
        $params = $event->getParam('params','');
        if ($params['serviceType'] != $this->service->getServiceNamespace()) {
            return;
        }
        $categories = $params['categories'] ?? '';
        $this->service->validateCategories($categories);
    }
}
