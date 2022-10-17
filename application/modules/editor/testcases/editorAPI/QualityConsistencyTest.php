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
use MittagQI\Translate5\Test\Filter;

/**
 * Testcase for 'TRANSLATE-2537: AutoQA: Check inconsistent translations'
 */
class QualityConsistencyTest extends editor_Test_JsonTest {

    /**
     * @var array
     */
    private static $segments = [];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $config->addTask('en', 'de')
            ->addUploadFile('testfiles/TRANSLATE-2537-en-de.xlf')
            ->setToEditAfterImport();
    }

    public static function beforeTests(): void {
        // Get segments needed for the test and check their quantity
        static::$segments = static::api()->getSegments(null, 29);
        static::assertEquals(count(static::$segments), 29, 'Not enough segments in the imported task');
    }

    /**
     * Test the qualities fetched for a segment
     */
    public function testSegmentQualities(){
        foreach ([3, 4, 5, 6, 9, 10] as $idx) {
            $fileName = 'expectedSegmentQualities-' . $idx . '.json';
            $qualities = static::api()->getJson('/editor/quality/segment?segmentId=' . static::$segments[$idx]->id, [], $fileName);
            $qualityFilter = Filter::createSingle('type', 'consistent');
            $this->assertModelsEqualsJsonFile('SegmentQuality', $fileName, $qualities, 'File '.$fileName.', Segment target: "'.static::$segments[$idx]->target.'"', $qualityFilter);
        }
    }
}