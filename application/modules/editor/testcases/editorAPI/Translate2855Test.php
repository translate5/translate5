<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * This is a test for the pivot pre-translation feature.
 *
 * The test works in the following way:
 *   - create temporray customer
 *   - create temporray MT resource using ZDemoMT plugin
 *   - assign the temporrary customer to be used as pivot default for ZDemoMT resource
 *   - create new task (with only 3 segments) asn assign the MT ZDemoMT resource
 *   - check if for all 3 task segments the pivot is pre-translated
 */
class Translate2855Test extends editor_Test_JsonTest {

     protected static $sourceLangRfc = 'en';
    protected static $targetLangRfc = 'de';

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init'
    ];

    protected static bool $setupOwnCustomer = true;

    /**
     * This method is called before the first test of this test class is run.
     * @throws Exception
     */
    public static function beforeTests(): void {

        // add needed Demo MT
        $params = [
            'resourceId' => 'ZDemoMT',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerIds' => [static::$testOwnCustomer->id],
            'customerUseAsDefaultIds' => [],
            'customerWriteAsDefaultIds' => [],
            'customerPivotAsDefaultIds' => [static::$testOwnCustomer->id],
            'serviceType' => 'editor_Plugins_ZDemoMT',
            'serviceName'=> 'ZDemoMT',
            'name' => 'API Testing::Pivot pre-translation_'.__CLASS__
        ];
        static::api()->addResource($params);

        // create task without starting the import
        $task = [
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'relaisLang' => self::$targetLangRfc,
            'customerId' => static::$testOwnCustomer->id,
            'edit100PercentMatch' => true,
            'autoStartImport' => 0
        ];
        static::api()->addImportFile(static::api()->getFile('Task-de-en.html'));
        static::api()->import($task,false,false);

        // queue the pretranslation
        static::api()->putJson('editor/languageresourcetaskpivotassoc/pretranslation/batch', [ 'taskGuid' => static::api()->getTask()->taskGuid ], null, false);

        // start the import & wait for finish
        static::api()->getJson('editor/task/'.static::api()->getTask()->id.'/import');
        static::api()->checkTaskStateLoop();
    }

    /**
     * Test if the task relais segments are pre-translated using ZDemoMT
     * @return void
     */
    public function testSegmentContent(){
        //open task for whole testcase
        static::api()->setTaskToEdit();
        $segments = static::api()->getSegments();

        self::assertEquals(3, count($segments), 'The number of segments does not match.');

        foreach ($segments as $segment){
            self::assertNotEmpty($segment->relais);
        }
    }

    /**
     * This method is called after the last test of this test class is run.
     */
    public static function afterTests(): void {
        $task = static::api()->getTask();
        // remove task & resources
        static::api()->deleteTask($task->id, 'testmanager');
        static::api()->removeResources();
    }
}
