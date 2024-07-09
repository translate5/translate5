<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2023 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\Exception;
use MittagQI\Translate5\Test\Import\LanguageResource;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Test if google translator as language resources works. This will create single segment task and pre-translate it
 * with google translator language resource.
 */
class Translate3445Test extends JsonTestAbstract
{
    protected static bool $skipIfOptionsMissing = true;

    protected static array $requiredRuntimeOptions = [
        'runtimeOptions.LanguageResources.google.projectId' => null,
        //null checks for no concrete value but if not empty
        'runtimeOptions.LanguageResources.google.apiKey' => null,
        //null checks for no concrete value but if not empty
    ];

    /**
     * @throws ZfExtended_Exception
     * @throws Exception
     */
    protected static function setupImport(Config $config): void
    {
        if (! self::isMasterTest()) {
            self::markTestSkipped('Test runs only in master test to reduce usage/costs.');
        } else {
            $config
                ->addLanguageResource(
                    LanguageResource::GOOGLE_TRANSLATE,
                    customerId: static::getTestCustomerId(),
                    sourceLanguage: 'en',
                    targetLanguage: 'zh-TW'
                );
            $config
                ->addPretranslation()
                ->setProperty('pretranslateMt', 1);
            $config
                ->addTask('en', 'zh-TW', static::getTestCustomerId(), 'TRANSLATE-3445-de-en.xlf')
                ->setToEditAfterImport();
        }
    }

    public function testSegmentValuesAfterImport(): void
    {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertModelsEqualsJsonFile('Segment', $jsonFileName, $segments, 'Imported segments are not as expected!');
    }
}
