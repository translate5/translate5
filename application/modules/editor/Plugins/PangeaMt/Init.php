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

class editor_Plugins_PangeaMt_Init extends ZfExtended_Plugin_Abstract {
    protected static $description = 'Provides connector to PangeaMT (offered by Pangeanic)';
    
    /**
     * @var editor_Plugins_PangeaMt_Service
     */
    protected editor_Plugins_PangeaMt_Service $service;

    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = [
        'pluginPangeaMtMain' => 'Editor.plugins.PangeaMt.controller.Main',
    ];
    
    public function init() {
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */
        $serviceManager->addService('editor_Plugins_PangeaMt');
        
        $this->service = ZfExtended_Factory::get('editor_Plugins_PangeaMt_Service');
        /* @var $service editor_Plugins_PangeaMt_Service */

        // If the plugin is not configured, remove the front-end controllers
        if (!$this->validateConfig()) {
            $this->frontendControllers = [];
            return;
        }
        // init the events only if the plugin is configured.
        $this->initEvents();
    }
    
    /**
     * define all event listener
     */
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        //set the available engines to a frontend variable
        $engineModel = ZfExtended_Factory::get('editor_Plugins_PangeaMt_Models_Engines');
        /* @var $engineModel editor_Plugins_PangeaMt_Models_Engines */
        try {
            $view->Php2JsVars()->set('LanguageResources.pangeaMtEngines', $engineModel->getAllEngines());
        }
        catch(Exception $e) {
            $view->Php2JsVars()->set('LanguageResources.pangeaMtEngines', []);
        }
    }
    
    /**
     * Check if the user has configured the plug-in so that it can be used.
     * If not, provide information what's missing.
     * Returns true if everything the plug-in needs is configured; otherwise false.
     * @return boolean
     */
    protected function validateConfig(): bool{
        try {
            $config = $this->getConfig()->toArray();
        } catch(ZfExtended_Plugin_Exception $e){
            $config = null;
        }
        if(empty($config)) {
            return false;
        }
        if(empty($config['server'])) {
            return false;
        }
        if(empty($config['apikey'])) {
            return false;
        }
        return true;
    }
}
