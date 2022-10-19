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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;

/***
 * This test will test the customerWriteAsDefaultIds flag in the languageresources-customer assoc.
 * The used tm memory is OpenTm2.
 */
class Translate2417Test extends editor_Test_JsonTest {

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init'
    ];

    private static LanguageResource $translationMemory;

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::getTestCustomerId();
        static::$translationMemory = $config
            ->addLanguageResource('opentm2', 'resource1.tmx', $customerId, $sourceLangRfc, $targetLangRfc)
            ->addDefaultCustomerId($customerId, true);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFolder('testfiles');
    }

    /**
     * Test if all the segments are as expected after import.
     */
    public function testSegments() {

        $tmId = static::$translationMemory->getId();
        static::api()->addUser('testmanager');
        static::api()->setTaskToEdit(static::getTask()->getId());
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!', true, true);

        // now test editing the segments
        self::assertLogin('testmanager');
        // load the first segment
        $segments = static::api()->getSegments(null, 1);
        // test the first segment
        $segToTest = $segments[0];

        // query the results from this segment and compare them against the expected initial json
        $jsonFileName = 'tmResultsBeforeEdit.json';
        $tmResults = static::api()->getJson('editor/languageresourceinstance/'.$tmId.'/query', ['segmentId' => $segToTest->id], $jsonFileName);
        $this->assertIsArray($tmResults, 'GET editor/languageresourceinstance/'.$tmId.'/query does not return an array but: '.print_r($tmResults,1).' and raw result is '.print_r(static::api()->getLastResponse(),1));
        $this->assertTmResultEqualsJsonFile($jsonFileName, $tmResults, 'The received tm results before segment modification are not as expected!');

        // set dummy translation for the first segment and save it. This should upload this translation to the tm to.
        $segToTest->targetEdit = "Aleks test tm update.";
        static::api()->saveSegment($segToTest->id, $segToTest->targetEdit);

        // after the segment save, check for the tm results for the same segment
        $jsonFileName = 'tmResultsAfterEdit.json';
        $tmResults = static::api()->getJson('editor/languageresourceinstance/'.$tmId.'/query', ['segmentId' => $segToTest->id], $jsonFileName);
        $this->assertTmResultEqualsJsonFile($jsonFileName, $tmResults, 'The received tm results after segment modification are not as expected!');
    }
}
