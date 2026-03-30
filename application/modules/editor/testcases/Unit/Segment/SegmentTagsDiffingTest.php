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
    /**
     * For now, we only use a single type of ins/del as the diffing creates ins/del for the same user and there are no TC tags from other users in play
     */
    protected array $testTags = [
        '<ins>' => '<ins class="trackchanges ownttip" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">',
        '<del>' => '<del class="trackchanges ownttip deleted" data-usertrackingid="1234" data-usercssnr="usernr1" data-workflowstep="review1" data-timestamp="2024-07-05T14:14:44+02:00">',
    ];

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

    public function testDiffing1(): void
    {
        $target = 'There are just 3 segments here.';
        $reimportEdited = 'There are only 3 segments here.';
        $diffedResult = 'There are <ins>only</ins><del>just</del> 3 segments here.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, true);
    }

    public function testDiffing2(): void
    {
        $target = 'Diese gibt es nur, um den Segmenteditor zu testen.';
        $reimportEdited = 'Diese gibt es nur, um den Segmenteditor zu testen TEST HINZUGEFÜGT.';
        $diffedResult = 'Diese gibt es nur, um den Segmenteditor zu testen<ins> TEST HINZUGEFÜGT</ins>.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, true);
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
        $diffedResult = 'Wenn die Auswertungs-Engine <ins>feststellt</ins><del>ermittelt</del>, dass die Zuverlässigkeit der Lösung <ins>bei der Verwendung</ins><del>mit</del> einer ionosphärenfreien Linearkombination höher ist, wird dies<ins> automatisch</ins> für die Lösung <ins>in Betracht</ins><del>automatisch</del> <ins>gezogen</ins><del>berücksichtigt</del>.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, false);
    }

    public function testRealDataTags3(): void
    {
        // testing "real" segment content against a reimport known to create broken markup
        // in this example, term-tags in conjunction with internal tags & changed content led to errors
        $target = 'Klicken Sie mit der rechten Maustaste, und öffnen Sie über das Kontextmenü das <div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div><div class="term preferredTerm exact" title="" data-tbxid="212e249c-8a4a-41c4-bdb4-3e0c85e452f1">Protokoll</div> Schleifen und <div class="term preferredTerm exact" title="" data-tbxid="4faa182b-ea82-42a3-847e-ab79b57e80ce">Abschlussfehler</div><div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div>.';
        $reimportEdited = 'Über Rechtsklick <div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Protokoll Schleifen und Abschlussfehler<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div> aus dem Kontextmenü auswählen.';
        $diffedResult = '<ins>Über</ins><del>Klicken</del> <ins>Rechtsklick</ins><del>Sie mit der rechten Maustaste, und öffnen Sie über das Kontextmenü das</del> <div class="open 6270742069643d2231222063747970653d22782d456d706861736973223e266c743b456d7068617369732667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;bpt id=&quot;1&quot; ctype=&quot;x-Emphasis&quot;&gt;&amp;lt;Emphasis&amp;gt;&lt;/bpt&gt;</span></div>Protokoll Schleifen und Abschlussfehler<div class="close 6570742069643d2231223e266c743b2f456d7068617369732667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;ept id=&quot;1&quot;&gt;&amp;lt;/Emphasis&amp;gt;&lt;/ept&gt;</span></div><ins> aus dem Kontextmenü auswählen</ins>.';

        $this->createDiffingTest($target, $reimportEdited, $diffedResult, false);
    }

    public function testExcelReimportDiffing1(): void
    {
        // testing the diffing for EXCEL Exports/Reimports
        // there, the "frontend" placeholder-"tags" like <1>, </1>, <2 /> - wihich are no valid tags - are used
        // additional QUIRK: in this code, the singular-tags will and must have a blank before the closing slash
        $target = 'Diese gibt es nur, um den Segmenteditor zu testen.';
        $reimportEdited = 'Diese gibt es nur, um den Segmenteditor zu testen TEST HINZUGEFÜGT.';
        $diffedResult = 'Diese gibt es nur, um den Segmenteditor zu testen<ins> TEST HINZUGEFÜGT</ins>.';

        $this->createExcelDiffingTest($target, $reimportEdited, $diffedResult);
    }

    public function testExcelReimportDiffing2(): void
    {
        // testing the diffing for EXCEL Exports/Reimports
        // there, the "frontend" placeholder-"tags" like <1>, </1>, <2 /> - wihich are no valid tags - are used
        // additional QUIRK: in this code, the singular-tags will and must have a blank before the closing slash
        $target = 'Diese <1>gibt</1> es nur, um<3 /> den <2>Segmenteditor</2> zu testen.';
        $reimportEdited = 'Diese <1>gibt</1> es nur, um<3 /> den <2>Segmenteditor</2> zu testen TEST HINZUGEFÜGT.';
        $diffedResult = 'Diese <1>gibt</1> es nur, um<3 /> den <2>Segmenteditor</2> zu testen<ins> TEST HINZUGEFÜGT</ins>.';

        $this->createExcelDiffingTest($target, $reimportEdited, $diffedResult);
    }

    public function testExcelReimportDiffing3(): void
    {
        // testing the diffing for EXCEL Exports/Reimports
        // there, the "frontend" placeholder-"tags" like <1>, </1>, <2 /> - wihich are no valid tags - are used
        // additional QUIRK: in this code, the singular-tags will and must have a blank before the closing slash
        $target = 'Diese <1>gibt</1> es nur, um<3 /> den <2>Segmenteditor</2> zu testen.';
        $reimportEdited = 'Diese <1>gibt</1> es nicht, um<3 /> einen <2>Segmenteditor</2> zu testen.';
        $diffedResult = 'Diese <1>gibt</1> es <ins>nicht</ins><del>nur</del>, um<3 /> <ins>einen</ins><del>den</del> <2>Segmenteditor</2> zu testen.';

        $this->createExcelDiffingTest($target, $reimportEdited, $diffedResult);
    }

    public function testExcelRealDataTags1(): void
    {
        // testing the diffing for EXCEL Exports/Reimports
        // there, the "frontend" placeholder-"tags" like <1>, </1>, <2 /> - wihich are no valid tags - are used
        // additional QUIRK: in this code, the singular-tags will and must have a blank before the closing slash
        $target = '蔡司驾驶型镜片凭借其特殊的蔡司钻立方<1 />®<2 /><4 />Plus 极光膜，则是高频驾驶人群的理想选择。<3 />';
        $reimportEdited = '如果您需要经常驾驶，请尝试配戴配有钻立方<1 />®<2 /><4 />极光膜S的蔡司驾驶型镜片。<3 />';
        $diffedResult = '<ins>如果您需要经常驾驶，请尝试配戴配有钻立方</ins><del>蔡司驾驶型镜片凭借其特殊的蔡司钻立方</del><1 />®<2 /><4 /><ins>极光膜S的蔡司驾驶型镜片</ins><del>Plus 极光膜，则是高频驾驶人群的理想选择</del>。<3 />';

        $this->createExcelDiffingTest($target, $reimportEdited, $diffedResult);
    }

    public function testExcelRealDataTags2(): void
    {
        // testing the diffing for EXCEL Exports/Reimports
        // there, the "frontend" placeholder-"tags" like <1>, </1>, <2 /> - wihich are no valid tags - are used
        // additional QUIRK: in this code, the singular-tags will and must have a blank before the closing slash
        $target = '得益于我们的创新表面膜层，采用钻立方<1 />®<2 /><5 />Plus 鎏金膜的镜片，易清洁性能提升至三倍。<3 />1<6 /><4 />';
        $reimportEdited = '得益于其创新的顶膜，配戴钻立方<1 />®<2 /><5 />鎏金膜S的镜片易清洁性提升可达三倍。<3 />1<6 /><4 />';
        $diffedResult = '<ins>得益于其创新的顶膜</ins><del>得益于我们的创新表面膜层</del>，<ins>配戴钻立方</ins><del>采用钻立方</del><1 />®<2 /><5 /><ins>鎏金膜S的镜片易清洁性提升可达三倍</ins><del>Plus 鎏金膜的镜片，易清洁性能提升至三倍</del>。<3 />1<6 /><4 />';

        $this->createExcelDiffingTest($target, $reimportEdited, $diffedResult);
    }

    private function createDiffingTest(
        string $originalTarget,
        string $reimportTarget,
        string $expectedResult,
        bool $withTerminologyValid
    ): void {
        // expand expected result
        $expectedResult = $this->shortToFull($expectedResult);
        // echo "\n\n" . (new SegmentTagSequence($originalTarget))->getFieldText() . "\n" .
        // (new SegmentTagSequence($reimportTarget))->getFieldText() . "\n";
        $contentProcessor = new ContentDefault(self::$testTask, [], self::$testUser);

        $diffedTarget = $contentProcessor->diffTargetWithTrackChanges(
            $originalTarget,
            $reimportTarget,
            false
        );

        $diffedTargetNoTerminology = $contentProcessor->diffTargetWithTrackChanges(
            $originalTarget,
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
     * Special test for testing the diffing used for Excel Export/Reimport
     * These Markups contain the "frontend-placeholders" to identify internal tags
     */
    private function createExcelDiffingTest(
        string $originalTarget,
        string $reimportTarget,
        string $expectedResult
    ): void {
        // expand expected result
        $expectedResult = $this->shortToFull($expectedResult);
        // diff
        $diffTagger = new \editor_Models_Export_DiffTagger_TrackChanges(self::$testTask, self::$testUser);
        $diffedSegment = $diffTagger->diffSegment(
            $originalTarget,
            $reimportTarget,
            date(NOW_ISO),
            self::$testUser->getUserName()
        );

        static::assertEquals(
            SegmentTagsTestAbstract::normalizeTrackChangesMarkup($expectedResult),
            SegmentTagsTestAbstract::normalizeTrackChangesMarkup($this->cleanDiffedTarget($diffedSegment))
        );
    }

    /**
     * Expands the TrackChanges ins/del test-tags to proer real TC tags
     */
    protected function shortToFull(string $markup): string
    {
        foreach ($this->testTags as $short => $full) {
            $markup = str_replace($short, $full, $markup);
        }

        return $markup;
    }

    /**
     * The diffing creates Markup with additional whitespace at the end :-(
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
