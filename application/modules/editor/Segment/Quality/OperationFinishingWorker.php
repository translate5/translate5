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

use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\Segment\Quality\Event\AutoQaFinishedEvent;

/**
 * Finishes Operations regarding the Quality processing
 */
class editor_Segment_Quality_OperationFinishingWorker extends editor_Models_Task_AbstractWorker
{
    /**
     * This defines the processing mode for the segments we process
     * This worker is used in various situations
     * @var string
     */
    private $processingMode;

    private bool $skipCheck;

    protected function validateParameters(array $parameters): bool
    {
        $this->skipCheck = (bool) ($parameters['skipCheck'] ?? false);

        // required param steers the way the segments are processed: either directly or via the LEK_segment_tags
        if (array_key_exists('processingMode', $parameters)) {
            $this->processingMode = $parameters['processingMode'];

            return true;
        }

        return false;
    }

    protected function work(): bool
    {
        // write the segments back to the segments model
        editor_Segment_Quality_Manager::instance()->finishOperation(
            $this->processingMode,
            $this->task,
            $this->skipCheck
        );

        // if not importing, unlock the task if locked (we lock in the operation worker)
        if ($this->processingMode != editor_Segment_Processing::IMPORT && $this->task->isLocked($this->task->getTaskGuid())) {
            $this->task->unlock();
        }

        EventDispatcher::create()->dispatch(new AutoQaFinishedEvent($this->task));

        return true;
    }
}
