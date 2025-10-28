<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Import;

use editor_Models_Import_Configuration;
use editor_Models_Import_DataProvider_Abstract;
use editor_Models_Task;
use MittagQI\Translate5\Task\Meta\TaskMetaDTO;
use MittagQI\Translate5\Task\Meta\TaskMetaImmutableDTO;
use ZfExtended_EventManager;
use ZfExtended_Factory;

class ImportEventTrigger
{
    /* Our triggered Events, in the order they'll be triggered */
    public const IMPORT_WORKER_QUEUED = 'importWorkerQueued';

    public const BEFORE_IMPORT = 'beforeImport';

    public const AFTER_UPLOAD_PREPARATION = 'afterUploadPreparation';

    public const AFTER_PROJECT_UPLOAD_PREPARATION = 'afterProjectUploadPreparation';

    public const AFTER_IMPORT = 'afterImport';

    public const AFTER_IMPORT_ERROR = 'afterImportError';

    public const INIT_TASK_META = 'initTaskMeta';

    private ZfExtended_EventManager $events;

    private bool $doDebug = false;

    public function __construct()
    {
        $this->events = ZfExtended_Factory::get(ZfExtended_EventManager::class, [self::class]);
    }

    /**
     * Using this proxy method for triggering the event to keep the legacy code bound to this class
     * instead to the new worker class
     */
    public function triggerAfterImport(
        editor_Models_Task $task,
        int $parentWorkerId,
        editor_Models_Import_Configuration $importConfig
    ): void {
        $this->triggerEvent(self::AFTER_IMPORT, [
            'task' => $task,
            'parentWorkerId' => $parentWorkerId,
            'importConfig' => $importConfig,
        ]);
    }

    /**
     * Using this proxy method for triggering the event to keep the legacy code bound to this class
     * instead to the new worker class
     */
    public function triggerAfterImportError(
        editor_Models_Task $task,
        int $parentWorkerId,
        editor_Models_Import_Configuration $importConfig
    ): void {
        $this->triggerEvent(self::AFTER_IMPORT_ERROR, [
            'task' => $task,
            'parentWorkerId' => $parentWorkerId,
            'importConfig' => $importConfig,
        ]);
    }

    public function triggerImportWorkerQueued(editor_Models_Task $task, int $parentId): void
    {
        $this->triggerEvent(self::IMPORT_WORKER_QUEUED, [
            'task' => $task,
            'workerId' => $parentId,
        ]);
    }

    public function triggerAfterProjectUploadPreparation(
        editor_Models_Task $task,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        TaskMetaImmutableDTO $projectMeta,
    ): void {
        $this->triggerEvent(self::AFTER_PROJECT_UPLOAD_PREPARATION, [
            'task' => $task,
            'metaDTO' => $projectMeta,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Triggers the evaluation of task-meta params,
     * which are used throughout the whole import-process and should be evaluated as early as possible
     */
    public function triggerTaskMetaEvent(editor_Models_Task $task, array $data, TaskMetaDTO $metaDTO): void
    {
        $this->triggerEvent(self::INIT_TASK_META, [
            'task' => $task,
            'metaDTO' => $metaDTO,
            'data' => $data,
        ]);
    }

    public function triggerBeforeImport(
        editor_Models_Task $task,
        editor_Models_Import_Configuration $importConfig
    ): void {
        $this->triggerEvent(self::BEFORE_IMPORT, [
            'task' => $task,
            'importFolder' => $importConfig->importFolder,
        ]);
    }

    public function triggerAfterUploadPreparation(
        editor_Models_Task $task,
        editor_Models_Import_DataProvider_Abstract $dataProvider,
        TaskMetaImmutableDTO $taskMeta,
    ): void {
        $this->triggerEvent(self::AFTER_UPLOAD_PREPARATION, [
            'task' => $task,
            'dataProvider' => $dataProvider,
            'metaDTO' => $taskMeta,
        ]);
    }

    private function triggerEvent(string $event, array $params): void
    {
        if ($this->doDebug) {
            error_log('IMPORT EVENT: ' . $event . ', task: ' . (array_key_exists('task', $params) ? $params['task']->getTaskGuid() : 'null'));
        }
        $this->events->trigger($event, self::class, $params);
    }
}
