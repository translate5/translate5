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

use MittagQI\Translate5\Segment\TagRepair\Tags;

/**
 * Several "classic" PHPUnit tests to check the general tag repair (not to mix up with the segment tags repair)
 * When creating test-data notice, that if a "<" shall be used in the markup it must be followed by a " "
 */
class TagsRepairTest extends editor_Test_MockedTaskTest {

    /**
     * Some Internal Tags to create Tests with
     */
    private $tags = [
        '<1>' => '<div class="test" id="ex12345" data-sth="test">',
        '</1>' => '</div>',
        '<2>' => '<span class="test">',
        '</2>' => '</span>',
        '<3>' => '<a href="http://www.example.com">',
        '</3>' => '</a>',
        '<4>' => '<div class="test2" id="uc3456" data-sth="test2">',
        '</4>' => '</div>',
        '<5>' => '<b>',
        '</5>' => '</b>',
        '<6/>' => '<br />',
        '<7/>' => '<img src="http://www.example.com/image.jpg" />',
        '<8/>' => '<hr />',
        '<9/>' => '<input name="test" type="text" value="test" />',
    ];

    public function testTagRepair0(){
        $markup = '<1><8/>Ein kurzer Satz</1>,<6/> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1><8/>Ein kurzer Satz</1>,<6/> der übersetzt<7/> werden <2>muss<9/></2>';
        $this->createTagsRepairTest($markup, '', $translated, true);
    }

    public function testTagRepair1(){
        $markup = '<1><8/>Ein kurzer Satz,</1><6/> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = 'Ein kurzer Satz, der übersetzt werden muss';
        $this->createTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair2(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1><8/>Ein kurzer Satz, der übersetzt<7/> werden muss<9/></2>';
        $this->createTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair3(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1><8/>Ein kurzer Satz, der übersetzt<7/> werden muss<9/></2>';
        $this->createTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair4(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1>Ein kurzer Satz, der übersetzt<7/> werden muss<9/></2>';
        $this->createTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair5(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $translated = '<1>Ein kurzer Satz, der übersetzt werden muss</2>';
        $this->createTagsRepairTest($markup, '', $translated);
    }

    public function testTagRepair6(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $translated = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair7(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair8(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that <2>has to be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that <2>has to be</2> translated<7/>';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair9(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to <2>be<9/></2> translated<7/>';
        $translated = '<1>A short sentence,</1> that has to be</2> translated<7/>';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair10(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to <2>be<9/></2> translated<7/>';
        $translated = '<1>A short sentence, that has to be</2> translated<7/>';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair11(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>A short sentence,</1> that has to<7/> be <2>translated<9/></2>';
        $translated = 'A short sentence, that has to be translated';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair12(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/></1>NOTHING!<7/><2><9/></2>';
        $translated = 'NOTHING!';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair13(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt<7/> werden <2>muss<9/></2>';
        $expected = '<1><8/>JUST</1> THREE<7/> WORDS<2><9/></2>';
        $translated = 'JUST THREE WORDS';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair14(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>JUST</1> THREE <7/>WORDS<2><9/></2>';
        $translated = 'JUST THREE WORDS';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair15(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $translated = '<1><8/>This now is a somehow longer sentence,</1> that is <2>really completely changed<9/></2>, as it<7/> can happen';
        //$translated = 'This now is a somehow longer sentence, that is really completely changed, as it can happen';
        $this->createTagsRepairTest($markup, $translated, $translated);
    }

    public function testTagRepair16(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>This now is a somehow longer sentence,</1> that is <2>really completely<9/></2> changed, as it<7/> can happen';
        $translated = 'This now is a somehow longer sentence,</1> that is <2>really completely changed, as it<7/> can happen';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair17(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>This now is a somehow longer sentence,</1> that is <2>really completely<9/></2> <7/>changed, as it can happen';
        $translated = 'This now is a somehow longer sentence, that is <2>really completely changed, as it can happen';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair18(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>This now is a somehow longer sentence,</1> that is really completely <7/>changed, as it <2>can happen<9/></2>';
        $translated = 'This now is a somehow longer sentence, that is really completely changed, as it can happen';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair19(){
        $markup = ' <1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2> ';
        $expected = '<1><8/> a a a a a a a a a a a a a a a a a a a a a a</1> a a a a a a a a a a a a a a a a a a a <7/>a a a a a a a <2>a a a a a a a a<9/></2> a ';
        $translated = ' a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a a ';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }

    public function testTagRepair20(){
        $markup = '<1><8/>Ein kurzer Satz,</1> der übersetzt <7/>werden <2>muss<9/></2>';
        $expected = '<1><8/>Hier     ist eine      Menge Whitespace</1>     drin,  auch wenn das      <7/>eigentlich <2>nicht vorkommt!<9/></2>';
        $translated = 'Hier     ist eine      Menge Whitespace     drin,  auch wenn das      eigentlich nicht vorkommt!';
        $this->createTagsRepairTest($markup, $expected, $translated);
    }
    /**
     * @param string $originalMarkup
     * @param string $translatedMarkup
     * @throws ZfExtended_Exception
     */
    protected function createTagsRepairTest(string $originalMarkup, string $expectedMarkup, string $translatedMarkup){
        $markup = $this->replaceInternalTags($originalMarkup);
        $tags = new Tags($markup);
        $expected = (empty($expectedMarkup)) ? $tags->render() : $this->replaceInternalTags($expectedMarkup);
        $request = $tags->getRequestHtml();
        $translated = $this->replaceRequestTags($translatedMarkup, $originalMarkup, $request);
        $actual = $tags->recreateTags($translated);

        // debugging
        if(false){
            error_log('===================');
            error_log('ORIGINAL: '.$originalMarkup);
            error_log('TRANSLATED: '.$translatedMarkup);
            error_log('RECREATED: '.$this->revertInternalTags($actual));
            error_log('===================');
        }
        $this->assertEquals($expected, $actual);
    }
    /**
     * Replaces short tags with real internal tags
     * @param string $markup
     * @return string
     */
    private function replaceInternalTags(string $markup) : string{
        foreach(array_keys($this->tags) as $key){
            $markup = str_replace($key, $this->tags[$key], $markup);
        }
        return $markup;
    }
    /**
     * @param $markup
     * @return string
     */
    private function replaceRequestTags(string $markup, string $original, string $request) : string {
        $pattern = '~(<[^ ][^>]*>)~i';
        $originalParts = preg_split($pattern, $original, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $requestParts = preg_split($pattern, $request, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $num = count($originalParts);
        for($i=0; $i < $num; $i++){
            if(preg_match($pattern, $originalParts[$i]) === 1){
                $markup = str_replace($originalParts[$i], $requestParts[$i], $markup);
            }
        }
        return $markup;
    }
    /**
     * @param string $markup
     * @return string
     */
    private function revertInternalTags(string $markup) : string {
        foreach(array_keys($this->tags) as $key){
            $markup = str_replace($this->tags[$key], $key, $markup);
        }
        return $markup;
    }
}