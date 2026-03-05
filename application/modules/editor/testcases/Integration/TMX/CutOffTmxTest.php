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

namespace MittagQI\Translate5\Test\Integration\TMX;

use MittagQI\Translate5\Test\UnitTestAbstract;
use MittagQI\Translate5\TMX\CutOffTmx;

class CutOffTmxTest extends UnitTestAbstract
{
    private string $tmxFile;

    public function setUp(): void
    {
        $this->tmxFile = __DIR__ . '/CutOffTmxTest/' . bin2hex(random_bytes(5)) . '_test.tmx';
        copy(__DIR__ . '/CutOffTmxTest/test.tmx', $this->tmxFile);
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmxFile)) {
            unlink($this->tmxFile);
        }

        $cutOffTmx = str_replace('.tmx', '', basename($this->tmxFile)) . '_cutoff.tmx';
        $cutOffTmx = __DIR__ . '/CutOffTmxTest/' . $cutOffTmx;

        if (file_exists($cutOffTmx)) {
            unlink($cutOffTmx);
        }
    }

    public function engineProvider(): array
    {
        return [
            ['tmxutils'],
            ['php'],
        ];
    }

    /**
     * @dataProvider engineProvider
     */
    public function test(string $engine): void
    {
        $config = new \Zend_Config([
            'runtimeOptions' => [
                'LanguageResources' => [
                    't5memory' => [
                        'useTmxUtilsTrim' => $engine === 'tmxutils',
                    ],
                ],
            ],
        ]);

        self::setConfig($config);

        $cutOffTmx = CutOffTmx::create();

        $cutOffTmx->cutOff($this->tmxFile, 5);

        self::assertFileExists($this->tmxFile);
        self::assertFileEquals(
            __DIR__ . "/CutOffTmxTest/expected-$engine.tmx",
            $this->tmxFile,
            'The cut off tmx file is not as expected.'
        );
    }
}
