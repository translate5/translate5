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
 * Tests if Relais Files are imported correctly, inclusive our alignment checks 
 */
class RelaisImportTest extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('de', 'en')
            ->addUploadFolder('testfiles')
            ->addProperty('relaisLang', 'it')
            ->setToEditAfterImport();
    }

    /**
     * Test if relais columns are containing the expected content
     */
    public function testRelaisContent() {
        //get segment list
        $segments = static::api()->getSegments();
        $segments = array_map(function($segment){
            //TODO remove array cast with PHP7
            return (array) $segment;
        }, $segments);
        $relais = array_column($segments, 'relais', 'segmentNrInTask');
        
        $expected = [
            '1' => 'Questo e un casa roso.',
            '2' => 'RELAIS - Here the alignment is OK.',
            '3' => '',
            '4' => 'RELAIS – Here the alignment is OK again.',
            '5' => '',
            '6' => 'RELAIS – Here the alignment is OK again 2.',
            '7' => '',
            '8' => 'RELAIS – Here the alignment is OK again 3.',
            
            '9' => 'RELAIS - Segment with ignored and different tags',
            '10' => 'RELAIS – Segment with ignored and equal tags',
            '11' => '',
            '12' => 'RELAIS – Segment with equal entity encoding',
            '13' => 'RELAIS – Segment with equal entity encoding',
                
            '14' => 'This is a red house',
            '15' => 'Here the alignment is OK.',
            '16' => '',
            '17' => 'Here the alignment is OK again.',
            '18' => 'Here the alignment is OK again 2.',
            '19' => 'Here the alignment is OK again 3.',
                
            '19' => 'Here the alignment is OK again 3.',
            // the next segment has a different MID to test the segmentNrInTask based algorithm
            '20' => 'Dieses Segment testet mit einer abweichenden MID als im orginal SDLXLIFF den segmentNrInTask basierten Match Algorithmus.',
            '21' => 'Apache 2.0 auf Unixsystemen - Manual',
            '22' => 'PHP Manual',
            '23' => 'Installation und Konfiguration',
            '24' => 'Installation auf Unix-Systemen',
            '25' => 'RELAIS - Apache 1.3.x auf Unix-Systemen',
            '26' => ''
        ];
        
        $this->assertEquals($expected, $relais, 'Relais columns not filled as expected!');
        
        //the following checks are only to ensure that the imported content contains terminology
        $targetSource = 'Das ist eine rotes <div title="" class="term standardizedTerm lowercase transFound" data-tbxid="term_06_1_de_1_00013">Haus</div>';
        $this->assertFieldTextEquals($targetSource, $segments[0]['source'], 'Imported Source is not as expected!');
        $targetEdit = 'This is <div title="" class="term preferredTerm exact" data-tbxid="term_03_1_en_1_00006">a</div> red <div title="" class="term preferredTerm exact" data-tbxid="term_05_1_en_1_00011a">house</div>';
        $this->assertFieldTextEquals($targetEdit, $segments[0]['targetEdit'], 'Imported Target is not as expected!');
        $targetEdit = '<div title="" class="term preferredTerm exact" data-tbxid="term_11_1_en_1_00019">Apache</div> 1.3.x auf Unix-Systemen';
        $this->assertFieldTextEquals($targetEdit, $segments[24]['targetEdit'], 'Imported Target is not as expected!');
    }
}