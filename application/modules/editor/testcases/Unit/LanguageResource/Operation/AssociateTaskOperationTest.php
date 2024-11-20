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

namespace MittagQI\Translate5\Test\Unit\LanguageResource\Operation;

use MittagQI\Translate5\LanguageResource\Operation\AssociateTaskOperation;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\LanguageResourceTaskAssocRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssociateTaskOperationTest extends TestCase
{
    private MockObject|LanguageResourceTaskAssocRepository $languageResourceTaskAssocRepository;

    private AssociateTaskOperation $associateTaskOperation;

    protected function setUp(): void
    {
        $this->languageResourceTaskAssocRepository = $this->createMock(LanguageResourceTaskAssocRepository::class);
        $this->associateTaskOperation = new AssociateTaskOperation($this->languageResourceTaskAssocRepository);
    }

    public function provideData(): iterable
    {
        yield [1, bin2hex(random_bytes(16)), true, true];
        yield [2, bin2hex(random_bytes(16)), false, false];
        yield [3, bin2hex(random_bytes(16)), true, false];
        yield [4, bin2hex(random_bytes(16)), false, true];
    }

    /**
     * @dataProvider provideData
     */
    public function testAssociate(
        int $languageResourceId,
        string $taskGuid,
        bool $segmentsUpdatable,
        bool $autoCreateOnImport
    ): void {
        $this->languageResourceTaskAssocRepository->expects(self::once())
            ->method('save')
            ->with(
                self::callback(
                    static function (TaskAssociation $taskAssociation) use (
                        $languageResourceId,
                        $taskGuid,
                        $segmentsUpdatable,
                        $autoCreateOnImport
                    ) {
                        self::assertSame($languageResourceId, $taskAssociation->getLanguageResourceId());
                        self::assertSame($taskGuid, $taskAssociation->getTaskGuid());
                        self::assertSame($segmentsUpdatable, (bool) $taskAssociation->getSegmentsUpdateable());
                        self::assertSame($autoCreateOnImport, (bool) $taskAssociation->getAutoCreatedOnImport());

                        return true;
                    }
                )
            );

        $this->associateTaskOperation->associate(
            $languageResourceId,
            $taskGuid,
            $segmentsUpdatable,
            $autoCreateOnImport,
        );
    }
}
