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

/***
 * Test the import progress feature. This will only test the progress report before the import is triggered
 * and the report progress after the import.
 *
 */
class Translate2342Test extends \editor_Test_ApiTest {
    /* @var $this Translate1484Test */
    
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';

    protected static array $forbiddenPlugins = [
        'editor_Plugins_SegmentStatistics_Bootstrap',
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init'
    ];

    protected static bool $setupOwnCustomer = true;

    public function testImportAndProgress() {

        // create task
        $task =[
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId' => static::$ownCustomer->id,
            'autoStartImport' => 0
        ];
        self::assertLogin('testmanager');
        static::api()->addImportFile(static::api()->getFile('import-test-file.html'));
        static::api()->import($task,false,false);

        // Create dummy MT resource
        $params =[
            'resourceId' => 'ZDemoMT',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [static::$ownCustomer->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::ZDemoMT_'.__CLASS__
        ];
        static::api()->addResource($params);

        // Add task to languageresource assoc
        static::api()->addTaskAssoc();

        // Queue the match anlysis worker
        $task = static::api()->getTask();
        $params = [
            'internalFuzzy' => 1,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 1,
            'isTaskImport' => 0
        ];
        static::api()->putJson('editor/task/'.$task->id.'/pretranslation/operation', $params, null, false);

        // now test the queued worker progress before and after the import.
        $result = static::api()->getJson('editor/task/importprogress',[
            'taskGuid' => $task->taskGuid
        ]);
        $result = $result->progress ?? null;
        $this->assertNotEmpty($result->progress ?? null,'No results found for the import progress.');

        //remove the non static properties
        unset($result->taskGuid);
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile('exportInitial.txt', null, false), json_encode($result, JSON_PRETTY_PRINT));
        }

        $expected = static::api()->getFileContent('exportInitial.txt');
        $actual = json_encode($result, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        self::assertEquals(trim($expected), trim($actual), "The initial queue worker progress and the result file does not match.");
        
        // run the import workers and check wait for task import
        static::api()->getJson('editor/task/'.$task->id.'/import');
        static::api()->checkTaskStateLoop();
        
        $result = static::api()->getJson('editor/task/importprogress',[
            'taskGuid' => $task->taskGuid
        ]);
        $result = $result->progress ?? null;
        $this->assertNotEmpty($result->progress ?? null,'No results found for the import progress.');

        //remove the non static properties
        unset($result->taskGuid);
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile('exportFinal.txt', null, false), json_encode($result, JSON_PRETTY_PRINT));
        }

        $expected = static::api()->getFileContent('exportFinal.txt');
        $actual = json_encode($result, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        self::assertEquals(trim($expected), trim($actual), "The initial queue worker progress and the result file does not match.");

        static::api()->deleteTask($task->id, 'testmanager');
        static::api()->removeResources();
    }
}
