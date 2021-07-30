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

/***
 * Enable deletion of user associations not in the user hierarchy.
 * @author aleksandar
 *
 */
class editor_Plugins_DeleteUserAssociations_Init extends ZfExtended_Plugin_Abstract {
    protected static $description = 'Enables deletion of user associations not in the user hierarchy';
    
    /**
     * Initialize the plugn "DeleteUserAssociations"
     * {@inheritDoc}
     * @see ZfExtended_Plugin_Abstract::init()
     */
    public function init() {
        $this->eventManager->attach('Editor_TaskuserassocController', 'afterIndexAction', array($this, 'handleEditableDeletable'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'afterPutAction', array($this, 'handleEditableDeletable'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'afterPostAction', array($this, 'handleEditableDeletable'));
        $this->eventManager->attach('Editor_TaskuserassocController', 'beforeDeleteAction', array($this, 'handleTaskUserAssocBeforeDelete'));
    }
    
    /***
     * Add deletable flag to the assoc record, so in the frontend the user is able to delete and other assoc users
     *
     * @param Zend_EventManager_Event $event
     */
    public function handleEditableDeletable(Zend_EventManager_Event $event) {
        $view = $event->getParam('view');
        //enable the deletable flag
        if(is_array($view->rows)) {
            foreach ($view->rows as &$row){
                if($row['role'] == editor_Workflow_Default::ROLE_TRANSLATORCHECK) {
                    $row['deletable'] = true;
                }
            }
        }
        elseif(is_object($view->rows)) {
            if($view->rows->role == editor_Workflow_Default::ROLE_TRANSLATORCHECK) {
                $view->rows->deletable = true;
            }
        }
    }

    /***
     * Before user assoc delete action handler
     * @param Zend_EventManager_Event $event
     */
    public function handleTaskUserAssocBeforeDelete(Zend_EventManager_Event $event){
        $params = $event->getParam('params');
        if(empty($params['id'])) {
            return;//bound to wrong action? id should exist
        }
        $tua = $event->getParam('entity');
        /* @var $tua editor_Models_TaskUserAssoc */
        $tua->load($params['id']);
        if($tua->getRole() != editor_Workflow_Default::ROLE_TRANSLATORCHECK) {
            return;
        }
        //add the backend right seeAllUsers to the current logged user, so the user is able to delete any assoc users
        $userSession = new Zend_Session_Namespace('user');
        $userData = $userSession->data;
        $acl = ZfExtended_Acl::getInstance();
        $acl->allow($userData->roles, 'backend', 'seeAllUsers');
    }
}
