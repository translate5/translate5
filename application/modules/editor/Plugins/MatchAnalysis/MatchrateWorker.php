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

class editor_Plugins_MatchAnalysis_MatchrateWorker extends editor_Models_Task_AbstractWorker
{
    public function __construct()
    {
        parent::__construct();
        $this->log = $this->log->cloneMe('plugin.matchanalysis');
    }

    protected function validateParameters(array $parameters): bool
    {
        return true;
    }

    public function work(): bool
    {
        if (! $this->taskIsValidForImportAnalysis()) {
            return true;
        }

        try {
            $analysis = new editor_Plugins_MatchAnalysis_MatchrateAnalysis();

            $updateCounter = 0;
            $lastProgress = 0;
            $analysis->analyse($this->task, function ($progress) use (&$updateCounter, &$lastProgress) {
                $updateCounter++;
                $lastProgress = $progress;
                //update the progress on each 100 segments (to prevent from possible deadlocks in worker table).
                if ($updateCounter % 100 == 0) {
                    $this->updateProgress($progress);
                }
            });

            if (! empty($lastProgress)) {
                $this->updateProgress($lastProgress);
            }

            return true;
        } catch (Throwable $e) {
            $this->log->exception($e, [
                'extra' => [
                    'task' => $this->task,
                ],
            ]);

            return false;
        }
    }

    private function taskIsValidForImportAnalysis(): bool
    {
        $taskGuid = $this->task->getTaskGuid();

        $materializedView = new editor_Models_Segment_MaterializedView($taskGuid);
        $db = Zend_Db_Table::getDefaultAdapter();
        $id = $db->fetchOne('SELECT id FROM ' . $materializedView->getName() . ' WHERE matchRate > 0 LIMIT 1');

        if (empty($id)) {
            $this->log->warn(
                'E1100',
                'MatchAnalysis Plug-In: No match rate information can be extracted from the imported files.',
                [
                    'task' => $this->task,
                ]
            );

            return false;
        }

        return true;
    }

    /***
     * Matchrate saving takes 1 % of the import time
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::getWeight()
     */
    public function getWeight(): int
    {
        return 1;
    }
}
