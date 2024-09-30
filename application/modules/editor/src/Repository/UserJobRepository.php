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

namespace MittagQI\Translate5\Repository;

use editor_Models_Task as Task;
use editor_Models_TaskUserAssoc as UserJob;
use MittagQI\Translate5\UserJob\TypeEnum;
use Zend_Db_Table_Row;
use ZfExtended_Factory;

class UserJobRepository
{
    public function getEmptyModel(): UserJob
    {
        return ZfExtended_Factory::get(UserJob::class);
    }

    /**
     * @return iterable<UserJob>
     */
    public function getLspJobsByTask(string $taskGuid): iterable
    {
        $tua = ZfExtended_Factory::get(UserJob::class);

        $jobs = $tua->loadByTaskGuidList([$taskGuid]);

        foreach ($jobs as $job) {
            if (TypeEnum::Coordinator === TypeEnum::from((int) $job['type'])) {
                $tua->init(
                    new Zend_Db_Table_Row(
                        [
                            'table' => $tua->db,
                            'data' => $job,
                            'stored' => true,
                            'readOnly' => false,
                        ]
                    )
                );

                yield clone $tua;
            }
        }
    }

    public function save(UserJob $job): void
    {
        $job->save();
    }

    /**
     * @return iterable<UserJob>
     */
    public function getTaskJobs(Task $task, bool $excludePmOverride = false): iterable
    {
        $tua = ZfExtended_Factory::get(UserJob::class);

        $jobs = $tua->loadByTaskGuidList([$task->getTaskGuid()]);

        foreach ($jobs as $job) {
            $tua->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $tua->db,
                        'data' => $job,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            if ($excludePmOverride && $tua->getIsPmOverride()) {
                continue;
            }

            yield clone $tua;
        }
    }
}
