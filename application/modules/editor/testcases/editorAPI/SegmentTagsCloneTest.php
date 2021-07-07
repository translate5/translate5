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
 * Several "classic" PHPUnit tests to check the FieldTags Cloning without TrackChanges tags
 * TODO: create test with additional quality-tags e.g. MQM
 */
class SegmentTagsCloneTest extends editor_Test_SegmentTagsTest {   

    /**
     * Some Internal Tags to create Tests with
     */
    private $open1 = '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;1&gt;</span><span class="full" data-originalid="123" data-length="-1">TEST</span></div>';
    private $close1 = '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/1&gt;</span><span class="full" data-originalid="123" data-length="-1">TEST</span></div>';
    private $open2 = '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;2&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>';
    private $close2 = '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/2&gt;</span><span class="full" data-originalid="124" data-length="-1">TEST</span></div>';
    private $open3 = '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;3&gt;</span><span class="full" data-originalid="125" data-length="-1">TEST</span></div>';
    private $close3 = '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/3&gt;</span><span class="full" data-originalid="125" data-length="-1">TEST</span></div>';
    private $open4 = '<div class="open internal-tag ownttip"><span class="short" title="TEST">&lt;4&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST</span></div>';
    private $close4 = '<div class="close internal-tag ownttip"><span class="short" title="TEST">&lt;/4&gt;</span><span class="full" data-originalid="126" data-length="-1">TEST</span></div>';
    private $single5 = '<div class="single tab internal-tag ownttip"><span class="short" title="&lt;5/&gt;: 1 tab character">&lt;5/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div>';
    private $single6 = '<div class="single internal-tag ownttip"><span class="short" title="&lt;char name=&quot;Indent&quot;/&gt;">&lt;6/&gt;</span><span class="full" data-originalid="259" data-length="-1">&lt;char name=&quot;Indent&quot;/&gt;</span></div>';
    private $single7 = '<div class="single newline internal-tag ownttip"><span class="short" title="&lt;7/&gt;: Newline">&lt;7/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>';
    private $insX = '<ins class="trackchanges ownttip" data-usertrackingid="1868" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2021-05-27T20:04:17+02:00" id="ext-element-{X}">';
    private $delX = '<del class="trackchanges ownttip deleted" data-usertrackingid="1868" data-usercssnr="usernr1" data-workflowstep="no workflow1" data-timestamp="2021-05-27T22:51:24+02:00" id="ext-element-{X}">';

    public function testEmptyMarkup(){
        $markup = '';
        $expected = '';
        $this->createTrackChangesCloneTest($expected, $markup);
    }
    
    public function testSimpleMarkup1(){
        // testing srings without any tags
        $markup = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        $expected = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }
    
