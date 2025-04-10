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

use editor_Workflow_Default as DefaultWorkflow;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

class ReimportSegmentsTest extends ImportTestAbstract
{
    protected static bool $setupOwnCustomer = true;

    private $db;

    protected function setUp(): void
    {
        $this->db = \Zend_Db_Table::getDefaultAdapter();
    }

    protected static function setupImport(Config $config): void
    {
        $sourceLanguage = 'en';
        $targetLanguage = 'de';
        $customerId = self::$ownCustomer->id;

        $config
            ->addTask($sourceLanguage, $targetLanguage, $customerId, 'simple-en-de.zip')
            ->addUser(TestUser::TestTranslator->value, params: [
                'workflow' => 'default',
                'workflowStepName' => 'translation',
            ])
            ->addUser(TestUser::TestLector->value, params: [
                'workflow' => 'default',
                'workflowStepName' => 'reviewing',
            ])
            ->setProperty('workflow', 'default')
            ->setProperty('taskName', static::NAME_PREFIX . 'ReimportSegmentsTest1')
        ;

        $config
            ->addTask($sourceLanguage, $targetLanguage, $customerId, 'simple-en-de.zip')
            ->addUser(TestUser::TestTranslator->value, params: [
                'workflow' => 'default',
                'workflowStepName' => 'translation',
            ])
            ->addUser(TestUser::TestLector->value, params: [
                'workflow' => 'default',
                'workflowStepName' => 'reviewing',
            ])
            ->setProperty('workflow', 'default')
            ->setProperty('taskName', static::NAME_PREFIX . 'ReimportSegmentsTest2')
        ;

        $config
            ->addLanguageResource('opentm2', null, $customerId, $sourceLanguage, $targetLanguage)
            ->setProperty('name', 'Some resource name')
        ;
    }

    public function testReimportIsNotTriggeredOnWorkflowEnd(): void
    {
        $task = self::getTaskAt(0)->getAsObject();
        self::api()->setTask($task);

        self::api()->login(TestUser::TestTranslator->value);
        self::assertEquals(DefaultWorkflow::STATE_OPEN, self::api()->reloadTask()->userState);

        self::api()->setTaskToEdit();
        $segments = self::api()->getSegments();
        //add missing translation by translator
        self::api()->saveSegment($segments[6]->id, 'Apache 2.x  auf Unix-Systemen');
        self::api()->setTaskToFinished();

        // workflow step changed now to review
        self::assertEquals('reviewing', self::api()->reloadTask()->workflowStepName);

        //check that USER_REVIEWER (in review) is now open
        self::api()->login(TestUser::TestLector->value);
        self::assertEquals(DefaultWorkflow::STATE_OPEN, self::api()->reloadTask()->userState);

        // Finish task and check no reimport is triggered
        self::api()->setTaskToFinished();
        self::assertEquals(DefaultWorkflow::STATE_FINISH, self::api()->reloadTask()->userState);

        $result = $this->db->query(
            'SELECT COUNT(*) FROM Zf_worker WHERE worker LIKE ?',
            ['%PrepareReimportSegmentsWorker%']
        );

        self::assertEquals(0, $result->fetchColumn());
    }

    public function testReimportIsTriggeredOnWorkflowEnd(): void
    {
        $task = self::getTaskAt(1)->getAsObject();
        self::api()->setTask($task);

        self::api()->login(TestUser::TestManager->value);

        $tms = $this->getT5MemoryLanguageResources();
        self::assertCount(1, $tms);

        static::api()->putJson('editor/languageresourcetaskassoc/' . $tms[0]->taskassocid, [
            'taskGuid' => self::api()->getTask()->taskGuid,
            'languageResourceId' => $tms[0]->languageResourceId,
            'segmentsUpdateable' => true,
        ]);

        // Check task tm was created
        $tms = $this->getT5MemoryLanguageResources();
        self::assertCount(2, $tms);
        self::assertNotEmpty(array_filter($tms, static fn (stdClass $tm) => $tm->isTaskTm));

        self::api()->login(TestUser::TestTranslator->value);
        self::api()->setTaskToEdit();
        $segments = self::api()->getSegments();
        self::api()->saveSegment($segments[6]->id, 'Some translation');
        self::api()->setTaskToFinished();

        self::api()->login(TestUser::TestLector->value);
        self::api()->setTaskToFinished();

        $result = $this->db->query(
            'SELECT COUNT(*) FROM Zf_worker WHERE worker LIKE ?',
            ['%PrepareReimportSegmentsWorker%']
        );

        self::assertEquals(1, $result->fetchColumn());
    }

    private function getT5MemoryLanguageResources(): array
    {
        $filters = [[
            'property' => 'taskGuid',
            'operator' => 'eq',
            'value' => self::api()->getTask()->taskGuid,
        ]];

        $tms = self::api()->getJson(
            'editor/languageresourcetaskassoc?filter=' . urlencode(json_encode($filters, flags: JSON_THROW_ON_ERROR)),
        );

        return array_filter($tms, static fn (stdClass $tm) => $tm->serviceName === 'OpenTM2');
    }
}
