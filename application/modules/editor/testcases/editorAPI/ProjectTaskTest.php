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
use MittagQI\Translate5\Test\JsonTestAbstract;

/***
 * 1. Create project with 4 project tasks.
 * 2. Create term collection which will be assigned as default for all the tasks.
 * 3. The term tagger will tag some of the segments in each project task.
 * 4. Compare the segment content after term tagging for each project task.
 *
 */
class ProjectTaskTest extends JsonTestAbstract
{
    protected static $sourceLangRfc = 'en';

    protected static $targetLangRfc = ['de', 'it', 'fr'];

    protected static bool $termtaggerRequired = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_TermTagger_Bootstrap',
    ];

    protected static array $requiredRuntimeOptions = [
        'autoQA.autoStartOnImport' => 1,
    ];

    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        $ownCustomerId = static::$ownCustomer->id;
        $config
            ->addLanguageResource('termcollection', 'collection.tbx', $ownCustomerId)
            ->addDefaultCustomerId($ownCustomerId);
        $config->addTask(static::$sourceLangRfc, static::$targetLangRfc, $ownCustomerId)
            ->addUploadFolder('testfiles')
            ->addTaskConfig('runtimeOptions.autoQA.autoStartOnImport', '1')
            ->setProperty('edit100PercentMatch', 0)
            ->setProperty('taskName', static::NAME_PREFIX . 'ProjectTaskTest'); // TODO FIXME: we better generate data independent from resource-names ...
    }

    /***
     * Validate the basic project task values
     */
    public function testProjectTaskCreation()
    {
        static::api()->reloadProjectTasks();
        self::assertEquals(count(static::api()->getProjectTasks()), 3, 'The number of the project task is not as expected');
        $languages = static::api()->getLanguages();

        $getRfc = function ($id) use ($languages) {
            foreach ($languages as $lang) {
                if ($id == $lang->id) {
                    return $lang->rfc5646;
                }
            }

            return '';
        };

        //validate the task target language
        foreach (static::api()->getProjectTasks() as $task) {
            self::assertEquals($getRfc($task->sourceLang), self::$sourceLangRfc, 'The project task does not match the expected source language');
            self::assertContains($getRfc($task->targetLang), self::$targetLangRfc, 'The task target language (' . $task->targetLang . ') can not be found in the expected values.');
            self::assertEquals($task->taskType, static::api()::INITIAL_TASKTYPE_PROJECT_TASK, 'Project tasktype does not match the expected type.');
        }
    }

    /***
     * For each project task, check the segment content. Some of the segments are with terms.
     */
    public function testProjectTasksSegmentContent()
    {
        $project = static::api()->getTask();
        static::api()->reloadProjectTasks();
        foreach (static::api()->getProjectTasks() as $task) {
            $this->checkProjectTaskSegments($task);
        }
        static::api()->setTask($project);
    }

    /***
     * Check the segments content for the given task.
     * For this, first the task needs to be opened for editing. After the check the task will be set to open again.
     * @param stdClass $task
     */
    private function checkProjectTaskSegments(stdClass $task)
    {
        //set internal current task for further processing
        static::api()->setTask($task);

        error_log('Segments check for task [' . $task->taskName . ']');
        //open the task for editing. This is the only way to load the segments via the api
        static::api()->setTaskToEdit($task->id);

        $fileName = str_replace(['/', '::'], '_', $task->taskName . '.json');
        $segments = static::api()->getSegments($fileName);
        // compare segments (this API will strip/adjust segment contents)
        $this->assertSegmentsEqualsJsonFile(
            fileToCompare: $fileName,
            segments: $segments,
            message: 'Imported segments are not as expected in ' . basename($fileName) . '!',
            stopOnFirstFailedDiff: false
        );

        // close the task for editing
        static::api()->setTaskToOpen($task->id);
    }
}
