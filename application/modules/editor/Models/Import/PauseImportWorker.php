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

namespace MittagQI\Translate5\Models\Import;

/**
 * Base class for pause workers
 */
abstract class PauseImportWorker extends \editor_Models_Task_AbstractWorker
{
    public const PROCESSOR = 'processor';
    
    protected function validateParameters($parameters = []): bool
    {
        return isset($parameters[self::PROCESSOR])
            && class_exists($parameters[self::PROCESSOR])
            && in_array(PauseWorkerProcessorInterface::class, class_implements($parameters[self::PROCESSOR]), true);
    }

    protected function work(): bool
    {
        $params = $this->workerModel->getParameters();

        /** @var PauseWorkerProcessorInterface $processor */
        $processor = \ZfExtended_Factory::get($params[self::PROCESSOR]);

        $sleepTime = $processor->getSleepTimeSeconds();
        $maxTime = $processor->getMaxWaitTimeSeconds();
        $elapsedTime = 0;

        while ($elapsedTime < $maxTime) {
            if (!$processor->shouldWait($this->task)) {
                break;
            }

            $elapsedTime+= $sleepTime;
            sleep($sleepTime);
        }

        return true;
    }
}
