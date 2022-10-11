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
 * Test word count of a task when edit100PercentMatch enabled/disabled.
 * This will also test the analysis results when the task edit100PercentMatch is enabled/disabled
 */
class Translate2428Test extends editor_Test_ImportTest {
    
    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init'
    ];

    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::$ownCustomer->id;
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate2428Test_one'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc)
            ->setProperty('name', 'API Testing::ZDemoMT_Translate2428Test_two'); // TODO FIXME: we better generate data independent from resource-names ...
        $config
            ->addPretranslation()
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFolder('testfiles', 'XLF-test.zip')
            ->setProperty('wordCount', 0)
            ->setProperty('edit100PercentMatch', false)
            ->setProperty('taskName', 'API Testing::Translate2428Test'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    /**
     * Test the word count and analysis with and without 100% match enabled/disabled
     */
    public function testTaskWorkCount() {
        $wordCount = static::getTask()->getProperty('wordCount');
        $this->assertEquals(66, $wordCount, 'Task word count is not as expected!');
        
        $this->checkAnalysis('edit100PercentMatch_false.txt');
        
        //enable 100% matches for edition. This should calculate also the word count
        static::api()->putJson('editor/task/'.static::getTask()->getId(), ['edit100PercentMatch' => 1]);
        
        static::getTask()->reload(static::api());
        $wordCount = static::getTask()->getProperty('wordCount');
        
        $this->assertEquals(72, $wordCount, 'Task word count is not as expected!');
        
        $this->checkAnalysis('edit100PercentMatch_true.txt');
    }
    
    
    /***
     * Check and validate the analysis results. $validationFileName is file name constant (edit100PercentMatch_false and edit100PercentMatch_true)
     * which will switch the expected result to compare against.
     * @param string $validationFileName
     */
    private function checkAnalysis(string $validationFileName){
        $analysis = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis',[
            'taskGuid' => static::getTask()->getTaskGuid()
        ]);
        
        $this->assertNotEmpty($analysis,'No results found for the matchanalysis.');
        //remove the created timestamp since is not relevant for the test
        foreach ($analysis as &$a){
            unset($a->created);
        }
        //this is to recreate the file from the api response
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile($validationFileName, null, false), json_encode($analysis, JSON_PRETTY_PRINT));
        }
        $expected = static::api()->getFileContent($validationFileName);
        $actual = json_encode($analysis, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected analysis and the result file does not match.");
    }
}
