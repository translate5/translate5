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

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Workflow\Actions;

use editor_Models_Customer_Customer;
use editor_Models_Export_Worker;
use editor_Workflow_Actions_Abstract;
use editor_Workflow_Default;
use MittagQI\Translate5\Plugins\CotiHotfolder\CotiHotfolder;
use MittagQI\Translate5\Plugins\CotiHotfolder\Worker\UploadFinishedTask;
use MittagQI\Translate5\Task\TaskService;
use Zend_Db_Table;
use ZfExtended_Factory;

class ExportReadyProjectAction extends editor_Workflow_Actions_Abstract
{
    private TaskService $taskService;

    public function __construct()
    {
        $this->taskService = new TaskService();
    }

    public function startExportWorker(): void
    {
        if (! str_contains($this->config->task->getForeignId(), CotiHotfolder::PLUGIN_PREFIX)) {
            return;
        }

        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customer->load((int) $this->config->task->getCustomerId());
        $customerConfig = $customer->getConfig();

        if (! $customerConfig->runtimeOptions->plugins?->CotiHotfolder?->enableAutoExport) {
            return;
        }

        $worker = ZfExtended_Factory::get(editor_Models_Export_Worker::class);
        $worker->initFolderExport(
            $this->config->task,
            false,
            CotiHotfolder::getTaskPreparedExportDir($this->config->task)
        );

        $parentExportWorkerId = $worker->queue();
        $projectId = (int) $this->config->task->getProjectId();

        if (! $this->taskService->isProjectFinished($projectId)) {
            return;
        }

        // Any related projects to wait for ?
        $taskIds = $this->getTaskIdsOfRelatedProjectsIfEnded($projectId);
        if ($taskIds === null) {
            return;
        }

        foreach ($this->taskService->getProjectTaskList($projectId) as $task) {
            $taskIds[] = $task->getId();
        }
        $this->queueUploadWorker($this->config->task->getTaskGuid(), $taskIds, $parentExportWorkerId);
    }

    private function queueUploadWorker(string $taskGuid, array $taskIds, int $parentExportWorkerId): void
    {
        $uploadFinishedTask = new UploadFinishedTask();
        $uploadFinishedTask->init($taskGuid, [
            'taskIds' => $taskIds,
        ]);
        $uploadFinishedTask->queue($parentExportWorkerId, startNext: false);
    }

    // returns null if some tasks are still to be finished (not in "workflowEnded" state)
    private function getTaskIdsOfRelatedProjectsIfEnded($projectId): ?array
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $uploadId = $db->fetchOne('SELECT upload_id FROM LEK_coti_project_upload_assoc WHERE project_id=' . $projectId);
        $taskIds = [];
        if (! empty($uploadId)) {
            $relatedProjectIds = $db->fetchCol('SELECT project_id FROM LEK_coti_project_upload_assoc WHERE upload_id=' . $uploadId . ' AND project_id<>' . $projectId);
            if (! empty($relatedProjectIds)) {
                foreach ($relatedProjectIds as $relatedProjectId) {
                    foreach ($this->taskService->getProjectTaskList((int) $relatedProjectId) as $task) {
                        if (editor_Workflow_Default::STEP_WORKFLOW_ENDED !== $task->getWorkflowStepName()) {
                            return null;
                        }
                        $taskIds[] = $task->getId();
                    }
                }
            }
        }

        return $taskIds;
    }
}
