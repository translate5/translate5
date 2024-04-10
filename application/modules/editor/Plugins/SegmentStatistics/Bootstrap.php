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

use MittagQI\Translate5\Task\Import\ImportEventTrigger;

/**
 * Plugin Bootstrap for Segment Statistics Plugin
 */
class editor_Plugins_SegmentStatistics_Bootstrap extends ZfExtended_Plugin_Abstract
{
    protected static string $description = 'Creates term specific segment statistics.';

    /**
     * Just for better readability
     */
    protected string $typeImport = editor_Plugins_SegmentStatistics_Worker::TYPE_IMPORT;

    /**
     * Just for better readability
     */
    protected string $typeExport = editor_Plugins_SegmentStatistics_Worker::TYPE_EXPORT;

    public function init(): void
    {
        $this->blocks(editor_Plugins_SegmentStatistics_BootstrapEditableOnly::class);
        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::AFTER_IMPORT,
            [$this, 'handleAfterImportCreateStat'],
            -90
        );
        //priority -10000 in order to always allow other plugins to modify meta-data before writer runs
        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::AFTER_IMPORT,
            [$this, 'handleImportWriteStat'],
            -10000
        );
        $this->eventManager->attach(
            editor_Models_Export::class,
            'afterExport',
            [$this, 'handleAfterExport'],
            -10000
        );
        $this->eventManager->attach(
            editor_Models_Export::class,
            'afterExport',
            [$this, 'handleExportWriteStat'],
            -10010
        );
    }

    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleAfterImportCreateStat(Zend_EventManager_Event $event): void
    {
        $this->callWorker($event, 'editor_Plugins_SegmentStatistics_Worker', $this->typeImport);
    }

    /**
     * handler for event: editor_Models_Import#afterImport
     * @param $event Zend_EventManager_Event
     */
    public function handleImportWriteStat(Zend_EventManager_Event $event): void
    {
        $this->callWorker($event, 'editor_Plugins_SegmentStatistics_WriteStatisticsWorker', $this->typeImport);
    }

    /**
     * handler for event: editor_Models_Export#afterExport
     */
    public function handleAfterExport(Zend_EventManager_Event $event): void
    {
        $this->callWorker($event, 'editor_Plugins_SegmentStatistics_Worker', $this->typeExport);
    }

    /**
     * handler for event: editor_Models_Export#afterExport
     * @param $event Zend_EventManager_Event
     */
    public function handleExportWriteStat(Zend_EventManager_Event $event): void
    {
        $this->callWorker($event, 'editor_Plugins_SegmentStatistics_WriteStatisticsWorker', $this->typeExport);
    }

    /**
     * @param string $worker worker class name
     * @param string $type im- or export
     */
    protected function callWorker(Zend_EventManager_Event $event, $worker, $type): void
    {
        $parentId = $event->getParam('parentWorkerId');
        $task = $event->getParam('task');
        $worker = ZfExtended_Factory::get($worker);
        /* @var $worker editor_Plugins_SegmentStatistics_Worker */
        $worker->init($task->getTaskGuid(), [
            'type' => $type,
        ]);
        $worker->queue($parentId);
    }
}
