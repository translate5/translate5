<?php

namespace MittagQI\Translate5\Task\Import\Defaults;

use editor_Models_Task as Task;

interface ITaskDefaults
{
    public function applyDefaults(Task $task, bool $importWizardUsed = false): void;
}
