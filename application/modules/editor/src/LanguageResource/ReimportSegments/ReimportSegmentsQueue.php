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

namespace MittagQI\Translate5\LanguageResource\ReimportSegments;

use DateTimeInterface;
use MittagQI\Translate5\LanguageResource\Exception\ReimportQueueException;
use ZfExtended_Models_Worker;

class ReimportSegmentsQueue
{
    /**
     * @throws ReimportQueueException
     */
    public function queueSnapshot(
        string $taskGuid,
        int $languageResourceId,
        array $options = []
    ): void {
        $worker = new PrepareReimportSegmentsWorker();

        $options['languageResourceId'] = $languageResourceId;

        $success = $worker->init($taskGuid, $options);

        if (! $success) {
            throw new ReimportQueueException();
        }

        $worker->queue();
    }

    public function queueReimport(
        string $taskGuid,
        int $languageResourceId,
        string $runId
    ): void {
        $reimportWorker = new ReimportSegmentsWorker();

        $options = [
            'languageResourceId' => $languageResourceId,
            'runId' => $runId,
        ];

        $success = $reimportWorker->init($taskGuid, $options);

        if (! $success) {
            throw new ReimportQueueException();
        }

        $reimportWorker->queue();
    }

    public function queueReimportDelayed(
        string $taskGuid,
        int $languageResourceId,
        string $runId,
        int $currentRun,
        DateTimeInterface $delayUntil,
        array $reimportOnlyIds = [],
    ): void {
        $reimportWorker = new ReimportSegmentsWorker();

        $options = [
            'languageResourceId' => $languageResourceId,
            'runId' => $runId,
            'currentRun' => $currentRun,
            'reimportOnlyIds' => $reimportOnlyIds,
        ];

        $success = $reimportWorker->init($taskGuid, $options);

        if (! $success) {
            throw new ReimportQueueException();
        }

        $reimportWorker->queue(state: ZfExtended_Models_Worker::STATE_DELAYED);
        $model = $reimportWorker->getModel();
        $model->setDelayedUntil($delayUntil->getTimestamp());
        $model->setDelays($currentRun);
        $model->save();
    }
}
