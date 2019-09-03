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
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    /**
     * This init code may throw exceptions which are then handled by the calling place 
     */
    protected function initThrowable() {
        $this->serviceType  = $this->service->getServiceNamespace();
        $this->serviceName  = $this->service->getName();
        $this->serviceColor = $this->service->getDefaultColor();
    }
    
    protected function validateConfig() {
        $config = $this->getConfig()->toArray();
        if(empty($config)) {
            error_log("NEC-TM: No config given");
            return false;
        }
        if(empty($config['server'])) {
            error_log("NEC-TM: No server config given");
            return false;
        }
        if(empty($config['credentials']) || !is_array($config['credentials'])) {
            error_log("NEC-TM: No credentials config given or is no array");
            return false;
        }
        return true;
    }
}
