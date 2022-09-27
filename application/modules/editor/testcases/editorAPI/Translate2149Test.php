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
 * Testcase for TRANSLATE-2149 Xliff import deletes part of segment and a tag
 * The problem was the algorithm in editor_Models_Import_FileParser_Xlf_SurroundingTags was modifying internally the
 *  data arrays, which was then cutting of to much data instead only the desired tags.
 *
 *  The wrong result of the second segment was:
 *    <1>damit wir keine Probleme mit dem Urheberrecht bekommen”.
 *  instead of the correct one:
 *    Text der von extern kommt zu ersetzen “<1>damit wir keine Probleme mit dem Urheberrecht bekommen”.</1>
 */
class Translate2149Test extends editor_Test_JsonTest {

    protected static array $forbiddenPlugins = [
        'editor_Plugins_LockSegmentsBasedOnConfig_Bootstrap',
        'editor_Plugins_NoMissingTargetTerminology_Bootstrap'
    ];

    protected static array $requiredRuntimeOptions = [
        'import.xlf.preserveWhitespace' => 0,
        'import.xlf.ignoreFramingTags' => 'all'
    ];

    public static function beforeTests(): void {

        $task = array(
            'sourceLang' => 'de',
            'targetLang' => 'es',
            'edit100PercentMatch' => true,
            'lockLocked' => 1,
        );

        $zipfile = static::api()->zipTestFiles('testfiles/','XLF-test.zip'); //TRANSLATE-2149-de-es
        
        static::api()->addImportFile($zipfile);
        static::api()->import($task);
        
        static::api()->addUser('testlector');
        
        //login in beforeTests means using this user in whole testcase!
        static::api()->login('testlector');
        
        $task = static::api()->getTask();
        //open task for whole testcase
        static::api()->setTaskToEdit($task->id);
    }
    
    /**
     * Testing segment values directly after import
     */
    public function testSegmentValuesAfterImport() {
        $jsonFileName = 'expectedSegments.json';
        $segments = static::api()->getSegments($jsonFileName, 10);
        $this->assertSegmentsEqualsJsonFile($jsonFileName, $segments, 'Imported segments are not as expected!');
    }
    
    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager', 'testlector');
    }
}