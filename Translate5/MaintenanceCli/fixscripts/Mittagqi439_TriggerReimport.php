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
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentsQueue;
use MittagQI\Translate5\LanguageResource\TaskTm\Repository\TaskTmRepository;
use MittagQI\Translate5\LanguageResource\TaskTm\Workflow\Executors\ReimportSegmentsActionExecutor;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\TaskRepository;
use Translate5\MaintenanceCli\FixScript\FixScriptAbstract;

class Mittagqi439_TriggerReimport extends FixScriptAbstract
{
    public function fix(): void
    {
        $select = $this->db->select()
            ->from('Zf_errorlog', ['id'])
            ->where('eventCode = ?', 'E1603')
            ->where('created >= ?', '2025-09-01 00:00:00')
        ;

        $errorLogIds = $this->db->fetchCol($select);
        $errorLogIds = array_map(fn ($id) => (int) $id + 1, $errorLogIds);

        $select = $this->db->select()
            ->from('Zf_errorlog')
            ->where('id IN (?)', $errorLogIds)
        ;
        $entries = $this->db->fetchAll($select);

        $taskRepository = TaskRepository::create();
        $executor = new ReimportSegmentsActionExecutor(
            Zend_Registry::get('logger'),
            new ReimportSegmentsQueue(),
            new LanguageResourceRepository(),
            new TaskTmRepository(),
        );

        foreach ($entries as $entry) {
            if ($entry['eventCode'] !== 'E1547') {
                continue;
            }

            $extra = json_decode($entry['extra'], true);

            $taskGuid = $extra['task'] ?? null;
            $worker = $extra['worker'] ?? null;

            if (! $taskGuid || ! str_contains($worker, 'ReimportSegmentsWorker')) {
                continue;
            }

            try {
                $task = $taskRepository->getByGuid($taskGuid);
            } catch (\MittagQI\Translate5\Task\Exception\InexistentTaskException) {
                continue;
            }

            $this->info('Will trigger reimport for task ' . $taskGuid);

            $executor->reimportSegments($task);
        }
    }
}
