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
 * Create tmp customer and define auto assigned users
 * Create task with the tmp customer and matching workflow/source/target and validate if the user is auto assigned
 */
class Translate2081Test extends editor_Test_JsonTest {

    protected static $sourceLangRfc='de';
    protected static $targetLangRfc='en';

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init'
    ];

    protected static bool $setupOwnCustomer = true;

    /***
     * Add default user assoc and validate the results
     */
    public function testDefaultUserAssoc(){

        $params = [
            'customerId' => static::$ownCustomer->id,
            'workflow' => 'default',
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'userGuid' => '{00000000-0000-0000-C100-CCDDEE000003}', // testlector
            'workflowStepName' => 'translation'
        ];
        $result = static::api()->postJson('editor/userassocdefault', $params);
        unset($result->id);
        unset($result->customerId);
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile('assocResult.txt', null, false), json_encode($result, JSON_PRETTY_PRINT));
        }
        $expected = static::api()->getFileContent('assocResult.txt');
        $actual = json_encode($result, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, "The expected file (assocResult) an the result file does not match.");
    }

    /***
     * Create the task and validate if the auto assign is done
     *
     * @depends testDefaultUserAssoc
     */
    public function testTaskAutoAssign(){

        // create the task and wait for the import
        $task = [
            'taskName' => 'API Testing::'.__CLASS__, //no date in file name possible here!
            'sourceLang' => self::$sourceLangRfc,
            'targetLang' => self::$targetLangRfc,
            'customerId' => static::$ownCustomer->id,
            'edit100PercentMatch' => true,
            'autoStartImport' => 1
        ];
        self::assertLogin('testmanager');
        static::api()->addImportFile(static::api()->getFile('TRANSLATE-2545-de-en.xlf'));
        static::api()->import($task,false);
        error_log('Task created. '.static::api()->getTask()->taskName);


        // after the task is created/imported, check if the users are auto assigned.
        $task = static::api()->getTask();
        $data = static::api()->getJson('editor/taskuserassoc',[
            'filter' => '[{"operator":"eq","value":"' .$task->taskGuid . '","property":"taskGuid"}]'
        ]);

        //filter out the non static data
        $data = array_map(function($assoc){
            unset($assoc->id);
            unset($assoc->taskGuid);
            unset($assoc->usedInternalSessionUniqId);
            unset($assoc->staticAuthHash);
            unset($assoc->editable);
            unset($assoc->deletable);
            unset($assoc->assignmentDate);
            return $assoc;
        }, $data);

        //file_put_contents(static::api()->getFile('expected.json', null, false), json_encode($data,JSON_PRETTY_PRINT));
        $this->assertEquals(static::api()->getFileContent('expected.json'), $data, 'The expected users are not auto assigned to the task');

        static::api()->deleteTask($task->id, 'testmanager');
    }
}
