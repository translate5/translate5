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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultLspJob\Operation;

use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Model\DefaultLspJob;
use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DeleteDefaultLspJobOperation;
use MittagQI\Translate5\Repository\DefaultLspJobRepository;
use MittagQI\Translate5\Repository\DefaultUserJobRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class DeleteDefaultLspJobOperationTest extends TestCase
{
    private MockObject|DefaultLspJobRepository $defaultLspJobRepository;

    private MockObject|DefaultUserJobRepository $defaultUserJobRepository;

    private ZfExtended_Logger $logger;

    private DeleteDefaultLspJobOperation $operation;

    public function setUp(): void
    {
        $this->defaultLspJobRepository = $this->createMock(DefaultLspJobRepository::class);
        $this->defaultUserJobRepository = $this->createMock(DefaultUserJobRepository::class);
        $this->logger = $this->createMock(ZfExtended_Logger::class);

        $this->operation = new DeleteDefaultLspJobOperation(
            $this->defaultLspJobRepository,
            $this->defaultUserJobRepository,
            $this->logger,
        );
    }

    public function testDelete(): void
    {
        $defaultUserJob = $this->createMock(DefaultUserJob::class);
        $defaultUserJob->method('__call')->willReturn(1);

        $this->defaultUserJobRepository->method('find')->willReturn($defaultUserJob);

        $this->defaultUserJobRepository
            ->expects(self::once())
            ->method('delete')
            ->with((int) $defaultUserJob->getId());

        $defaultLspJob = $this->createMock(DefaultLspJob::class);
        $defaultLspJob->method('__call')->willReturn(12);

        $this->defaultLspJobRepository
            ->expects(self::once())
            ->method('delete')
            ->with((int) $defaultLspJob->getId());

        $this->operation->delete($defaultLspJob);
    }
}
