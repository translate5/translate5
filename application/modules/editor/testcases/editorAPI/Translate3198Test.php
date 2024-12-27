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

use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * Match analysis tests.
 * The test will check if the current codebase provides a valid matchanalysis test results.
 * The valid test results are counted by hand.
 * For more info about the segment results check the Analysis test result Info.ods document in the test folder
 */
class Translate3198Test extends ImportTestAbstract
{
    use \MittagQI\Translate5\Test\Api\AnalysisTrait;

    protected static array $requiredPlugins = [
        'editor_Plugins_Okapi_Init',
        'editor_Plugins_MatchAnalysis_Init',
        'editor_Plugins_InstantTranslate_Init',
    ];

    /**
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testPenalties(): void
    {
        // Shortcuts
        $customerId = static::getTestCustomerId();
        $config = static::getConfig();
        $tbxFile = 'TermCollection_de_en.tbx';
        $taskFile = 'Task-de-AT_en-US.html';
        $prefix = 'API Testing::' . __CLASS__;

        // [1] import termcollection resource for de => en-US
        $config->import(
            $collection = $config
                ->addLanguageResource('termcollection', "testfiles/$tbxFile", $customerId)
                ->setProperty('name', $name = "$prefix " . pathinfo($tbxFile, PATHINFO_FILENAME))
        );
        $this->assertTrue(is_object($collection), 'Unable to create a test collection');
        $this->assertEquals($name, $collection->name);

        // [2] setup penalty for termcollection langres<=>customer, to be further used as default for langres<=>task
        //     those penalties would mean 104%-matches will be reduced to 103%-matches, as only general penalty will be
        //     applicable for this termcollection in the task we're going to import
        static::api()->postJson('editor/languageresourcecustomerassoc', [
            'penaltyGeneral' => $penalty['general']['customer']['tc'] = 1,
            'penaltySublang' => $penalty['sublang']['customer']['tc'] = 2,
            'customerId' => $customerId,
            'languageResourceId' => $collection->getId(),
        ], null, false);

        // [3] import zdemomt resource for de-DE => en-GB
        $config->import(
            $zdemomt = $config
                ->addLanguageResource('zdemomt', null, $customerId, 'de-DE', 'en-GB')
                ->addDefaultCustomerId($customerId)
                ->setProperty('name', "$prefix ZDemoMT_de-DE_en-GB")
        );

        // [4] setup penalty for zdemomt langres<=>customer, to be further used as default for langres<=>task
        //     those penalties would mean 70%-matches will be reduced to 57%-matches, so found matches
        //     will appear in 59%-50% range instead of 79%-70% range, if kept (but they won't be kept at those values)
        static::api()->postJson('editor/languageresourcecustomerassoc', [
            'penaltyGeneral' => $penalty['general']['customer']['mt'] = 2,
            'penaltySublang' => $penalty['sublang']['customer']['mt'] = 10,
            'customerId' => $customerId,
            'languageResourceId' => $zdemomt->getId(),
        ], null, false);

        // [6] import task for de-AT => en-US
        $config->import(
            $config
                ->addTask('de-AT', 'en-US', $customerId, "testfiles/$taskFile")
                ->setProperty('wordCount', 114)
                ->setProperty('taskName', "$prefix Task-de-AT_en-US")
        );

        // [7] assign termcollection to task and make sure default penalties were picked from langres<=>customer
        $taskAssoc = static::api()->postJson('editor/languageresourcetaskassoc', [
            "taskGuid" => static::getTask()->getTaskGuid(),
            "languageResourceId" => $collection->getId(),
            "segmentsUpdateable" => false,
        ]);
        $this->assertEquals($penalty['general']['customer']['tc'], $taskAssoc->penaltyGeneral);
        $this->assertEquals($penalty['sublang']['customer']['tc'], $taskAssoc->penaltySublang);

        // [8] assign zdemomt to task and make sure default penalties were picked from langres<=>customer
        $taskId = static::getTask()->getId();
        $taskAssoc = static::api()->postJson('editor/languageresourcetaskassoc', [
            "taskGuid" => $taskGuid = static::getTask()->getTaskGuid(),
            "languageResourceId" => $zdemomt->getId(),
            "segmentsUpdateable" => false,
        ]);
        $this->assertEquals($penalty['general']['customer']['mt'], $taskAssoc->penaltyGeneral);
        $this->assertEquals($penalty['sublang']['customer']['mt'], $taskAssoc->penaltySublang);

        // [9] setup penalties for zdemomt langres<=>task, to override the default ones coming from langres<=>customer
        //     those penalties would mean 70%-matches will be reduced to 60%-matches, so found matches
        //     will appear in 69%-60% range instead of 79%-70% range, as both general and sublang penalties will be
        //     applicable for this mt resource in this task
        $taskAssoc = static::api()->putJson("editor/languageresourcetaskassoc/$taskAssoc->id", [
            "taskGuid" => $taskGuid,
            "languageResourceId" => $zdemomt->getId(),
            "segmentsUpdateable" => false,
            "penaltyGeneral" => $penalty['general']['task']['mt'] = 1,
            "penaltySublang" => $penalty['sublang']['task']['mt'] = 9,
        ]);
        $this->assertEquals($penalty['general']['task']['mt'], $taskAssoc->penaltyGeneral);
        $this->assertEquals($penalty['sublang']['task']['mt'], $taskAssoc->penaltySublang);

        // [10] run analysis
        echo date('H:i:s');
        static::api()->putJson("/editor/task/$taskId/pretranslation/operation", [
            'isTaskImport' => 0,
            'batchQuery' => 0,
            'internalFuzzy' => 1,
            'pretranslateTmAndTerm' => 1,
            'pretranslateMt' => 1,
            'pretranslateMatchrate' => 100,
        ]);

        // todo: make this work: static::api()->waitForTaskState($taskId, editor_Plugins_MatchAnalysis_Models_MatchAnalysis::TASK_STATE_ANALYSIS);
        sleep(20);

        // [8] Get analysis results
        $analysisFact = static::api()->getJson('editor/plugins_matchanalysis_matchanalysis', [
            'taskGuid' => $taskGuid,
            'unitType' => 'word',
        ], $analysisFile = 'analysis.json');

        // Make sure analysis is not empty
        $this->assertNotEmpty($analysisFact, 'No results for matchanalysis');

        // Get expected analysis
        $analysisPlan = static::api()->getFileContent($analysisFile);

        // Make sure analysis is as expected
        $this->assertEquals(
            $this->filterTaskAnalysis($analysisPlan),
            $this->filterTaskAnalysis($analysisFact),
            "The expected and actual matchanalysis results do no match for file $analysisFile"
        );
    }
}
