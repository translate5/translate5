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

namespace MittagQI\Translate5\Test\Unit\CoordinatorGroup\Operations;

use MittagQI\Translate5\CoordinatorGroup\Model\CoordinatorGroup;
use MittagQI\Translate5\CoordinatorGroup\Operations\CoordinatorGroupCreateOperation;
use MittagQI\Translate5\Repository\CoordinatorGroupRepository;
use PHPUnit\Framework\TestCase;

class CoordinatorGroupCreateOperationTest extends TestCase
{
    public function parentIdProvider(): array
    {
        return [
            [null],
            [1],
        ];
    }

    /**
     * @dataProvider parentIdProvider
     */
    public function testCreateCoordinatorGroup(?int $parentId): void
    {
        $groupRepository = $this->createMock(CoordinatorGroupRepository::class);

        $mock = new class($parentId) extends CoordinatorGroup {
            public function __construct(
                private ?int $parentId
            ) {
            }

            public function setDescription(string $description): void
            {
                TestCase::assertSame('description', $description);
            }

            public function setName(string $name): void
            {
                TestCase::assertSame('name', $name);
            }

            public function setParentId(?int $parentId): void
            {
                TestCase::assertSame($this->parentId, $parentId);
            }
        };

        $groupRepository->method('getEmptyModel')->willReturn($mock);
        $groupRepository->expects(self::once())->method('save');

        $service = new CoordinatorGroupCreateOperation(
            $groupRepository,
        );

        $group = $service->createCoordinatorGroup('name', 'description', $parentId);

        self::assertInstanceOf(CoordinatorGroup::class, $group);
    }
}
