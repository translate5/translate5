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
 * Test word count of a task when edit100PercentMatch enabled/disabled.
 * This will also test the analysis results when the task edit100PercentMatch is enabled/disabled
 */
class Translate2428Test extends \editor_Test_ApiTest {
    
    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init'
    ];

    protected static bool $setupOwnCustomer = true;
    
    public static function beforeTests(): void {

        self::assertAppState();

        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertLogin('testmanager');

        // Create the task. The task will not be imported directly autoStartImport is 0!
        $task = [
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId'=>static::$testCustomer->id,
            'autoStartImport'=>0,
            'wordCount' => 0,//just to overwrite the default value set by the ApiHelper
            'edit100PercentMatch' => 0
        ];
        $zipfile = static::api()->zipTestFiles('testfiles/','XLF-test.zip');
        static::api()->addImportFile($zipfile);
        static::api()->import($task,false,false);
        // add Demo-MTs
        static::addZDemoMTMt('one');
        static::addZDemoMTMt('two');

        // add resource assocs
        static::api()->addTaskAssoc();

        // queue analysis
        $params = [
            'internalFuzzy' => 1,
            'pretranslateMatchrate' => 100,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 1,
            'isTaskImport' => 0
        ];
        static::api()->putJson('editor/task/'.static::api()->getTask()->id.'/pretranslation/operation', $params, null, false);

        static::api()->getJson('editor/task/'.static::api()->getTask()->id.'/import');
        static::api()->checkTaskStateLoop();
    }

    /***
     * Create dummy mt resource.
     */
    private static function addZDemoMTMt(string $suffix){
        $params = [
            'resourceId'=>'ZDemoMT',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [static::$testCustomer->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::ZDemoMT_'.__CLASS__.'_'.$suffix
        ];
        static::api()->addResource($params);
    }
    
    /**
     * Test the word count and analysis with and without 100% match enabled/disabled
     */
    public function testTaskWorkCount() {
        $wordCount = static::api()->getTask()->wordCount;
        $this->assertEquals(66, $wordCount, 'Task word count is not as expected!');
        
        $this->checkAnalysis('edit100PercentMatch_false.txt');
        
        $task = static::api()->getTask();
        //enable 100% matches for edition. This should calculate also the word count
        static::api()->putJson('editor/task/'.$task->id, ['edit100PercentMatch' => 1]);
        
        static::api()->reloadTask();
        $wordCount = static::api()->getTask()->wordCount;
        
        $this->assertEquals(72, $wordCount, 'Task word count is not as expected!');
        
        $this->checkAnalysis('edit100PercentMatch_true.txt');
    }
    
    
    /***
     * Check and validate the analysis results. $validationFileName is file name constant (edit100PercentMatch_false and edit100PercentMatch_true)
     * which will switch the expected result to compare against.
     * @param string $validationFileName
     */
    private function checkAnalysis(string $validationFileName){
        $analysis=static::api()->getJson('editor/plugins_matchanalysis_matchanalysis',[
            'taskGuid'=>static::api()->getTask()->taskGuid
        ]);
        
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove the created timestamp since is not relevant for the test
        foreach ($analysis as &$a){
            unset($a->created);
        }
        //this is to recreate the file from the api response
        //file_put_contents(static::api()->getFile($validationFileName, null, false), json_encode($analysis, JSON_PRETTY_PRINT));
        $expected=static::api()->getFileContent($validationFileName);
        $actual=json_encode($analysis, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected analysis and the result file does not match.");
    }

    public static function afterTests(): void {
        $task = static::api()->getTask();
        static::api()->deleteTask($task->id, 'testmanager');
        //remove the created resources
        static::api()->removeResources();
    }
}
