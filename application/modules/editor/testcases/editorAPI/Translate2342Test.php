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

use MittagQI\Translate5\Test\Api\DbHelper;
use MittagQI\Translate5\Test\Import\Config;

/***
 * Test the import progress feature. This will only test the progress report before the import is triggered
 * and the report progress after the import.
 */
class Translate2342Test extends editor_Test_ImportTest
{
    protected static array $forbiddenPlugins = [
        'editor_Plugins_SegmentStatistics_Bootstrap',
    ];

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_ZDemoMT_Init',
    ];

    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        // we remove all "done" workers as well for this test
        DbHelper::cleanupWorkers(true);

        $sourceLangRfc = 'de';
        $targetLangRfc = 'en';
        $customerId = static::$ownCustomer->id;
        $config
            ->addLanguageResource('zdemomt', null, $customerId, $sourceLangRfc, $targetLangRfc);
        $config
            ->addPretranslation()
            ->setProperty('pretranslateMt', 1);
        $config
            ->addTask($sourceLangRfc, $targetLangRfc, $customerId)
            ->addUploadFile('import-test-file.html')
            ->addTaskConfig('runtimeOptions.autoQA.enableSegmentSpellCheck', '0') // crucial: otherwise the self-queueing spellcheck-worker leads to unpredictable results
            ->setNotToWaitForImported(); // this triggers the task-import to immediately start
    }

    public function testImportAndProgress()
    {
        $taskId = static::getTask()->getId();
        $taskGuid = static::getTask()->getTaskGuid();
        // now test the queued worker progress before and after the import.
        $result = static::api()->getJson('editor/task/importprogress', [
            'taskGuid' => $taskGuid,
        ]);
        $result = $result->progress ?? null;
        $this->assertNotEmpty($result->progress ?? null, 'No results found for the import progress.');

        // run the import workers and check wait for task import
        static::api()->getJson('editor/task/' . $taskId . '/import');
        static::api()->waitForCurrentTaskStateOpen();

        $result = static::api()->getJson('editor/task/importprogress', [
            'taskGuid' => $taskGuid,
        ]);
        $result = $result->progress ?? null;
        $this->assertNotEmpty($result->progress ?? null, 'No results found for the import progress.');
    }
}
