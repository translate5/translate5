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

class editor_Plugins_ChangeLog_Init extends ZfExtended_Plugin_Abstract {
    protected static $description = 'Provides change-log information in the GUI';
    
    /**
     * Contains the Plugin Path relativ to APPLICATION_PATH or absolut if not under APPLICATION_PATH
     * @var array
     */
    protected $frontendControllers = array(
        'pluginChangeLogChangelog' => 'Editor.plugins.ChangeLog.controller.Changelog',
    );
    
    protected $localePath = 'locales';
    
    public function getFrontendControllers() {
        return $this->getFrontendControllersFromAcl();
    }
    
    public function init() {
        $this->initEvents();
        $this->addController('ChangelogController');
        $this->initRoutes();
    }
    
    protected function initEvents() {
        $this->eventManager->attach('Editor_IndexController', 'afterIndexAction', array($this, 'injectFrontendConfig'));
        $this->eventManager->attach('Editor_IndexController', 'afterLocalizedjsstringsAction', array($this, 'initJsTranslations'));
    }
    
    public function initJsTranslations(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $view->pluginLocale()->add($this, 'views/localizedjsstrings.phtml');
    }
    
    public function injectFrontendConfig(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        $user = new Zend_Session_Namespace('user');
        $userId = $user->data->id;
        $changelogdb = ZfExtended_Factory::get('editor_Models_Changelog');
        /* @var $changelogdb editor_Models_Changelog */
        
        //-1 when user has not seen any changelogs before
        $lastChangeLogId = $changelogdb->getLastChangelogForUserId($userId);
        
        $result = $changelogdb->moreChangeLogs($lastChangeLogId,$changelogdb->getUsergroup($user->data));
        
        if(empty($result)){
            //when user has seen all new changelogs:
            $lastChangeLogId = 0;
        }
        $view->Php2JsVars()->set('plugins.ChangeLog.lastSeenChangelogId', $lastChangeLogId);
        $view->Php2JsVars()->set('plugins.ChangeLog.jiraIssuesUrl', Zend_Registry::get('config')->runtimeOptions->jiraIssuesUrl);
        
        $view->headLink()->appendStylesheet($this->getResourcePath('plugin.css'));
    }
    
    /**
     * defines all URL routes of this plug-in
     */
    protected function initRoutes() {
        $f = Zend_Registry::get('frontController');
        /* @var $f Zend_Controller_Front */
        $r = $f->getRouter();
        
        $restRoute = new Zend_Rest_Route($f, array(), array(
                'editor' => array('plugins_changelog_changelog',
                ),
        ));
        $r->addRoute('plugins_changelog_restdefault', $restRoute);
 
    }
}
