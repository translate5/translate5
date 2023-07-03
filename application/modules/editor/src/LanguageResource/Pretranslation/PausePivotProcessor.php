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

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\Pretranslation;

use editor_Models_Task as Task;
use MittagQI\Translate5\PauseWorker\PauseWorkerProcessorInterface;
use MittagQI\Translate5\LanguageResource\TaskPivotAssociation;
use MittagQI\Translate5\PauseWorker\AbstractLanguageResourcesProcessor;
use Zend_Config;
use Zend_Registry;
use ZfExtended_Factory;

class PausePivotProcessor extends AbstractLanguageResourcesProcessor implements PauseWorkerProcessorInterface
{
    private Zend_Config $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = Zend_Registry::get('config');
    }

    public function shouldWait(Task $task): bool
    {
        $taskPivotAssoc = ZfExtended_Factory::get(TaskPivotAssociation::class);
        $languageResourceIds = array_column(
            $taskPivotAssoc->loadTaskAssociated($task->getTaskGuid()),
            'languageResourceId'
        );
        $languageResourceIds = array_map('intval', $languageResourceIds);

        return $this->areStillImporting($task, ...$languageResourceIds);
    }

    public function getMaxWaitTimeSeconds(): int
    {
        return $this->config->runtimeOptions->worker->{PausePivotWorker::class}->maxPauseTime;
    }

    public function getSleepTimeSeconds(): int
    {
        return 5;
    }
}
