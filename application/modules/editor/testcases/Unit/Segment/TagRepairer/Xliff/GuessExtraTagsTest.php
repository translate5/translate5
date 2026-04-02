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

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Unit\Segment\TagRepairer\Xliff;

use MittagQI\Translate5\Segment\TagRepair\Xliff\GuessExtraTags;
use MittagQI\Translate5\Segment\TagRepair\Xliff\XliffTag;
use PHPUnit\Framework\TestCase;

class GuessExtraTagsTest extends TestCase
{
    private GuessExtraTags $repair;

    protected function setUp(): void
    {
        $this->repair = new GuessExtraTags();
    }

    private function makeTag(string $type, string $id, ?string $rid, string $fullTag): XliffTag
    {
        return new XliffTag($type, $id, $rid, 0, $fullTag);
    }

    /**
     * No additional tags in translated — text must be unchanged.
     */
    public function testNoAdditionalTagsReturnsTextUnchanged(): void
    {
        $text = 'Hello <bx id="1" rid="1"/> World <ex id="2" rid="1"/>';

        $sourceTag1 = $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>');
        $sourceTag2 = $this->makeTag('ex', '2', '1', '<ex id="2" rid="1"/>');

        $translatedTag1 = $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>');
        $translatedTag2 = $this->makeTag('ex', '2', '1', '<ex id="2" rid="1"/>');

        $result = $this->repair->apply($text, [$sourceTag1, $sourceTag2], [$translatedTag1, $translatedTag2]);

        $this->assertSame($text, $result);
    }

    /**
     * An "additional-<id>" tag in the translated text matches a source tag by type and ID.
     * The additional tag should be replaced with the corrected source tag representation.
     */
    public function testAdditionalTagIsReplacedWithMatchingSourceTag(): void
    {
        // Source has a bx tag with id="1" and rid="1"
        $sourceTag = $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>');

        // Translated contains an "additional-1" version of that tag (id not matched during translation)
        $additionalFullTag = '<bx id="additional-1" rid="additional-1"/>';
        $translatedTag = $this->makeTag('bx', 'additional-1', 'additional-1', $additionalFullTag);

        $text = 'Hello ' . $additionalFullTag . ' World';

        $result = $this->repair->apply($text, [$sourceTag], [$translatedTag]);

        // The additional tag should have been replaced with the recreated source tag
        $this->assertStringNotContainsString('additional-1', $result);
        $this->assertStringContainsString('id="1"', $result);
    }

    /**
     * An additional tag in translated matches a source tag by RID (not just ID).
     */
    public function testAdditionalTagMatchedByRid(): void
    {
        // Source tag whose rid matches the stripped "additional-" id from translated
        $sourceTag = $this->makeTag('ex', '2', '5', '<ex id="2" rid="5"/>');

        // Translated has "additional-5" — the "5" matches sourceTag's rid
        $additionalFullTag = '<ex id="additional-5" rid="additional-5"/>';
        $translatedTag = $this->makeTag('ex', 'additional-5', 'additional-5', $additionalFullTag);

        $text = 'Text ' . $additionalFullTag . ' end';

        $result = $this->repair->apply($text, [$sourceTag], [$translatedTag]);

        $this->assertStringNotContainsString('additional-5', $result);
        $this->assertStringContainsString('id="2"', $result);
    }

    /**
     * Type mismatch between additional translated tag and source tag — no replacement should occur.
     */
    public function testTypeMismatchPreventsReplacement(): void
    {
        // Source is a 'bx' type, but translated additional tag is 'ex' type
        $sourceTag = $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>');

        $additionalFullTag = '<ex id="additional-1" rid="additional-1"/>';
        $translatedTag = $this->makeTag('ex', 'additional-1', 'additional-1', $additionalFullTag);

        $text = 'Hello ' . $additionalFullTag . ' World';

        $result = $this->repair->apply($text, [$sourceTag], [$translatedTag]);

        // No replacement should happen — text unchanged
        $this->assertSame($text, $result);
    }

    /**
     * No matching source tag found for an additional translated tag — text must remain unchanged.
     */
    public function testNoMatchingSourceTagLeavesTextUnchanged(): void
    {
        // Source has id="99", but translated additional tag refers to id "1" — no match
        $sourceTag = $this->makeTag('bx', '99', '99', '<bx id="99" rid="99"/>');

        $additionalFullTag = '<bx id="additional-1" rid="additional-1"/>';
        $translatedTag = $this->makeTag('bx', 'additional-1', 'additional-1', $additionalFullTag);

        $text = 'Hello ' . $additionalFullTag . ' World';

        $result = $this->repair->apply($text, [$sourceTag], [$translatedTag]);

        $this->assertSame($text, $result);
    }

