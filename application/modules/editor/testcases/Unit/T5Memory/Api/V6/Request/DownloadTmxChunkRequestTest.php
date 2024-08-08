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

namespace MittagQI\Translate5\Test\Unit\T5Memory\Api\V6\Request;

use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmxChunkRequest;
use PHPUnit\Framework\TestCase;

class DownloadTmxChunkRequestTest extends TestCase
{
    public function testCreation(): void
    {
        $request = new DownloadTmxChunkRequest('http://example.com', 'tmName');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.com/tmName/download.tmx', (string) $request->getUri());
        self::assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
    }

    public function testCreationWithLimit(): void
    {
        $request = new DownloadTmxChunkRequest('http://example.com', 'tmName', 10);

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.com/tmName/download.tmx', (string) $request->getUri());
        self::assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
        self::assertSame(
            json_encode(
                [
                    'limit' => 10,
                ],
                JSON_PRETTY_PRINT
            ),
            $request->getBody()->getContents()
        );
    }

    public function testCreationWithOffset(): void
    {
        $request = new DownloadTmxChunkRequest('http://example.com', 'tmName', startFromInternalKey: '10:1');

        self::assertSame('GET', $request->getMethod());
        self::assertSame('http://example.com/tmName/download.tmx', (string) $request->getUri());
        self::assertSame('UTF-8', $request->getHeaderLine('Accept-charset'));
        self::assertSame('application/xml', $request->getHeaderLine('Accept'));
        self::assertSame(
            json_encode(
                [
                    'startFromInternalKey' => '10:1',
                ],
                JSON_PRETTY_PRINT
            ),
            $request->getBody()->getContents()
        );
    }

    public function testThrowInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DownloadTmxChunkRequest('http://example.com', 'tmName', startFromInternalKey: '10-1');
    }
}
