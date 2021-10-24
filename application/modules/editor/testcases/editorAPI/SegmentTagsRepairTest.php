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

/**
 * Several "classic" PHPUnit tests to check the TagRepair which detects faulty structures and fixes them by removing or restructuring the internal tags
 */
class SegmentTagsRepairTest extends editor_Test_SegmentTagsTest {
    
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

    public function testTagComparision0(){
        $fixed = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $this->createRepairTest($fixed, $fixed, []);
    }
    
    public function testTagComparision1(){
        $broken = 'Lorem <1>ipsum</1> dolor sit amet, </2>consetetur sadipscing<5/><2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $fixed = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // wrong order open/close
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision2(){
        $broken = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</3> aliquyam erat</4>, sed diam voluptua.<7/>';
        $fixed = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt</3> ut<6/> labore et <4>dolore</4> magna aliquyam erat, sed diam voluptua.<7/>';
        // overlapping tags
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision3(){
        $broken = 'Lorem <1>ipsum<6/> dolor sit amet, <4>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor </1>invidunt ut<3> labore et <2>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $fixed = 'Lorem <1>ipsum<6/></1> dolor sit amet, <4>consetetur</4> sadipscing<5/><2> elitr, sed diam nonumy eirmod tempor invidunt ut<3> labore</3> et </2>dolore magna aliquyam erat, sed diam voluptua.';
        // faulty structure
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision4(){
        $broken = 'Lorem <1>ipsum<6/> dolor sit amet, <2>consetetur sadipscing</1> elitr, sed diam nonumy eirmod tempor </2>invidunt ut<3> labore et <4>dolore magna</3> aliquyam erat</4>, sed diam voluptua.';
        $fixed = 'Lorem <1>ipsum<6/></1> dolor sit amet, <2>consetetur</2> sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut<3> labore</3> et <4>dolore</4> magna aliquyam erat, sed diam voluptua.';
        // faulty structure
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision5(){
        $fixed = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div>ranslation Management System';
        $broken = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span title="<run1>" class="short">&lt;1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span title="</run1>" class="short" id="ext-element-243">&lt;/1&gt;</span><span data-originalid="1" data-length="-1" class="full">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span title="<run2>" class="short">&lt;2&gt;</span><span data-originalid="2" data-length="-1" class="full">&lt;run2&gt;</span></div>ranslation Management System<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span title="</run3>" class="short">&lt;/3&gt;</span><span data-originalid="3" data-length="-1" class="full">&lt;/run3&gt;</span></div>';
        // test based on real data from the AutoQA approval
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty','internal_tags_added'], true);
    }
    
    public function testTagComparision6(){
        $fixed = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run1&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run1&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run2&gt;">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;run2&gt;</span></div>ranslation <div class="open 6270742069643d2233223e266c743b72756e333e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;run3&gt;">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;run3&gt;</span></div>M<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run3&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/run3&gt;</span></div>anagement System<div class="close 6570742069643d2232223e266c743b2f72756e323e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/run2&gt;">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/run2&gt;</span></div>';
        $broken = '<div class="open 6270742069643d2231223e266c743b72756e313e3c2f627074 internal-tag ownttip"><span class="short" title="<run1>" id="ext-element-241">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;run1&gt;</span></div>T<div class="close 6570742069643d2231223e266c743b2f72756e313e3c2f657074 internal-tag ownttip"><span class="short" title="</run1>">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/run1&gt;</span></div><div class="open 6270742069643d2232223e266c743b72756e323e3c2f627074 internal-tag ownttip"><span class="short" title="<run2>">&lt;2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;run2&gt;</span></div>ranslation <div class="open 6270742069643d2233223e266c743b72756e333e3c2f627074 internal-tag ownttip"><span class="short" title="<run3>">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;run3&gt;</span></div>M<div class="close 6570742069643d2233223e266c743b2f72756e333e3c2f657074 internal-tag ownttip"><span class="short" title="</run3>">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/run3&gt;</span></div>anagement <div class="close 6270742069643d2234223e266c743b72756e343e3c2f627074 internal-tag ownttip"><span class="short" title="<run4>">&lt;4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;run4&gt;</span></div>S<div class="close 6570742069643d2234223e266c743b2f72756e343e3c2f657074 internal-tag ownttip"><span class="short" title="</run4>">&lt;/4&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;/run4&gt;</span></div>ystem<div class="close 6570742069643d2232223e266c743b2f72756e323e3c2f657074 internal-tag ownttip"><span class="short" title="</run2>">&lt;/2&gt;</span><span class="full" data-originalid="2" data-length="-1">&lt;/run2&gt;</span></div>';
        // test based on real data from the AutoQA approval
        $this->createRepairTest($fixed, $broken, ['internal_tag_structure_faulty', 'internal_tags_added'], true);
    }
    /**
     * Creates a test for the internal tag comparision. The passed markup will have the following markup replaced with internal tags
     * Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>
     * @param string $fixed
     * @param string $broken
     * @param array|string $expectedState
     */
    private function createRepairTest($fixed, $broken, $expectedState, $doNotConvert=false){
        $fixedTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->replaceInternalTags($fixed), 'target', 'targetEdit');
        $brokenTags = new editor_Segment_FieldTags($this->getTestTask(), 123456, $this->replaceInternalTags($broken), 'target', 'targetEdit');
        // first, compare to find errors
        $tagComparision = new editor_Segment_Internal_TagComparision($brokenTags, $fixedTags);
        $this->assertEquals($expectedState, $tagComparision->getStati());
        
        $hasFaults = in_array('internal_tag_structure_faulty', $expectedState);
        $tagRepair = new editor_Segment_Internal_TagRepair($brokenTags, NULL);
        $this->assertEquals($hasFaults, $tagRepair->hadErrors());
        
        $fixedTags = ($doNotConvert) ? $brokenTags->render() : $this->revertInternalTags($brokenTags->render());
        if($hasFaults){            
            $this->assertEquals($fixed, $fixedTags);
            // echo "\n========== HAD ERRORS ===========\n".$broken."\n".$fixedTags."\n============\n";
        } else {
            $this->assertEquals($broken, $fixedTags);
            // echo "\n========== HAD ERRORS ===========\n".$broken."\n".$fixedTags."\n============\n";
        }
        // make sure the fixed tags would be detected as correct
        $tagComparision = new editor_Segment_Internal_TagComparision($brokenTags, NULL);
        $this->assertEquals([], $tagComparision->getStati());
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
     * 
     * @param string $markup
     * @return string
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