    /**
     * A source tag already present in the translated text (matched by type+id) should not
     * be available for matching additional tags.
     */
    public function testAlreadyMatchedSourceTagIsNotReusedForAdditional(): void
    {
        // Source tag with id="1"
        $sourceTag = $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>');

        // Translated has both the normal tag and an additional one claiming the same id
        $normalFullTag = '<bx id="1" rid="1"/>';
        $additionalFullTag = '<bx id="additional-1" rid="additional-1"/>';

        $normalTag = $this->makeTag('bx', '1', '1', $normalFullTag);
        $additionalTag = $this->makeTag('bx', 'additional-1', 'additional-1', $additionalFullTag);

        $text = $normalFullTag . ' Hello ' . $additionalFullTag . ' World';

        $result = $this->repair->apply($text, [$sourceTag], [$normalTag, $additionalTag]);

        // The normal tag is already matched, so the additional one cannot steal source tag "1".
        // The additional tag should remain as-is (source tag was not available).
        $this->assertStringContainsString($additionalFullTag, $result);
    }

    /**
     * Multiple additional tags — each should be matched to a distinct source tag (no reuse).
     */
    public function testMultipleAdditionalTagsAreEachMatchedToDistinctSourceTags(): void
    {
        $sourceTag1 = $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>');
        $sourceTag2 = $this->makeTag('bx', '2', '2', '<bx id="2" rid="2"/>');

        $additionalFullTag1 = '<bx id="additional-1" rid="additional-1"/>';
        $additionalFullTag2 = '<bx id="additional-2" rid="additional-2"/>';

        $additionalTag1 = $this->makeTag('bx', 'additional-1', 'additional-1', $additionalFullTag1);
        $additionalTag2 = $this->makeTag('bx', 'additional-2', 'additional-2', $additionalFullTag2);

        $text = $additionalFullTag1 . ' Hello ' . $additionalFullTag2 . ' World';

        $result = $this->repair->apply(
            $text,
            [$sourceTag1, $sourceTag2],
            [$additionalTag1, $additionalTag2]
        );

        // Both additional tags should have been replaced
        $this->assertStringNotContainsString('additional-1', $result);
        $this->assertStringNotContainsString('additional-2', $result);
        $this->assertStringContainsString('id="1"', $result);
        $this->assertStringContainsString('id="2"', $result);
    }

    /**
     * When the source tags array is empty, no replacements should happen.
     */
    public function testEmptySourceTagsReturnsTextUnchanged(): void
    {
        $additionalFullTag = '<bx id="additional-1" rid="additional-1"/>';
        $translatedTag = $this->makeTag('bx', 'additional-1', 'additional-1', $additionalFullTag);

        $text = 'Hello ' . $additionalFullTag . ' World';

        $result = $this->repair->apply($text, [], [$translatedTag]);

        $this->assertSame($text, $result);
    }

    /**
     * When both source and translated tag arrays are empty the text must be returned as-is.
     */
    public function testEmptyTagArraysReturnsTextUnchanged(): void
    {
        $text = 'Plain text without tags';

        $result = $this->repair->apply($text, [], []);

        $this->assertSame($text, $result);
    }

