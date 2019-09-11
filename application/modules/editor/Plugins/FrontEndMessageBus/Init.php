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
 */
class editor_Plugins_FrontEndMessageBus_Init extends ZfExtended_Plugin_Abstract {
    protected $frontendControllers = array(
        'pluginFrontEndMessageBus' => 'Editor.plugins.FrontEndMessageBus.controller.MessageBus',
    );
    
    public function init() {
        $this->initEvents();
    }
    
    protected function initEvents() {
        //FIXME Dummy call to test stuff
        $this->eventManager->attach('editor_TaskController', 'afterIndexAction', array($this, 'handleTest'));

        // FIXME send the session id to the message bus, so that the user is known and allowed to communicate
        // → idea here: Instead listening to a login event, we just attach to the IndexController. 
        //   If the application is loaded there, we trigger a "connect" to the MessageBus
        //   Then we have the user, the sessionId and so on. 
        //   Then we have to attach to garbage cleaning and logout to remove the session
        //   additionally we should create a sync function, triggered by the periodical cron and triggerable when the MessageBus restarts.
        //   sync should just deliver all sessions with logged in users, and opened tasks (if any) to the MessageBus.
        //   Sensefull?
        
        $this->eventManager->attach('editor_TaskController', 'afterTaskOpen', array($this, 'handleAfterTaskOpen'));
        
        //returns information if the configured okapi is alive / reachable
        $this->eventManager->attach('ZfExtended_Debug', 'applicationState', array($this, 'handleApplicationState'));
    }
    
    /**
     * Checks if the configured okapi instance is reachable
     * @param Zend_EventManager_Event $event
     */
    public function handleApplicationState(Zend_EventManager_Event $event) {
        $applicationState = $event->getParam('applicationState');
        $applicationState->messageBus = new stdClass();
        
        //TODO return information if message bus is alive or not
        $applicationState->messageBus->running = true;
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleTest(Zend_EventManager_Event $event) {
        $taskMsg = ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Messages_Task');
        /* @var $taskMsg editor_Plugins_FrontEndMessageBus_Messages_Task */
        $taskMsg->test();
    }
    
    /**
     * @param Zend_EventManager_Event $event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event) {
        $taskMsg = ZfExtended_Factory::get('editor_Plugins_FrontEndMessageBus_Messages_Task');
        /* @var $taskMsg editor_Plugins_FrontEndMessageBus_Messages_Task */
        $taskMsg->open($event->getParam('task'));
        //FIXME add information about the current user and Session!
    }
}
