<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

declare(strict_types=1);

namespace MittagQI\Translate5\Task\BatchOperations\Handler;

use MittagQI\Translate5\Export\QueuedExportService;
use MittagQI\Translate5\Task\BatchOperations\BatchExportWorker;
use MittagQI\Translate5\Task\BatchOperations\BatchSetTaskGuidsProvider;
use MittagQI\Translate5\Task\BatchOperations\DTO\TaskGuidsQueryDto;
use MittagQI\Translate5\Task\BatchOperations\Exception\MaintenanceScheduledException;
use MittagQI\Translate5\Task\BatchOperations\TaskBatchExportInterface;
use REST_Controller_Request_Http as Request;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_Logger;
use ZfExtended_Models_Installer_Maintenance;
use ZfExtended_Utils;
use ZfExtended_Zendoverwrites_Translate;

class TaskBatchExport implements TaskBatchExportInterface
{
    private string $token;

    public function __construct(
        private readonly ZfExtended_Logger $logger,
        private readonly BatchSetTaskGuidsProvider $taskGuidsProvider,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('task.batch.export'),
            BatchSetTaskGuidsProvider::create(),
        );
    }

    public function process(Request $request): void
    {
        $this->logger->info(
            'E1012',
            'Batch export',
            [
                'request' => $request->getParams(),
            ]
        );
        // selected checkboxes only
        $taskIds = $request->getParam('taskIds');
        if (empty($taskIds)) {
            return;
        }
        if (ZfExtended_Models_Installer_Maintenance::isLoginLock(30)) {
            throw new MaintenanceScheduledException();
        }

        $allowedTaskIds = $this->taskGuidsProvider->getAllowedTaskIds(TaskGuidsQueryDto::fromRequest($request));
        $taskIds = array_intersect(explode(',', $taskIds), $allowedTaskIds);

        $exportService = QueuedExportService::create();

        $this->token = ZfExtended_Utils::uuid();
        $workerId = BatchExportWorker::queueExportWorker(
            ZfExtended_Authentication::getInstance()->getUserId(),
            $taskIds,
            $exportService->composeExportDir($this->token)
        );

        $exportService->makeQueueRecord($this->token, $workerId, 'export'); //w/o extension
    }

    public function getUrl(): string
    {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        return "/editor/queuedexport/{$this->token}?title=" . $translate->_('Exportieren');
    }
}
