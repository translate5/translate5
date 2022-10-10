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

/**
 * Testcase for TRANSLATE-2538
 */
class QualitySpellCheckTest extends editor_Test_JsonTest {

    protected static array $requiredPlugins = [
        'editor_Plugins_SpellCheck_Init'
    ];

    public function testTask0(){
        $this->performTestForTask('ten segments --- de-DE en-US', 10);
    }

    private function performTestForTask(string $taskName, int $expectedSegmentQuantity){

        // Detect source and target languages from filename
        $lang = [];
        preg_match('~ --- ([^ ]+) ([^ ]+)$~', $taskName, $lang);

        // import task
        $config = static::getConfig();
        $config->import(
            $config
                ->addTask($lang[1], $lang[2])
                ->addUploadFile('testfiles/' . $taskName . '.csv')
                ->setToEditAfterImport()
        );

        // Get segments and check their quantity
        $factQty = count(static::api()->getSegments(null, 10));
        static::assertEquals($factQty, $expectedSegmentQuantity, 'Not enough segments in the imported task');

        // Check qualities
        $jsonFile = $taskName.'.json';
        $tree = static::api()->getJsonTree('/editor/quality', [], $jsonFile);
        $treeFilter = editor_Test_Model_Filter::createSingle('qtype', 'spellcheck');
        $this->assertModelEqualsJsonFile('FilterQuality', $jsonFile, $tree, '', $treeFilter);
    }
}