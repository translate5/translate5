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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**
 * Initial Class of Plugin "SpellCheck"
 * Hint: class must be named NOT Bootstrap, otherwise we will get a strange Zend Error
 */
class editor_Plugins_SpellCheck_Init extends ZfExtended_Plugin_Abstract {
    
    /**
     * @var array
     */
    protected $frontendControllers = array(
        'pluginSpellCheck' => 'Editor.plugins.SpellCheck.controller.Editor'
    );
    
    protected $localePath = 'locales';
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    public function init() {
        $this->initEvents();
        $this->addController('SpellCheckQueryController');
        $this->initRoutes();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        /* @var $view Zend_View_Interface */
        //To set config values:
        //$view->Php2JsVars()->set('plugins.SpellCheck.XXX', $this->getConfig()->preloadedTranslationSegments);
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
        $view->Php2JsVars()->get('editor')->htmleditorCss[] = $this->getResourcePath('htmleditor.css');
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
                'editor' => array('plugins_spellcheck_spellcheckquery',
                ),
        ));
        $r->addRoute('plugins_spellcheck_restdefault', $restRoute);
        
        $languagesRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_spellcheck_spellcheckquery/languages',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_spellcheck_spellcheckquery',
                        'action' => 'languages'
                ));
        $r->addRoute('plugins_spellcheck_languages', $languagesRoute);
        
        
        $matchesRoute = new ZfExtended_Controller_RestLikeRoute(
                'editor/plugins_spellcheck_spellcheckquery/matches',
                array(
                        'module' => 'editor',
                        'controller' => 'plugins_spellcheck_spellcheckquery',
                        'action' => 'matches'
                ));
        $r->addRoute('plugins_spellcheck_matches', $matchesRoute);
    }
}
