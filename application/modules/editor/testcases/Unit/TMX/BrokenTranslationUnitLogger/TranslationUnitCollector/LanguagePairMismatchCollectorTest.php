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

namespace MittagQI\Translate5\Test\Unit\TMX\BrokenTranslationUnitLogger\TranslationUnitCollector;

use MittagQI\Translate5\TMX\BrokenTranslationUnitLogger\TranslationUnitCollector\LanguagePairMismatchCollector;
use PHPUnit\Framework\TestCase;
use ZfExtended_Logger;

class LanguagePairMismatchCollectorTest extends TestCase
{
    private string $testDir;

    private LanguagePairMismatchCollector $collector;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/language-pair-test-' . uniqid();
        mkdir($this->testDir, 0777, true);

        $this->collector = LanguagePairMismatchCollector::create($this->testDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);
        }
    }

    public function testCollectTUWritesToFile(): void
    {
        $tu = '<tu><tuv xml:lang="en-US"><seg>Test</seg></tuv><tuv xml:lang="de-DE"><seg>Test</seg></tuv></tu>';

        $this->collector->collectTU($tu, []);

        $filename = $this->testDir . '/E1771.tmx';
        $this->assertFileExists($filename);
        $this->assertStringContainsString($tu, file_get_contents($filename));
    }

    public function testCollectTUTracksLanguagePairs(): void
    {
        $tu1 = '<tu><tuv xml:lang="en-US"><seg>Test</seg></tuv><tuv xml:lang="de-DE"><seg>Test</seg></tuv></tu>';
        $tu2 = '<tu><tuv xml:lang="de-DE"><seg>Test2</seg></tuv><tuv xml:lang="en-us"><seg>Test2</seg></tuv></tu>';
        $tu3 = '<tu><tuv xml:lang="fr-FR"><seg>Test3</seg></tuv><tuv xml:lang="es-ES"><seg>Test3</seg></tuv></tu>';

        $this->collector->collectTU($tu1, []);
        $this->collector->collectTU($tu2, []);
        $this->collector->collectTU($tu3, []);

        $logger = $this->createMock(ZfExtended_Logger::class);
        $logger->expects($this->once())
            ->method('__call')
            ->willReturnCallback(
                function ($method, $parameters) {
                    [$code, $message, $extra] = $parameters;

                    TestCase::assertSame('warn', $method);
                    TestCase::assertSame('E1771', $code);
                    TestCase::assertSame("Translation units were skipped as doesn't match language pair of Language resource", $message);
                    TestCase::assertArrayHasKey('skipped_per_language_pair', $extra);
                    TestCase::assertSame(2, $extra['skipped_per_language_pair']['en-us - de-de']);
                    TestCase::assertSame(1, $extra['skipped_per_language_pair']['fr-fr - es-es']);
                }
            );

        $this->collector->writeLog($logger);
    }

    public function testCollectTUHandlesReversedLanguagePairs(): void
    {
        $tu1 = '<tu><tuv xml:lang="en-US"><seg>Test</seg></tuv><tuv xml:lang="de-DE"><seg>Test</seg></tuv></tu>';
        $tu2 = '<tu><tuv xml:lang="de-DE"><seg>Test2</seg></tuv><tuv xml:lang="en-US"><seg>Test2</seg></tuv></tu>';

        $this->collector->collectTU($tu1, []);
        $this->collector->collectTU($tu2, []);

        $logger = $this->createMock(ZfExtended_Logger::class);
        $logger->expects($this->once())
            ->method('__call')
            ->willReturnCallback(
                function ($method, $parameters) {
                    [$code, $message, $extra] = $parameters;

                    TestCase::assertSame('warn', $method);
                    TestCase::assertSame('E1771', $code);
                    TestCase::assertArrayHasKey('skipped_per_language_pair', $extra);
                    TestCase::assertSame(2, $extra['skipped_per_language_pair']['en-us - de-de']);
                }
            );

        $this->collector->writeLog($logger);
    }

    public function testWriteLogDoesNothingWhenNoTUsCollected(): void
    {
        $logger = $this->createMock(ZfExtended_Logger::class);
        $logger->expects($this->never())->method('__call');

        $this->collector->writeLog($logger);
    }

    public function testWriteLogIncludesFilePath(): void
    {
        $tu = '<tu><tuv xml:lang="en-US"><seg>Test</seg></tuv><tuv xml:lang="de-DE"><seg>Test</seg></tuv></tu>';
        $this->collector->collectTU($tu, []);

        $logger = $this->createMock(ZfExtended_Logger::class);
        $logger->expects($this->once())
            ->method('__call')
            ->willReturnCallback(
                function ($method, $parameters) {
                    [$code, $message, $extra] = $parameters;

                    TestCase::assertSame('warn', $method);
                    TestCase::assertSame('E1771', $code);
                    TestCase::assertArrayHasKey('file', $extra);
                    TestCase::assertStringEndsWith('E1771.tmx', $extra['file']);
                }
            );

        $this->collector->writeLog($logger);
    }

    public function testWriteLogMergesExtraData(): void
    {
        $tu = '<tu><tuv xml:lang="en-US"><seg>Test</seg></tuv><tuv xml:lang="de-DE"><seg>Test</seg></tuv></tu>';
        $this->collector->collectTU($tu, []);

        $logger = $this->createMock(ZfExtended_Logger::class);
        $logger->expects($this->once())
            ->method('__call')
            ->willReturnCallback(
                function ($method, $parameters) {
                    [$code, $message, $extra] = $parameters;

                    TestCase::assertSame('warn', $method);
                    TestCase::assertSame('E1771', $code);
                    TestCase::assertArrayHasKey('customKey', $extra);
                    TestCase::assertSame('customValue', $extra['customKey']);
                }
            );

        $this->collector->writeLog($logger, [
            'customKey' => 'customValue',
        ]);
    }

    public function testLogCodeReturnsCorrectCode(): void
    {
        $this->assertSame('E1771', LanguagePairMismatchCollector::logCode());
    }

    public function testMultipleTUsAreAppendedToFile(): void
    {
        $tu1 = '<tu><tuv xml:lang="en-US"><seg>First</seg></tuv><tuv xml:lang="de-DE"><seg>Erste</seg></tuv></tu>';
        $tu2 = '<tu><tuv xml:lang="en-US"><seg>Second</seg></tuv><tuv xml:lang="de-DE"><seg>Zweite</seg></tuv></tu>';

        $this->collector->collectTU($tu1, []);
        $this->collector->collectTU($tu2, []);

        $filename = $this->testDir . '/E1771.tmx';
        $content = file_get_contents($filename);

        $this->assertStringContainsString('First', $content);
        $this->assertStringContainsString('Second', $content);
        $this->assertStringContainsString('Erste', $content);
        $this->assertStringContainsString('Zweite', $content);
    }
}
