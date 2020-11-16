<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * Empty dummy test to test the surrounding test framework
 */
class SegmentTagsTest extends \ZfExtended_Test_Testcase {
    
    public static function setUpBeforeClass(): void {
        // TODO: Why is that neccessary ???
        require_once 'Tag.php';
        require_once 'TextNode.php';
        require_once 'Segment/InternalTag.php';
        require_once 'Segment/AnyInternalTag.php';
        require_once 'Segment/Tags.php';
        require_once 'Segment/TagCreator.php';
        require_once 'Utils/Dom.php';
        parent::setUpBeforeClass();
    }
    /**
     * 
     */
    public function testSimpleTag(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42"><span>Link Text</span> <img class="link-img" src="/some/icon.svg"/></a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($result, $expected);
    }
    /**
     *
     */
    public function testUnicodeTag(){
        $expected = '<div><p>イリノイ州シカゴにて、アイルランド系の家庭に、</p></div>';
        $dom = new editor_Utils_Dom();
        $element = $dom->loadUnicodeElement($expected);
        $result = $dom->saveHTML($element);
        $this->assertEquals($result, $expected);
    }
    /**
     *
     */
    public function testUnicodeWhitespaceTag(){
        $expected = '<div><p>イリノイ州シカゴにて、アイルランド系の家庭に、</p></div>';
        $dom = new editor_Utils_Dom();
        $element = $dom->loadUnicodeElement('  Hello! '.$expected.', something else, ...');
        $result = $dom->saveHTML($element);
        $this->assertEquals($result, $expected);
    }
    /**
     *
     */
    public function testMultipleUnicodeWhitespaceTag(){
        $expected = ' ÜüÖöÄäß? Japanisch: <div>イリノイ州シカゴにて、</div><p>アイルランド系の家庭に、</p> additional Textnode :-)';
        $dom = new editor_Utils_Dom();
        $elements = $dom->loadUnicodeMarkup($expected);
        $result = '';
        foreach($elements as $element){
            $result .= $dom->saveHTML($element);
        }
        $this->assertEquals($result, $expected);
    }
    /**
     * 
     */
    public function testTagWithAttributes(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42"><span>Link Text</span> <img class="link-img" src="/some/icon.svg"/></a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($result, $expected);
    }
    /**
     *
     */
    public function testSingleTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(6, 11, 'test', 'a'));
        $markup = 'Lorem <a>ipsum</a> dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    /**
     *
     */
    public function testMultipleTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(6, 26, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 72, 'test', 'b'));
        $markup = 'Lorem <a>ipsum dolor sit amet</a>, consetetur sadipscing <b>elitr, sed diam nonumy</b> eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    /**
     *
     */
    public function testOverlappingTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(6, 26, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 72, 'test', 'b'));
        $tags->addTag(new editor_Segment_AnyInternalTag(18, 60, 'test', 'c'));
        $markup = 'Lorem <a>ipsum dolor </a><c><a>sit amet</a>, consetetur sadipscing </c><b><c>elitr, sed</c> diam nonumy</b> eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    /**
     *
     */
    public function testOverlappingNestedTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(6, 26, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 72, 'test', 'b'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 55, 'test', 'c'));
        $tags->addTag(new editor_Segment_AnyInternalTag(18, 60, 'test', 'd'));
        $markup = 'Lorem <a>ipsum dolor </a><d><a>sit amet</a>, consetetur sadipscing </d><b><d><c>elitr</c>, sed</d> diam nonumy</b> eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    /**
     *
     */
    public function testOverlappingNestedFulllengthTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(0, 80, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyInternalTag(0, 80, 'test', 'b'));
        $tags->addTag(new editor_Segment_AnyInternalTag(6, 26, 'test', 'c'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 72, 'test', 'd'));
        $tags->addTag(new editor_Segment_AnyInternalTag(18, 60, 'test', 'e'));
        $markup = '<a><b>Lorem <c>ipsum dolor </c><e><c>sit amet</c>, consetetur sadipscing </e><d><e>elitr, sed</e> diam nonumy</d> eirmod.</b></a>';
        $this->createTagsTest($tags, $markup);
    }
    /**
     *
     */
    public function testSingularNestedTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(5, 5, 'test', 'div'));
        $tags->addTag(new editor_Segment_AnyInternalTag(5, 5, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 50, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 50, 'test', 'div'));
        $markup = 'Lorem<div><img/></div> ipsum dolor sit amet, consetetur sadipscing <div><img/></div>elitr, sed diam nonumy eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    /**
     *
     */
    public function testSingularNestedFulllengthTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyInternalTag(5, 5, 'test', 'div'));
        $tags->addTag(new editor_Segment_AnyInternalTag(5, 5, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 50, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyInternalTag(50, 50, 'test', 'div'));
        $tags->addTag(new editor_Segment_AnyInternalTag(0, 80, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyInternalTag(0, 80, 'test', 'b'));
        $markup = '<a><b>Lorem<div><img/></div> ipsum dolor sit amet, consetetur sadipscing <div><img/></div>elitr, sed diam nonumy eirmod.</b></a>';
        $this->createTagsTest($tags, $markup);
    }
    /**
     * 
     * @return editor_Segment_Tags
     */
    private function createTags() : editor_Segment_Tags{
        $segmentId = 1234567;
        $segmentText = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.'; // 80 characters
        $field = 'source';
        return new editor_Segment_Tags($segmentId, $segmentText, $field);
    }
    /**
     * 
     * @param editor_Segment_Tags $tags
     * @param string $expectedMarkup
     */
    private function createTagsTest(editor_Segment_Tags $tags, string $expectedMarkup){
        // compare rendered Markup
        $this->assertEquals($tags->render(), $expectedMarkup);
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_Tags::fromJson($expectedJSON);
        $this->assertEquals($jsonTags->toJson(), $expectedJSON);
        // unparse test
        $unparseTags = new editor_Segment_Tags($tags->getSegmentId(), $tags->getSegmentText(), $tags->getField());
        $unparseTags->unparse($expectedMarkup);
        $this->assertEquals($unparseTags->render(), $expectedMarkup);
    }
}