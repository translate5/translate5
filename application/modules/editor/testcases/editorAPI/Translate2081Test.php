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

    protected static $sourceLangRfc = 'de';
    protected static $targetLangRfc = 'en';

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init'
    ];

    protected static bool $setupOwnCustomer = true;

    /***
     * Add default user assoc and validate the results
     * Create the task and validate if the auto assign is done
     */
    public function testAutoAssign(): void
    {

        $params = [
            'customerId' => static::$ownCustomer->id,
            'workflow' => 'default',
            'sourceLang' => static::$sourceLangRfc,
            'targetLang' => static::$targetLangRfc,
            'userGuid' => static::api()->getUserGuid('testtranslator'),
            'workflowStepName' => 'translation'
        ];
        $result = static::api()->postJson('editor/userassocdefault', $params);
        unset($result->id, $result->customerId);
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile('assocResult.txt', null, false), json_encode($result, JSON_PRETTY_PRINT));
        }
        $expected = static::api()->getFileContent('assocResult.txt');
        $actual = json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        self::assertEquals($expected, $actual, 'The expected file (assocResult) an the result file does not match.');

        $config = static::getConfig();
        $task = $config
            ->addTask(static::$sourceLangRfc, static::$targetLangRfc, static::$ownCustomer->id, 'TRANSLATE-2081-de-en.xlf')
            ->setNotToFailOnError();
        $config->import($task);

        // after the task is created/imported, check if the users are auto assigned.
        $data = static::api()->getJson('editor/taskuserassoc',[
            'filter' => '[{"operator":"eq","value":"' . $task->getTaskGuid() . '","property":"taskGuid"}]'
        ]);

        // TODO FIXME: write a reusable Model for this !
        //filter out the non static data
        $data = array_map(function($assoc){
            unset($assoc->id, $assoc->taskGuid, $assoc->usedInternalSessionUniqId, $assoc->staticAuthHash, $assoc->editable, $assoc->deletable, $assoc->assignmentDate);
            return $assoc;
        }, $data);

        $expectedData = static::api()->getFileContent('expected.json', $data, true);
        static::assertEquals($expectedData, $data, 'The expected users are not auto assigned to the task');
    }
}
