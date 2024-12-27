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

namespace MittagQI\Translate5\Test\Unit\DefaultJobAssignment\DefaultLspJob\Operation\DTO;

use MittagQI\Translate5\DefaultJobAssignment\DefaultLspJob\Operation\DTO\NewDefaultLspJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\NewDefaultJobDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\TrackChangesRightsDto;
use MittagQI\Translate5\DefaultJobAssignment\DTO\WorkflowDto;
use MittagQI\Translate5\DefaultJobAssignment\Exception\InvalidLanguageIdProvidedException;
use MittagQI\Translate5\JobAssignment\UserJob\TypeEnum;
use PHPUnit\Framework\TestCase;

class NewDefaultLspJobTest extends TestCase
{
    public function testZeroAsSourceLanguageIdThrowsError(): void
    {
        $this->expectException(InvalidLanguageIdProvidedException::class);

        new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            0,
            1,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );
    }

    public function testZeroAsTargetLanguageIdThrowsError(): void
    {
        $this->expectException(InvalidLanguageIdProvidedException::class);

        new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            0,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );
    }

    public function testSameSourceAsTargetLanguageIdThrowsError(): void
    {
        $this->expectException(InvalidLanguageIdProvidedException::class);

        new NewDefaultLspJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            1,
            new WorkflowDto(
                'default',
                'translation',
            ),
            null,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );
    }

    public function testFromDefaultJobDto(): void
    {
        $defaultJobDto = new NewDefaultJobDto(
            1,
            '{633a9811-a1f6-4fa8-81f7-2206d7a93ba4}',
            1,
            2,
            new WorkflowDto(
                'default',
                'translation',
            ),
            TypeEnum::Lsp,
            2.5,
            new TrackChangesRightsDto(
                false,
                false,
                false,
            ),
        );

        $newDefaultLspJobDto = NewDefaultLspJobDto::fromDefaultJobDto($defaultJobDto);

        self::assertSame($defaultJobDto->customerId, $newDefaultLspJobDto->customerId);
        self::assertSame($defaultJobDto->userGuid, $newDefaultLspJobDto->userGuid);
        self::assertSame($defaultJobDto->sourceLanguageId, $newDefaultLspJobDto->sourceLanguageId);
        self::assertSame($defaultJobDto->targetLanguageId, $newDefaultLspJobDto->targetLanguageId);
        self::assertEquals($defaultJobDto->workflow, $newDefaultLspJobDto->workflow);
        self::assertSame($defaultJobDto->deadline, $newDefaultLspJobDto->deadline);
        self::assertEquals($defaultJobDto->trackChangesRights, $newDefaultLspJobDto->trackChangesRights);
    }
}
