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

namespace MittagQI\Translate5\Task\BatchSet;

use editor_Models_TaskUserAssoc;

class BatchSetDeadlineDate extends BatchSetAbstract
{
    public function update(array $taskGuids): void
    {
        $deadlineDate = $this->request->getParam('deadlineDate');
        $workflow = $this->request->getParam('batchWorkflow');
        $workflowStep = $this->request->getParam('batchWorkflowStep');

        if (empty($workflow) || empty($workflowStep)) {
            $this->logger->error('E1012', 'Missing workflow' . (empty($workflow) ? '' : 'Step') . ' parameter for batch update');

            return;
        }

        try {
            $deadlineDate = (new \DateTime($deadlineDate))->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            $this->logger->exception($e);

            return;
        }

        $tuaModel = new editor_Models_TaskUserAssoc();
        $tuas = $tuaModel->loadByTaskGuidList($taskGuids, $workflow, $workflowStep);
        foreach ($tuas as $tua) {
            $tuaModel->load($tua['id']);
            $prevDeadline = $tuaModel->getDeadlineDate();
            $tuaModel->setDeadlineDate($deadlineDate);
            $tuaModel->save();
            $this->logger->info('E1012', 'job deadline changed (batch set)', [
                'previous' => $prevDeadline,
                'tua' => $tuaModel->getSanitizedEntityForLog(),
                'task' => $tua['taskGuid'],
            ]);
        }
    }
}
