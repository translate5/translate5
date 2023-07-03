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

/**
 * @deprecated
 * see https://jira.translate5.net/browse/TRANSLATE-3162
 * TODO FIXME: Implement as User-Config
 */
class Editor_AppsController extends ZfExtended_Controllers_Action {

    /**
     * @deprecated for legacy users of this action (old links etc) the dispatching to termportal / instanttranslate is done in the corresponding plugins
     * @throws Zend_Exception
     */
    public function indexAction(){
        \MittagQI\Translate5\Applet\Dispatcher::getInstance()
            ->call($this->getParam('name', null));

        //either we are redirected, or if no redirect target found, we trigger a notfound:
        throw new ZfExtended_NotFoundException;
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

        $validApps = array_keys(\MittagQI\Translate5\Applet\Dispatcher::getInstance()->getHashPathMap());
        if(!in_array($appName, $validApps)){
            return;
        }
        
        $userId = (new Zend_Session_Namespace('user'))->data->id;
        $meta=ZfExtended_Factory::get('editor_Models_UserMeta');
        /* @var $meta editor_Models_UserMeta */
        $meta->saveLastUsedApp($userId, $appName);
    }
}