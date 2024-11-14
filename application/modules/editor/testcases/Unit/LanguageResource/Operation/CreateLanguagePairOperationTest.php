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

use editor_Models_LanguageResources_Languages as LanguagePair;
use editor_Models_Languages as Language;
use MittagQI\Translate5\LanguageResource\Operation\CreateLanguagePairOperation;
use MittagQI\Translate5\Repository\LanguagePairRepository;
use MittagQI\Translate5\Repository\LanguageRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateLanguagePairOperationTest extends TestCase
{
    private MockObject|LanguageRepository $languageRepositoryMock;

    private MockObject|LanguagePairRepository $languagePairRepositoryMock;

    private CreateLanguagePairOperation $createLanguagePairOperation;

    protected function setUp(): void
    {
        $this->languageRepositoryMock = $this->createMock(LanguageRepository::class);
        $this->languagePairRepositoryMock = $this->createMock(LanguagePairRepository::class);
        $this->createLanguagePairOperation = new CreateLanguagePairOperation(
            $this->languageRepositoryMock,
            $this->languagePairRepositoryMock
        );
    }

    public function testCreateLanguagePair(): void
    {
        $languageResourceId = 1;
        $sourceId = 2;
        $targetId = 3;

        $this->languageRepositoryMock->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->getLanguageMock($sourceId),
                $this->getLanguageMock($targetId)
            );

        $this->languagePairRepositoryMock->expects(self::once())
            ->method('save')
            ->with(
                self::callback(
                    static function (LanguagePair $languagePair) use ($languageResourceId, $sourceId, $targetId) {
                        self::assertSame($languageResourceId, $languagePair->getLanguageResourceId());
                        self::assertSame($sourceId, $languagePair->getSourceLang());
                        self::assertSame($targetId, $languagePair->getTargetLang());

                        return true;
                    }
                )
            );

        $this->createLanguagePairOperation->createLanguagePair($languageResourceId, $sourceId, $targetId);
    }

    public function getLanguageMock(int $id): Language
    {
        $language = $this->createMock(Language::class);
        $language
            ->method('__call')
            ->willReturnMap([
                ['getId', [], $id],
                ['getRfc5646', [], bin2hex(random_bytes(4))],
                ['getLangName', [], 'sourceLang'],
            ]);

        return $language;
    }
}
