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

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api;

use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentErroneousException;
use MittagQI\Translate5\T5Memory\Api\SegmentUpdateResultValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SegmentUpdateResultValidatorTest extends TestCase
{
    private SegmentUpdateResultValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = SegmentUpdateResultValidator::create();
    }

    public function testEnsureSegmentValidDoesNotThrowWhenResponseIsSuccessful(): void
    {
        $response = $this->createSuccessfulResponse();

        $this->validator->ensureSegmentValid($response);

        static::assertTrue(true);
    }

    public function testEnsureSegmentValidDoesNotThrowWhenResponseFailsWithDifferentErrorMessage(): void
    {
        $response = $this->createFailedResponse('Some other error message');

        $this->validator->ensureSegmentValid($response);

        static::assertTrue(true);
    }

    /**
     * @dataProvider errorMessageProvider
     */
    public function testEnsureSegmentValidHandlesVariousErrorMessages(
        string $errorMessage,
        bool $shouldThrowException
    ): void {
        $response = $this->createFailedResponse($errorMessage);

        if ($shouldThrowException) {
            $this->expectException(SegmentErroneousException::class);
            $this->expectExceptionMessage($errorMessage);
        }

        $this->validator->ensureSegmentValid($response);

        if (! $shouldThrowException) {
            // If no exception is thrown, the test passes
            static::assertTrue(true);
        }
    }

    public function errorMessageProvider(): iterable
    {
        yield 'exact xml error message' => [
            'errorMessage' => 'Error in xml in source or target!',
            'shouldThrowException' => true,
        ];

        yield 'different error message' => [
            'errorMessage' => 'Something weird happened',
            'shouldThrowException' => false,
        ];

        yield 'empty error message' => [
            'errorMessage' => '',
            'shouldThrowException' => false,
        ];
    }

    /**
     * @return ResponseInterface&MockObject
     */
    private function createSuccessfulResponse(): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('successful')->willReturn(true);

        return $response;
    }

    /**
     * @return ResponseInterface&MockObject
     */
    private function createFailedResponse(string $errorMessage): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('successful')->willReturn(false);
        $response->method('getErrorMessage')->willReturn($errorMessage);

        return $response;
    }
}
