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

use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\ImportTestAbstract;

/**
 * SegmentsToUsersTest imports a test task, adds users with the same workflow-role and assigns segments to them.
 * It then checks if they are allowed to edit segments accordingly.
 */
class SegmentsToUsersTest extends ImportTestAbstract
{
    public const STEP = 'translation';

    public const SEGMENTRANGE_USER1 = '1-3,5';

    public const SEGMENTS_USER1 = [1, 2, 3, 5];

    public const SEGMENTRANGE_USER2 = '6-7';

    public const SEGMENTS_USER2 = [6, 7];

    public const NON_EDITABLE_SEGMENTS_EXPECTED = '4,8-10';

    protected static bool $termtaggerRequired = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_FrontEndMessageBus_Init',
    ];

    protected static function setupImport(Config $config): void
    {
        $config
            ->addTask('en', 'de', -1, 'testcase-de-en.xlf')
            ->setUsageMode('simultaneous')
            ->addUser(TestUser::TestLector->value, 'open', self::STEP, [
                'segmentrange' => self::SEGMENTRANGE_USER1,
            ])
            ->addUser(TestUser::TestTranslator->value, 'open', self::STEP, [
                'segmentrange' => self::SEGMENTRANGE_USER2,
            ]);
    }

    /**
     * Checks if the segments that are NOT editable are recognized as expected.
     */
    public function testNonEditableSegments()
    {
        $missingsegmentranges = static::getTask()->reload(static::api())->getProperty('missingsegmentranges')[0];
        $this->assertEquals('translation', $missingsegmentranges->workflowStepName);
        $this->assertEquals(self::NON_EDITABLE_SEGMENTS_EXPECTED, $missingsegmentranges->missingSegments);
    }

    /**
     * Opens the task for User1 and checks if the segments are editable as expected.
     */
    public function testEditableSegmentsForUser1()
    {
        static::api()->login(TestUser::TestLector->value);
        $taskId = static::getTask()->reload(static::api())->getId();
        //open task
        static::api()->setTaskToEdit($taskId);
        //get segment list
        $segments = static::api()->getSegments(null, 20);
        //check if segments are editable as expected
        $this->checkSegments($segments, self::SEGMENTS_USER1);
        static::api()->logout();
    }

    /**
     * Opens the task for User2 and checks if the segments are editable as expected.
     */
    public function testEditableSegmentsForUser2()
    {
        static::api()->login(TestUser::TestTranslator->value);
        $taskId = static::getTask()->reload(static::api())->getId();
        //open task
        static::api()->setTaskToEdit($taskId);
        //get segment list
        $segments = static::api()->getSegments(null, 20);
        //check if segments are editable as expected
        $this->checkSegments($segments, self::SEGMENTS_USER2);
        static::api()->logout();
    }

    /**
     * Checks if segments are editable as expected.
     * @param array $allSegments
     */
    protected function checkSegments($allSegments, $editableSegmentsExpected)
    {
        $editableSegments = [];
        foreach ($allSegments as $segment) {
            if ($segment->editable == 1) {
                $editableSegments[] = (int) $segment->segmentNrInTask;
            }
        }
        $this->assertEquals($editableSegmentsExpected, $editableSegments);
    }
}
