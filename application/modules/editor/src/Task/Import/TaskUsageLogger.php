<?php
/*
 *
 *
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
declare(strict_types=1);

namespace MittagQI\Translate5\Task\Import;

use editor_Models_task;
use editor_Models_TaskUsageLog;

class TaskUsageLogger
{
    public function __construct(private editor_Models_TaskUsageLog $log)
    {
    }

    /**
     * Handle the task usage log for given entity. This will update the sum counter or insert new record
     * based on the unique key of `taskType`,`customerId`,`yearAndMonth`
     *
     * @param editor_Models_task $task
     */
    public function log(editor_Models_task $task): void
    {
        $this->log->setTaskType($task->getTaskType()->id());
        $this->log->setSourceLang($task->getSourceLang());
        $this->log->setTargetLang($task->getTargetLang());
        $this->log->setCustomerId($task->getCustomerId());
        $this->log->setYearAndMonth(date('Y-m'));
        $this->log->updateInsertTaskCount();
    }
}
