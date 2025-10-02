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

namespace MittagQI\Translate5\Test\Unit\Workflow\BatchSet;

use MittagQI\Translate5\JobAssignment\UserJob\BatchUpdate\UserJobDeadlineBatchUpdater;
use MittagQI\Translate5\Repository\UserJobRepository;
use MittagQI\Translate5\Task\BatchOperations\BatchSetTaskGuidsProvider;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidDeadlineDateStringProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidWorkflowProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Exception\InvalidWorkflowStepProvidedException;
use MittagQI\Translate5\Task\BatchOperations\Handler\TaskBatchSetDeadlineDate;
use PHPUnit\Framework\TestCase;
use REST_Controller_Request_Http as Request;
use ZfExtended_Logger;

class TaskBatchSetDeadlineDateTest extends TestCase
{
    private const batchType = 'deadlineDate';

    private TaskBatchSetDeadlineDate $taskBatchSetDeadlineDate;

    protected function setUp(): void
    {
        $logger = $this->createMock(ZfExtended_Logger::class);
        $userJobRepository = $this->createMock(UserJobRepository::class);
        $userJobDeadlineBatchUpdater = $this->createMock(UserJobDeadlineBatchUpdater::class);
        $taskGuidsProvider = $this->createMock(BatchSetTaskGuidsProvider::class);
        $this->taskBatchSetDeadlineDate = new TaskBatchSetDeadlineDate($logger, $userJobRepository, $userJobDeadlineBatchUpdater, $taskGuidsProvider);
    }

    public function testSupportsDeadline(): void
    {
        self::assertTrue($this->taskBatchSetDeadlineDate->supports(self::batchType));
    }

    public function testEmptyWorkflow(): void
    {
        $request = self::getRequest();
        $request->setParam('batchWorkflow', '');
        $this->expectException(InvalidWorkflowProvidedException::class);
        $this->taskBatchSetDeadlineDate->process($request);
    }

    public function testEmptyWorkflowStep(): void
    {
        $request = self::getRequest();
        $request->setParam('batchWorkflowStep', '');
        $this->expectException(InvalidWorkflowStepProvidedException::class);
        $this->taskBatchSetDeadlineDate->process($request);
    }

    public function testEmptyDeadlineDate(): void
    {
        $request = self::getRequest();
        $request->setParam('deadlineDate', '');
        $this->expectException(InvalidDeadlineDateStringProvidedException::class);
        $this->taskBatchSetDeadlineDate->process($request);
    }

    public function testIncorrectDeadlineDate(): void
    {
        $request = self::getRequest();
        $request->setParam('deadlineDate', 'DUMMY_STRING');
        $this->expectException(InvalidDeadlineDateStringProvidedException::class);
        $this->taskBatchSetDeadlineDate->process($request);
    }

    private static function getRequest(): Request
    {
        $request = new Request();
        $request->setParam('batchType', self::batchType);
        $request->setParam('batchWorkflow', 'default');
        $request->setParam('batchWorkflowStep', 'translation');
        $request->setParam('deadlineDate', '2025-03-05T09:43:00');

        return $request;
    }
}