    /**
     * Real-life example with multiple additional tags and a mix of matches and non-matches.
     */
    public function testRealLifeExampleWithMatchingAdditionalIndexes(): void
    {
        $text = '<bx mid="additional-1" original="3c6270742069643d223722207269643d2234222f3e" rid="2" />Do you want to add the contents of an email to a <ex mid="additional-2" original="3c6570742069643d223822207269643d2235222f3e" rid="3" /> <bx mid="additional-3" original="3c6270742069643d223922207269643d2236222f3e" rid="4" />OneNote<ex mid="additional-4" original="3c6570742069643d22313022207269643d2237222f3e" rid="5" /> <bx mid="additional-5" original="3c6270742069643d22313122207269643d2238222f3e" rid="6" />Notebook?<ex mid="additional-6" original="3c6570742069643d22313222207269643d2239222f3e" rid="7" />';

        $sourceTags = [
            $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>'),
            $this->makeTag('ex', '2', '1', '<ex id="2" rid="1"/>'),
            $this->makeTag('bx', '3', '2', '<bx id="3" rid="2"/>'),
            $this->makeTag('ex', '4', '2', '<ex id="4" rid="2"/>'),
            $this->makeTag('bx', '5', '3', '<bx id="5" rid="3"/>'),
            $this->makeTag('ex', '6', '3', '<ex id="6" rid="3"/>'),
        ];

        $additionalTags = [
            $this->makeTag('bx', 'additional-1', '2', '<bx mid="additional-1" original="3c6270742069643d223722207269643d2234222f3e" rid="2" />'),
            $this->makeTag('ex', 'additional-2', '3', '<ex mid="additional-2" original="3c6570742069643d223822207269643d2235222f3e" rid="3" />'),
            $this->makeTag('bx', 'additional-3', '4', '<bx mid="additional-3" original="3c6270742069643d223922207269643d2236222f3e" rid="4" />'),
            $this->makeTag('ex', 'additional-4', '5', '<ex mid="additional-4" original="3c6570742069643d22313022207269643d2237222f3e" rid="5" />'),
            $this->makeTag('bx', 'additional-5', '6', '<bx mid="additional-5" original="3c6270742069643d22313122207269643d2238222f3e" rid="6" />'),
            $this->makeTag('ex', 'additional-6', '7', '<ex mid="additional-6" original="3c6570742069643d22313222207269643d2239222f3e" rid="7" />'),
        ];

        $result = $this->repair->apply($text, $sourceTags, $additionalTags);

        self::assertEquals(
            '<bx id="1" rid="1"/>Do you want to add the contents of an email to a <ex id="2" rid="1"/> <bx id="3" rid="2"/>OneNote<ex id="4" rid="2"/> <bx id="5" rid="3"/>Notebook?<ex id="6" rid="3"/>',
            $result
        );
    }

    public function testRealLifeExampleWithNotMatchingAdditionalIndexes(): void
    {
        $text = '<bx mid="additional-4" original="3c6270742069643d223722207269643d2234222f3e" rid="5" />Do you want to add the contents of an email to a <ex mid="additional-5" original="3c6570742069643d223822207269643d2235222f3e" rid="6" /> <bx mid="additional-6" original="3c6270742069643d223922207269643d2236222f3e" rid="7" />OneNote<ex mid="additional-7" original="3c6570742069643d22313022207269643d2237222f3e" rid="8" /> <bx mid="additional-8" original="3c6270742069643d22313122207269643d2238222f3e" rid="9" />Notebook?<ex mid="additional-9" original="3c6570742069643d22313222207269643d2239222f3e" rid="10" />';

        $sourceTags = [
            $this->makeTag('bx', '1', '1', '<bx id="1" rid="1"/>'),
            $this->makeTag('ex', '2', '1', '<ex id="2" rid="1"/>'),
            $this->makeTag('bx', '3', '2', '<bx id="3" rid="2"/>'),
            $this->makeTag('ex', '4', '2', '<ex id="4" rid="2"/>'),
            $this->makeTag('bx', '5', '3', '<bx id="5" rid="3"/>'),
            $this->makeTag('ex', '6', '3', '<ex id="6" rid="3"/>'),
        ];

        $additionalTags = [
            $this->makeTag('bx', 'additional-4', '5', '<bx mid="additional-4" original="3c6270742069643d223722207269643d2234222f3e" rid="5" />'),
            $this->makeTag('ex', 'additional-5', '6', '<ex mid="additional-5" original="3c6570742069643d223822207269643d2235222f3e" rid="6" />'),
            $this->makeTag('bx', 'additional-6', '7', '<bx mid="additional-6" original="3c6270742069643d223922207269643d2236222f3e" rid="7" />'),
            $this->makeTag('ex', 'additional-7', '8', '<ex mid="additional-7" original="3c6570742069643d22313022207269643d2237222f3e" rid="8" />'),
            $this->makeTag('bx', 'additional-8', '9', '<bx mid="additional-8" original="3c6270742069643d22313122207269643d2238222f3e" rid="9" />'),
            $this->makeTag('ex', 'additional-9', '10', '<ex mid="additional-9" original="3c6570742069643d22313222207269643d2239222f3e" rid="10" />'),
        ];

        $result = $this->repair->apply($text, $sourceTags, $additionalTags);

        self::assertEquals(
            '<bx mid="additional-4" original="3c6270742069643d223722207269643d2234222f3e" rid="5" />Do you want to add the contents of an email to a <ex mid="additional-5" original="3c6570742069643d223822207269643d2235222f3e" rid="6" /> <bx mid="additional-6" original="3c6270742069643d223922207269643d2236222f3e" rid="7" />OneNote<ex mid="additional-7" original="3c6570742069643d22313022207269643d2237222f3e" rid="8" /> <bx mid="additional-8" original="3c6270742069643d22313122207269643d2238222f3e" rid="9" />Notebook?<ex mid="additional-9" original="3c6570742069643d22313222207269643d2239222f3e" rid="10" />',
            $result
        );
    }
}
