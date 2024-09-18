<?php
/*
 START LICENSE AND COPYRIGHT

  This file is part of translate5

  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Segment;

use editor_Models_Task;
use editor_Segment_Tags;
use MittagQI\Translate5\Service\AbstractHttpService;
use ZfExtended_Exception;

abstract class AbstractProcessor
{
    /**
     * This prop can be used to adjust the batched/looped processing. If set, this time is slept between saving the processed segments and fetching unprocessed ones
     * @var int: milliseconds
     */
    protected int $loopingPause = 0;

    protected editor_Models_Task $task;

    protected AbstractHttpService $service;

    protected string $serviceUrl;

    protected string $processingMode;

    protected bool $isWorkerContext;

    /**
     * @throws ZfExtended_Exception
     */
    public function __construct(
        editor_Models_Task $task,
        AbstractHttpService $service,
        string $processingMode,
        string $serviceUrl = null,
        bool $isWorkerContext = false
    ) {
        $this->task = $task;
        $this->service = $service;
        $this->processingMode = $processingMode;
        $this->serviceUrl = $serviceUrl ?? $service->getServiceUrl();
        $this->isWorkerContext = $isWorkerContext;
    }

    /**
     * Processes a batch of segments (as normally done in the workers in a loop)
     * @param editor_Segment_Tags[] $segmentsTags
     */
    abstract public function processBatch(array $segmentsTags);

    /**
     * Processes a single segment (either for a retag in the worker or when editing segments via the frontend)
     * HINT: do not process stuff for the whole task, this API is called for single-segment actions!
     * IMPORTANT: after processing, editor_Segment_Tags::save() MUST be callsed when $saveTags is given !!
     */
    abstract public function process(editor_Segment_Tags $segmentTags, bool $saveTags = true);

    /**
     * Retrieves the ID of the bound service
     */
    public function getServiceId(): string
    {
        return $this->service->getServiceId();
    }

    /**
     * Retrieves the url of the bound service, which is usually set as constructor-argument
     */
    public function getServiceUrl(): string
    {
        return $this->serviceUrl;
    }

    /**
     * Retrieves the size of the batch we wish to process
     */
    public function getBatchSize(): int
    {
        return 1;
    }

    /**
     * Adds slepping-time between caving the processed & fetching unprocessed segments in looped processing
     */
    public function getLoopingPause(): int
    {
        return $this->loopingPause;
    }

    /**
     * Special interceptor-API that can prevent a processing-worker to process
     * It will be called by every worker before starting the work
     * This is meant for situations, where in the queuing phase of the workers conditions may are not yet set that affect, if a processing is neccessary or not
     * Note, that the worker inited the processor before so dependencies are set
     */
    public function prepareWorkload(int $workerIndex): bool
    {
        return true;
    }
}
