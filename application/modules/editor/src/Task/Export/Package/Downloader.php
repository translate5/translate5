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

namespace MittagQI\Translate5\Task\Export\Package;

use editor_Models_Task;
use MittagQI\Translate5\Task\Export\Exported\PackageWorker;
use MittagQI\ZfExtended\Controller\Response\Header;
use Zend_Session;
use ZfExtended_Factory;

/**
 *
 */
class Downloader
{
    public const TASK_PACKAGE_EXPORT_STATE = 'PackageExport';

    /**
     * @param editor_Models_Task $task
     * @param bool $diff
     * @return int
     */
    public function downloadPackage(editor_Models_Task $task, bool $diff): int
    {

        // Turn off limitations?
        ignore_user_abort(1);

        set_time_limit(0);

        $worker = ZfExtended_Factory::get(Worker::class);
        $exportFolder = $worker->initExport($task, $diff);

        $workerId = $worker->queue();

        $worker = ZfExtended_Factory::get(PackageWorker::class);

        $contextParams = [
            'exportFolder' => $exportFolder,
            'zipFileName' => $workerId,
            'cookie' => Zend_Session::getId()
        ];

        $worker->setup($task->getTaskGuid(), $contextParams);

        $packageWorkerId = $worker->queue($workerId);

        return $packageWorkerId;
    }

}