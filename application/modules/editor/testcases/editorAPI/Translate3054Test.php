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
use MittagQI\Translate5\Test\Import\LanguageResource;
use MittagQI\Translate5\Test\Import\Task;
use MittagQI\Translate5\Test\JsonTestAbstract;

/**
 * Check batch-setting falsePositive-flag for a similar qualities across whole task
 */
class Translate3054Test extends JsonTestAbstract
{
    protected static ?LanguageResource $tc = null;

    protected static array $forbiddenPlugins = [
        'editor_Plugins_FrontendMessageBus_Init',
    ];

    protected static bool $termtaggerRequired = true;

    protected static array $requiredPlugins = [
        'editor_Plugins_TermTagger_Bootstrap',
    ];

    protected static array $segments = [];

    protected static ?Task $task = null;

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected static function setupImport(Config $config): void
    {
        // Create TermCollection and import tbx-file there
        static::$tc = $config
            ->addLanguageResource('termcollection', 'testfiles/import.tbx', static::getTestCustomerId())
            ->setProperty('name', 'TC test');

        // Create task and import csv-file there
        static::$task = $config
            ->addTask('de', 'en', static::getTestCustomerId(), 'testfiles/import.csv')
            ->addTaskConfig('runtimeOptions.import.fileparser.csv.active', '1')
            ->setToEditAfterImport();
    }

    /***
     * Make sure speading given quality's falsePositive-prop to all other similar qualities in this task - works ok
     */
    public function testFalsePositiveSpread()
    {
        // Get segments needed for the test and check their quantity
        static::$segments = static::api()->getSegments();
        static::assertEquals(2, count(static::$segments), 'Unexpected segments qty in the imported task');

        // Make sure initial value of segment #1 termtagger-quality's falsePositive flag is 0 and similarQty is 2
        $s1q = static::api()->getJson('/editor/quality/segment?segmentId=' . static::$segments[0]->id);
        $idx = null;

        foreach ($s1q as $idx => $q) {
            if ($q->type == 'term') {
                break;
            }
        }
        static::assertEquals(0, $s1q[$idx]->falsePositive, 'quality.falsepositive have unexpected value');
        static::assertEquals(2, $s1q[$idx]->similarQty, 'quality.similarQty have unexpected value');

        // Make sure initial value of segment #2 termtagger-quality's falsePositive flag is 0 and similarQty is 2 as well
        $s2q = static::api()->getJson('/editor/quality/segment?segmentId=' . static::$segments[1]->id);
        $idx = null;
        foreach ($s2q as $idx => $q) {
            if ($q->type == 'term') {
                break;
            }
        }
        static::assertEquals(0, $s2q[$idx]->falsePositive, 'quality.falsepositive have unexpected value');
        static::assertEquals(2, $s2q[$idx]->similarQty, 'quality.similarQty have unexpected value');

        // Set falsePositive-flag to 1
        $taskId = static::$task->getId();
        $qId = $s2q[$idx]->id;
        static::api()->getJson("/editor/taskid/$taskId/quality/falsepositive?id=$qId&falsePositive=1");

        // Make sure initial value of segment #2 termtagger-quality's falsePositive flag changed to 1
        $s2q = static::api()->getJson('/editor/quality/segment?segmentId=' . static::$segments[1]->id);
        $idx = null;
        foreach ($s2q as $idx => $q) {
            if ($q->type == 'term') {
                static::assertEquals(1, $q->falsePositive, 'quality.falsepositive have unexpected value');

                break;
            }
        }

        // Spread falsePositive=X on all qualities in this task that are similar to quality $qId including itself
        // Note: X - is inverted value of falsePositive-flag for the quality $qId, so if current value
        // of falsePositive-flag is 1 for the quality $qId, it will become 0 for this quality and all
        // similar qualities, and vice versa
        $qId = $s2q[$idx]->id;
        static::api()->getJson("/editor/taskid/$taskId/quality/falsepositivespread?id=$qId");

        // Get fresh list of qualities for segment #1
        $s1q = static::api()->getJson('/editor/quality/segment?segmentId=' . static::$segments[0]->id);
        $idx = null;
        foreach ($s1q as $idx => $q) {
            if ($q->type == 'term') {
                break;
            }
        }

        // Make sure falsePositive-prop of termtagger-quality for segment #1 is now "1"
        static::assertEquals(0, $s1q[$idx]->falsePositive, 'quality.falsepositive have unexpected value');
    }
}
