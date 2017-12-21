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
 * Initial Class of Plugin "GlobalesePreTranslation"
 */
class editor_Plugins_GlobalesePreTranslation_Init extends ZfExtended_Plugin_Abstract {
    
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
            'pluginGlobalesePreTranslationGlobalese' => 'Editor.plugins.GlobalesePreTranslation.controller.Globalese'
    );
            
    protected $localePath = 'locales';
            
    public function init() {
        if(ZfExtended_Debug::hasLevel('plugin', 'GlobalesePreTranslation')) {
            ZfExtended_Factory::addOverwrite('Zend_Http_Client', 'ZfExtended_Zendoverwrites_Http_DebugClient');
        }
        $this->log = ZfExtended_Factory::get('ZfExtended_Log', array(false));
        $this->initEvents();
        $this->addController('GlobaleseController');
        $this->initRoutes();
    }
    
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('editor_Models_Import', 'importWorkerQueued', array($this, 'handleImportWorkerQueued'));
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
                'editor' => array('plugins_globalesepretranslation_globalese',),
        ));
        $r->addRoute('plugins_globalesepretranslation_restdefault', $restRoute);
        
        $groupsRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_globalesepretranslation_globalese/groups',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_globalesepretranslation_globalese',
                        'action' => 'groups'
                ));
        $r->addRoute('plugins_globalesepretranslation_groups', $groupsRoute);
        
        
        $enginesRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_globalesepretranslation_globalese/engines',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_globalesepretranslation_globalese',
                        'action' => 'engines'
                ));
        $r->addRoute('plugins_globalesepretranslation_engines', $enginesRoute);
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        $view->Php2JsVars()->set('plugins.GlobalesePreTranslation.api.username', $this->getConfig()->api->username);
        $view->Php2JsVars()->set('plugins.GlobalesePreTranslation.api.password', $this->getConfig()->api->password);
        $view->Php2JsVars()->set('plugins.GlobalesePreTranslation.api.apiKey', $this->getConfig()->api->apiKey);
        $alreadyExisting = $view->Php2JsVars()->get('segments')->matchratetypes;
        $view->Php2JsVars()->set('segments.matchratetypes', array_merge($alreadyExisting, [
            'globalese' => APPLICATION_RUNDIR.'/editor/plugins/resources/globalesePreTranslation/globalese.png'
        ]));
        
    }
    
    /**
     * The Globalese Worker must be initialized in a non worker request, to have access to the Globalese settings in the session 
     * @param Zend_EventManager_Event $event
     * @return void|boolean
     */
    public function handleImportWorkerQueued(Zend_EventManager_Event $event) {
        $worker = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_Worker');
        /* @var $worker editor_Plugins_GlobalesePreTranslation_Worker */
        
        $globaleseSession = new Zend_Session_Namespace('GlobalesePreTranslation');
        
        //if the auth parameters are not set or the engine and group, then the globalese translation process should not be started
        if($globaleseSession->group==null || $globaleseSession->engine==null || $globaleseSession->apiUsername==null || $globaleseSession->apiKey==null ){
            return;
        }
        
        $task = $event->getParam('task');
        
        //send the parametars from the session in the workier init method parametar
        $params=[
                'group'=>$globaleseSession->group,
                'engine'=>$globaleseSession->engine,
                'apiUsername'=>$globaleseSession->apiUsername,
                'apiKey'=>$globaleseSession->apiKey,
        ];
        
        // init worker and queue it
        // Since it has to be done in a none worker request to have session access, we have to insert the worker before the taskPost 
        if (!$worker->init($task->getTaskGuid(), $params)) {
            $this->log->logError('GlobalesePreTranslation-Error on worker init()', __CLASS__.' -> '.__FUNCTION__.'; Worker could not be initialized');
            return false;
        }
        $worker->queue($event->getParam('workerId'));
        
        //unset the session namespace
        Zend_Session::namespaceUnset('GlobalesePreTranslation');
    }
}