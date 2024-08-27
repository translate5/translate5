<?php

namespace MittagQI\Translate5\Task\Deadline;

use editor_Models_TaskUserAssoc;

class TaskUserAssociationUpdater
{
    public function __construct(
        private readonly editor_Models_TaskUserAssoc $taskUserAssocModel
    ) {
    }

    public function updateDeadlines(array $tuas, string $newDeadlineDate): void
    {
        foreach ($tuas as $tua) {
            $this->taskUserAssocModel->load($tua['id']);
            $this->taskUserAssocModel->setDeadlineDate($newDeadlineDate);
            $this->taskUserAssocModel->save();
        }
    }
}
