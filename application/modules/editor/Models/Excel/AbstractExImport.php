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

use MittagQI\Translate5\Task\TaskLockService;

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 * abstract base class for ex und import of excel files containing segment-data.
 * TODO: move this whole Excel-package to Segments-folder.
 */
abstract class editor_Models_Excel_AbstractExImport
{
    /**
     * @var ZfExtended_Logger
     */
    protected $log;

    private TaskLockService $lock;

    public function __construct()
    {
        $this->log = Zend_Registry::get('logger')->cloneMe('editor.task.exceleximport');
        $this->lock = TaskLockService::create();
    }

    /**
     * lock the task for editing and set the tasks state to "isExcelExported"
     */
    public function taskLock(editor_Models_Task $task): bool
    {
        return $this->lock->lockTask($task, editor_Models_Task::STATE_EXCELEXPORTED);
    }

    /**
     * lock the task for editing and set the tasks state to "isExcelExported"
     */
    public function taskUnlock(editor_Models_Task $task): bool
    {
        return $this->lock->unlockTask($task);
    }
}
