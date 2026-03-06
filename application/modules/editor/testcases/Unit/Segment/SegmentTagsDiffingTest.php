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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\Segment;

use editor_Models_Task;
use MittagQI\Translate5\Segment\Tag\SegmentTagSequence;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\SegmentContent\ContentDefault;
use MittagQI\Translate5\Test\SegmentTagsTestAbstract;
use MittagQI\Translate5\Test\UnitTestAbstract;
use ZfExtended_Models_User;

/**
 * Several "classic" PHPUnit tests to check the TagRepair which detects faulty structures and fixes them by removing or restructuring the internal tags
 */
class SegmentTagsDiffingTest extends UnitTestAbstract
{
    private static ZfExtended_Models_User $testUser;

    private static editor_Models_Task $testTask;

    public static function beforeTests(): void
    {
        self::$testUser = new ZfExtended_Models_User();
        self::$testUser->setLogin('SegmentTagsDiffingTest.user');
        self::$testUser->setUserGuid(\ZfExtended_Utils::guid(true));
        self::$testUser->save();

        self::$testTask = new editor_Models_Task();
        self::$testTask->setTaskGuid(\ZfExtended_Utils::uuid());
        self::$testTask->setState('Import');
        self::$testTask->setTaskName('SegmentTagsDiffingTest.task');
        self::$testTask->save();
    }

    public static function afterTests(): void
    {
        self::$testUser->delete();
        self::$testTask->delete();
    }

    public function testRealDataTags1(): void
    {
        // testing "real" segment content against a reimport known to create broken markup
        // in this example, only term-tags led to the problem, content is unchanged
        $target = '<div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Abtragsdefinition<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div> – Definieren, ob sich die positiven Abtragswerte auf das Material über oder unter der <div class="term preferredTerm exact" title="" data-tbxid="79073e0c-3f63-476a-a231-21c05b851f27">Referenz</div> beziehen.';
        $reimportEdited = '<div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Abtragsdefinition<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div> – Definieren, ob sich die positiven Abtragswerte auf das Material über oder unter der Referenz beziehen.';
        $diffedResult = '<div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Abtragsdefinition<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div> – Definieren, ob sich die positiven Abtragswerte auf das Material über oder unter der Referenz beziehen.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, false);
    }

    public function testRealDataTags2(): void
    {
        // testing "real" segment content against a reimport known to create broken markup
        // in this example, term-tags in conjunction with changed content led to errors
        $target = 'Wenn die Auswertungs-Engine ermittelt, dass die Zuverlässigkeit der Lösung mit einer ionosphärenfreien Linearkombination höher <div class="term preferredTerm lowercase" title="" data-tbxid="bf2e558a-cba3-4b37-be09-cee5c01fe68b">ist</div>, wird dies für die Lösung automatisch berücksichtigt.';
        $reimportEdited = 'Wenn die Auswertungs-Engine feststellt, dass die Zuverlässigkeit der Lösung bei der Verwendung einer ionosphärenfreien Linearkombination höher ist, wird dies automatisch für die Lösung in Betracht gezogen.';
        $diffedResult = 'Wenn die Auswertungs-Engine <ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">feststellt</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">ermittelt</del>, dass die Zuverlässigkeit der Lösung <ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">bei der Verwendung</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">mit</del> einer ionosphärenfreien Linearkombination höher ist, wird dies<ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00"> automatisch</ins> für die Lösung <ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">in Betracht</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">automatisch</del> <ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">gezogen</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">berücksichtigt</del>.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, false);
    }

    public function testRealDataTags3(): void
    {
        // testing "real" segment content against a reimport known to create broken markup$segmentId = 26135159;
        // in this example, term-tags in conjunction with internal tags & changed content led to errors
        $target = 'Klicken Sie mit der rechten Maustaste, und öffnen Sie über das Kontextmenü das <div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div><div class="term preferredTerm exact" title="" data-tbxid="212e249c-8a4a-41c4-bdb4-3e0c85e452f1">Protokoll</div> Schleifen und <div class="term preferredTerm exact" title="" data-tbxid="4faa182b-ea82-42a3-847e-ab79b57e80ce">Abschlussfehler</div><div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div>.';
        $reimportEdited = 'Über Rechtsklick <div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Protokoll Schleifen und Abschlussfehler<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div> aus dem Kontextmenü auswählen.';
        $diffedResult = '<ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">Über</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">Klicken</del> <ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">Rechtsklick</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">Sie mit der rechten Maustaste, und öffnen Sie über das Kontextmenü das</del> <div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Protokoll Schleifen und Abschlussfehler<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div><ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00"> aus dem Kontextmenü auswählen</ins>.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, false);
    }

    public function testRealDataTags4(): void
    {
        // testing "real" segment content against a reimport known to create broken markup$segmentId = 26135159;
        // in this example, term-tags in conjunction with internal tags & changed content led to errors
        $target = 'There are just 3 segments here.';
        $reimportEdited = 'There are only 3 segments here.';
        $diffedResult = 'There are <ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">only</ins><del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">just</del> 3 segments here.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, true);
    }

    public function testRealDataTags5(): void
    {
        // testing "real" segment content against a reimport known to create broken markup$segmentId = 26135159;
        // in this example, term-tags in conjunction with internal tags & changed content led to errors
        $target = 'Diese gibt es nur, um den Segmenteditor zu testen.';
        $reimportEdited = 'Diese gibt es nur, um den Segmenteditor zu testen TEST HINZUGEFÜGT.';
        $diffedResult = 'Diese gibt es nur, um den Segmenteditor zu testen<ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00"> TEST HINZUGEFÜGT</ins>.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, true);
    }

    private function createDiffingTest(
        string $segmentTarget,
        string $reimportTarget,
        string $expectedResult,
        bool $withTerminologyValid
    ): void {
        // echo "\n\n" . (new SegmentTagSequence($segmentTarget))->getFieldText() . "\n" .
        // (new SegmentTagSequence($reimportTarget))->getFieldText() . "\n";
        $contentProcessor = new ContentDefault(self::$testTask, [], self::$testUser);

        $diffedTarget = $contentProcessor->diffTargetWithTrackChanges(
            $segmentTarget,
            $reimportTarget,
            false
        );

        $diffedTargetNoTerminology = $contentProcessor->diffTargetWithTrackChanges(
            $segmentTarget,
            $reimportTarget
        );

        $exception = null;
        $markup = '';

        try {
            $sequence = new SegmentTagSequence($diffedTarget);
            $markup = $sequence->render();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        if ($withTerminologyValid) {
            static::assertNull($exception, 'Diffing with terminology is expected to be valid');
            static::assertEquals($this->cleanDiffedTarget($diffedTarget), $markup);
        } else {
            static::assertNotNull($exception, 'Diffing with terminology is expected to be invalid');
        }

        $exception = null;

        try {
            $sequence = new SegmentTagSequence($diffedTargetNoTerminology);
            $markup = $sequence->render();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        static::assertNull($exception, 'Diffing without terminology is expected to be valid');
        static::assertEquals($this->cleanDiffedTarget($diffedTargetNoTerminology), $markup);
        static::assertEquals(
            SegmentTagsTestAbstract::normalizeTrackChangesMarkup($expectedResult),
            SegmentTagsTestAbstract::normalizeTrackChangesMarkup($markup)
        );
    }

    /**
     * The difing creates Markup with additional whitespace at the end :-(
     */
    private function cleanDiffedTarget(string $markup): string
    {
        return preg_replace(
            '~(<(ins|del)[^<]+) >~',
            '$1>',
            $markup
        );
    }
}