    public function testSimpleMarkup2(){
        // testing srings without any tags
        $markup = '<del>Lorem ipsum dolor sit amet, consetetur sadipscing elitr.</del> Sed diam nonumy eirmod tempor invidunt ut labore et<ins> dolore magna aliquyam erat</ins>, sed <del>diam</del> voluptua.';
        $expected = ' Sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }
    
    public function testSimpleMarkup3(){
        // testing srings without any tags
        $markup = '<del>Lorem ipsum dolor <del>sit amet, consetetur</del> sadipscing elitr. </del>Sed diam nonumy eirmod tempor invidunt ut labore et<ins> dolore magna aliquyam erat</ins>, sed diam voluptua.';
        $expected = 'Sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }
    
    public function testSimpleMarkup4(){
        // testing srings without any tags
        $markup = '<del>Lorem ipsum dolor sit amet, consetetur sadipscing elitr. </del>Sed diam nonumy eirmod tempor invidunt ut labore et<ins> dolore magna aliquyam erat</ins>, sed<del> diam voluptua</del>.';
        $expected = 'Sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed.';
        $this->createTrackChangesCloneTest($expected, $markup);
    }
    
    public function testSimpleMarkup5(){
        // testing srings without any tags
        $markup = 'Lorem ipsum dolor sit <ins>amet</ins>, consetetur <ins>sadipscing</ins> elitr, sed diam nonumy eirmod tempor invi<del>dunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</del>';
        $expected = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invi';
        $this->createTrackChangesCloneTest($expected, $markup);
    }

    public function testMarkup1(){
        // testing content without ins/del
        $markup = 'Lorem <1>ipsum dolor</1> sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $expected = 'Lorem <1>ipsum dolor</1> sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }
    
    public function testMarkup2(){
        $markup = 'Lorem <1>ipsum</1> dolor sit amet, <del><2>consetetur sadipscing<5/></2></del> elitr, sed diam <ins>nonumy eirmod tempor</ins> <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $expected = 'Lorem <1>ipsum</1> dolor sit amet, elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }

    public function testMarkup3(){
        $markup = 'Lorem <1>ipsum <del>dolor</del></1> <del>sit</del> amet, <2><del>consetetur sadipscing<5/></del></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> <del>labore et <4>dolore magna</4> aliquyam erat</del></3>, sed diam voluptua.';
        $expected = 'Lorem <1>ipsum </1> amet, <2></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> </3>, sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }
    
    public function testMarkup4(){
        // testing content without ins/del
        $markup = 'Lorem <del><1>ipsum dolor</1></del> sit amet, <2>consetetur sadipscing<del><5/></del></2> elitr, sed diam nonumy eirmod tempor <3><ins>invidunt</ins> <del>ut</del><6/> labore et <ins><4>dolore magna</4></ins> aliquyam erat</3><del>,</del> sed diam voluptua.';
        $expected = 'Lorem sit amet, <2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt <6/> labore et <4>dolore magna</4> aliquyam erat</3> sed diam voluptua.';
        $this->createTrackChangesCloneTest($expected, $markup);
        $this->createTrackChangesFilterCloneTest($expected, $markup);
        $this->createTrackChangesMqmFilterCloneTest(editor_Segment_Tag::strip($expected), $markup);
    }
    /**
     * Creates a test for the tags cloning. The passed markup will have the following short-tags replaced with "real" internal tags
     * Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>
     * @param string $expected
     * @param string $markup
     */
    private function createTrackChangesCloneTest($expected, $markup){
        $markupConverted = $this->replaceTags($markup);
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, 'target', $markupConverted, 'target', 'target');
        $markupUnconverted = $this->revertTags($markupTags->render());
        $this->assertEquals($markup, $markupUnconverted);
 
        $expectedConverted = $this->replaceTags($expected);
        $expectedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, 'target', $expectedConverted, 'target', 'target');
        // create clone without trackchanges
        $clonedTags = $markupTags->cloneWithoutTrackChanges();
        // render the cloned tags
        $renderedCloned = $clonedTags->render();
        // revert the structure to a human readable form
        $reconvertedCloned = $this->revertTags($renderedCloned);
        
        // compare in various ways        
        $this->assertEquals($expected, $reconvertedCloned);
        $this->assertEquals($expectedConverted, $renderedCloned);
        $this->assertEquals($expectedTags->render(), $renderedCloned);
        // ther order in the cloned json still has the old values, so we ignore the ordering
        $this->assertEquals($this->cleanOrderInJSON($expectedTags->toJson()), $this->cleanOrderInJSON($clonedTags->toJson()));
        // make sure the original tags do not become manipulated. We need to remove the IDs here, but at one point they may have to be included for trackchanges purposes
        $markupExpected = preg_replace('~ id="ext-element-[0-9]+"~', '', $markupConverted);
        $this->assertEquals($markupExpected, $markupTags->render());
    }
    /**
     * Creates a test for the tags cloning with filtering for internal tags only
     * The passed markup will have the following short-tags replaced with "real" internal tags
     * Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>
     * @param string $expected
     * @param string $markup
     */
    private function createTrackChangesFilterCloneTest($expected, $markup){
        // we filter for internal tags only
        $filter = [ editor_Segment_Tag::TYPE_INTERNAL ];
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, 'target', $this->replaceTags($markup), 'target', 'target');
        // a full clone without filter
        $markupTagsCloned = $markupTags->cloneFiltered();
        $expectedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, 'target', $this->replaceTags($expected), 'target', 'target');
        // create expected clone and only filtered tags
        $expectedTags = $expectedTags->cloneFiltered($filter);
        // create clone without trackchanges and only filtered tags
        $clonedTags = $markupTags->cloneWithoutTrackChanges($filter);
        // compare
        $this->assertEquals($expectedTags->render(), $clonedTags->render());
        $this->assertEquals($this->cleanOrderInJSON($expectedTags->toJson()), $this->cleanOrderInJSON($clonedTags->toJson()));
        // compare full clone
        $this->assertEquals($markupTags->render(), $markupTagsCloned->render());
        $this->assertEquals($this->cleanOrderInJSON($markupTags->toJson()), $this->cleanOrderInJSON($markupTagsCloned->toJson()));
    }
    /**
     * Creates a test for the tags cloning with filtering for MQM tags only, what will effectively remove all tags as we only have internal tags
     * The passed markup will have the following short-tags replaced with "real" internal tags
     * Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>
     * @param string $expected
     * @param string $markup
     */
    private function createTrackChangesMqmFilterCloneTest($expected, $markup){
        // we filter for internal tags only
        $filter = [ editor_Segment_Tag::TYPE_MQM ];
        $markupTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, 'target', $this->replaceTags($markup), 'target', 'target');
        // a full clone without filter
        $markupTagsCloned = $markupTags->cloneFiltered();
        $expectedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, 'target', $this->replaceTags($expected), 'target', 'target');
        // create expected clone and only filtered tags
        $expectedTags = $expectedTags->cloneFiltered($filter);
        // create clone without trackchanges and only filtered tags
        $clonedTags = $markupTags->cloneWithoutTrackChanges($filter);
        // compare
        $this->assertEquals($expectedTags->render(), $clonedTags->render());
        $this->assertEquals($this->cleanOrderInJSON($expectedTags->toJson()), $this->cleanOrderInJSON($clonedTags->toJson()));
        // compare full clone
        $this->assertEquals($markupTags->render(), $markupTagsCloned->render());
        $this->assertEquals($this->cleanOrderInJSON($markupTags->toJson()), $this->cleanOrderInJSON($markupTagsCloned->toJson()));
    }
    /**
     * Removes the order / parentOrder props in the json-data as they are not sequenced (but valid!) in the cloned tags
     * @param string $json
     * @return string
     */
    private function cleanOrderInJSON($json){
        $json = preg_replace('/"parentOrder":[0-9]+,/', '"parentOrder":-1,', $json);
        return preg_replace('/"order":[0-9]+,/', '"order":-1,', $json);
    }
    /**
     * 
     * @param string $markup
     * @return string
     */
    private function replaceTags($markup){
        $markup = $this->replaceInternalTags($markup);
        $markup = $this->replaceInsDelTags($markup);
        return $markup;
    }
    /**
     * 
     * @param string $markup
     * @return string
     */
    private function revertTags($markup){
        $markup = $this->revertInternalTags($markup);
        $markup = $this->revertInsDelTags($markup);
        return $markup;
    }
    /**
     * 
     * @param string $markup
     * @return string
     */
    private function replaceInsDelTags($markup){
        $markup = $this->replaceMultipleTags($markup, '<ins>', $this->insX);
        $markup = $this->replaceMultipleTags($markup, '<del>', $this->delX);
        return $markup;
    }
    /**
     * Replaces multiple ins/del tags and fills them with correct numberings
     * @param string $markup
     * @param string $search
     * @param string $replace
     * @return string
     */
    private function replaceMultipleTags($markup, $search, $replace){
        $count = -1;
        $result = preg_replace_callback('~'.$search.'~', function($matches) use ($count, $replace) {
            $count++;
            return str_replace('{X}', $count, $replace);
        }, $markup);
        return $result;
    }
    /**
     *
     * @param string $markup
     * @return string
     */
    private function revertInsDelTags($markup){
        $markup = preg_replace('~<ins[^>]+>~', '<ins>', $markup);
        $markup = preg_replace('~<del[^>]+>~', '<del>', $markup);
        return $markup;
    }
    /**
     * Replaces short tags with real internal tags
     * @param string $markup
     * @return string
     */
    private function replaceInternalTags($markup){
        $markup = str_replace('<1>', $this->open1, $markup);
        $markup = str_replace('</1>', $this->close1, $markup);
        $markup = str_replace('<2>', $this->open2, $markup);
        $markup = str_replace('</2>', $this->close2, $markup);
        $markup = str_replace('<3>', $this->open3, $markup);
        $markup = str_replace('</3>', $this->close3, $markup);
        $markup = str_replace('<4>', $this->open4, $markup);
        $markup = str_replace('</4>', $this->close4, $markup);
        $markup = str_replace('<5/>', $this->single5, $markup);
        $markup = str_replace('<6/>', $this->single6, $markup);
        $markup = str_replace('<7/>', $this->single7, $markup);
        return $markup;
    }
    /**
     * Reverts the replacing of tgas
     * @param string $markup
     * @return mixed
     */
    private function revertInternalTags($markup){
        $markup = str_replace($this->open1, '<1>', $markup);
        $markup = str_replace($this->close1, '</1>', $markup);
        $markup = str_replace($this->open2, '<2>', $markup);
        $markup = str_replace($this->close2, '</2>', $markup);
        $markup = str_replace($this->open3, '<3>', $markup);
        $markup = str_replace($this->close3, '</3>', $markup);
        $markup = str_replace($this->open4, '<4>', $markup);
        $markup = str_replace($this->close4, '</4>', $markup);
        $markup = str_replace($this->single5, '<5/>', $markup);
        $markup = str_replace($this->single6, '<6/>', $markup);
        $markup = str_replace($this->single7, '<7/>', $markup);
        return $markup;
    }
}