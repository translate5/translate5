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

/***
 */
class Editor_AppsController extends ZfExtended_Controllers_Action {
    
    public function indexAction(){
        Zend_Layout::getMvcInstance()->setLayout('apps');
        Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH.'/modules/editor/layouts/scripts');
        $config = Zend_Registry::get('config');
        $this->view->render('apps/layoutConfig.php');
        $this->view->appVersion = ZfExtended_Utils::getAppVersion();
        
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        
        $this->view->Php2JsVars()->set('restpath',APPLICATION_RUNDIR.'/'.Zend_Registry::get('module').'/');
        $this->view->Php2JsVars()->set('apps.appName',$this->getRequest()->getParam('name'));
        $this->view->Php2JsVars()->set('apps.apiUrl',$this->getRequest()->getParam('apiUrl'));
        
        $this->view->Php2JsVars()->set('apps.title',$translate->_('translate5-apps'));
        $this->view->Php2JsVars()->set('apps.instanttranslate.title',$translate->_('translate5-instanttranslate'));
        $this->view->Php2JsVars()->set('apps.termportal.title',$translate->_('translate5-termportal'));
        
        $this->view->Php2JsVars()->set('apps.loginUrl', APPLICATION_RUNDIR.$config->runtimeOptions->loginUrl);
    }
    
    /***
     * Update the last used app for the current user
     */
    public function lastusedappAction() {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $appName=$this->getRequest()->getParam('appName');
        if(empty($appName)){
            return;
        }
        
        //TODO: when more apps, create service classes
        $validApps=['instanttranslate','termportal'];
        if(!in_array($appName, $validApps)){
            return;
        }
        
        $userId = (new Zend_Session_Namespace('user'))->data->id;
        $meta=ZfExtended_Factory::get('editor_Models_UserMeta');
        /* @var $meta editor_Models_UserMeta */
        $meta->saveLastUsedApp($userId, $appName);
    }
}