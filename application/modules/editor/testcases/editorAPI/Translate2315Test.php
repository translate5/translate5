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

/**
 * Testcase for TRANSLATE-2315 repetition filtering
 * For details see the issue.
 */
class Translate2315Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static string $setupUserLogin = 'testlector';

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'en')
            ->addUploadFolder('testfiles')
            ->setToEditAfterImport();
    }

    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->checkRepetition($segments[0]->id, [$segments[1]->id]);
        $this->checkRepetition($segments[2]->id, []);
        $this->checkRepetition($segments[4]->id, []);
        $this->checkRepetition($segments[6]->id, [$segments[7]->id]);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    protected function checkRepetition(int $idToGetFor, array $idsToBeFound) {
        $alikes = static::api()->getJson('editor/alikesegment/'.$idToGetFor);
        $idsFound = array_column($alikes, 'id');
        sort($idsFound);
        sort($idsToBeFound);
        $this->assertEquals($idsFound, $idsToBeFound, 'The found alike segment IDs are not as expected!');
    }
    
    
    /**
     * @depends testSegmentValuesAfterImport
     */
    public function testSegmentEditing() {
        //get segment list
        $segments = static::api()->getSegments(null, 10);
        
        //edit the segment and make some target repetitions
        static::api()->saveSegment($segments[4]->id, 'target rep 1');
        static::api()->saveSegment($segments[5]->id, 'target rep 1');
        static::api()->saveSegment($segments[6]->id, 'target rep 2');
        static::api()->saveSegment($segments[7]->id, 'target rep 2');

        $this->checkRepetition($segments[0]->id, [$segments[1]->id]); //source rep
        $this->checkRepetition($segments[2]->id, []); //still no repetition
        $this->checkRepetition($segments[4]->id, [$segments[5]->id]); // target rep
        $this->checkRepetition($segments[6]->id, [$segments[7]->id]); // both is a rep
        
        //check direct PUT result
        $jsonFileName = 'expectedSegments-edited.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Edited segments are not as expected!');
    }
}
