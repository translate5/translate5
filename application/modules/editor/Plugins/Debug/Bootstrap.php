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

use MittagQI\Translate5\Acl\Rights;
use MittagQI\Translate5\Task\Import\ImportEventTrigger;

/**
 * Plugin Bootstrap for Debug Plugin
 */
class editor_Plugins_Debug_Bootstrap extends ZfExtended_Plugin_Abstract
{
    protected static string $description = 'Provides debug information in the GUI - for development';

    public function init(): void
    {
        $this->eventManager->attach(
            Editor_IndexController::class,
            'afterIndexAction',
            [$this, 'handleAfterIndexAction']
        );
        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::AFTER_IMPORT,
            [$this, 'handleAfterImport']
        );
        $this->eventManager->attach(
            editor_Models_Import_Worker_SetTaskToOpen::class,
            'importCompleted',
            [$this, 'handleImportCompleted']
        );
        $this->eventManager->attach(
            editor_Models_Export::class,
            'afterExport',
            [$this, 'handleAfterExport']
        );
        $this->eventManager->attach(
            editor_Models_Export_Exported_Worker::class,
            'exportCompleted',
            [$this, 'handleExportCompleted']
        );
        $this->eventManager->attach(
            editor_TaskController::class,
            'afterTaskOpen',
            [$this, 'handleAfterTaskOpen']
        );

        // allow PMs to read the user assoc auth hashes for clicking user associations to log in as that user
        $acl = ZfExtended_Acl::getInstance();
        $acl->allow('pm', Rights::ID, Rights::READ_AUTH_HASH);
    }

    public function handleAfterIndexAction(Zend_EventManager_Event $event)
    {
        $view = $event->getParam('view');
        $view->Php2JsVars()->set('debug.showTaskGuid', '1'); //shows taskGuid in GUI
    }

    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterImport(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', array($task->getTaskGuid()));
        error_log("Task imported: " . $task->getTaskName() . ' ' . $task->getTaskGuid() . ' (' . $mv->getName() . ')');
    }

    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterTaskOpen(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', array($task->getTaskGuid()));
        error_log("Task opened: " . $task->getTaskName() . ' ' . $task->getTaskGuid() . ' (' . $mv->getName() . ')');
    }

    /**
     * handler for event: editor_Models_Import_Worker_SetTaskToOpen#importCompleted
     * @param $event Zend_EventManager_Event
     */
    public function handleImportCompleted(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        error_log("Task reopened after import: " . $task->getTaskName() . ' ' . $task->getTaskGuid());
    }

    /**
     * handler for event: editor_Models_Export#afterExport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterExport(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        error_log("Task data exported: " . $task->getTaskName() . ' ' . $task->getTaskGuid());
    }

    /**
     * handler for event: editor_Models_Export_Exported_Worker#exportCompleted
     * @param $event Zend_EventManager_Event
     */
    public function handleExportCompleted(Zend_EventManager_Event $event)
    {
        $task = $event->getParam('task');
        /* @var $task editor_Models_Task */
        error_log("Task exported completly: " . $task->getTaskName() . ' ' . $task->getTaskGuid());
    }
}