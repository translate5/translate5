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
 * Several "classic" PHPUnit tests to check the OOP Tag-Parsing API againsted selected test data
 */
class SegmentTagsTest extends \ZfExtended_Test_Testcase {
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
    
    public function testUnicodeTag(){
        $expected = '<div><p>イリノイ州シカゴにて、アイルランド系の家庭に、</p></div>';
        $dom = new editor_Utils_Dom();
        $element = $dom->loadUnicodeElement($expected);
        $result = $dom->saveHTML($element);
        $this->assertEquals($result, $expected);
    }
    
    public function testUnicodeWhitespaceTag(){
        $expected = '<div><p>イリノイ州シカゴにて、アイルランド系の家庭に、</p></div>';
        $dom = new editor_Utils_Dom();
        $element = $dom->loadUnicodeElement('  Hello! '.$expected.', something else, ...');
        $result = $dom->saveHTML($element);
        $this->assertEquals($expected, $result);
    }
    
    public function testMultipleUnicodeWhitespaceTag(){
        $expected = ' ÜüÖöÄäß? Japanisch: <div>イリノイ州シカゴにて、</div><p>アイルランド系の家庭に、</p> additional Textnode :-)';
        $dom = new editor_Utils_Dom();
        $elements = $dom->loadUnicodeMarkup($expected);
        $result = '';
        foreach($elements as $element){
            $result .= $dom->saveHTML($element);
        }
        $this->assertEquals($expected, $result);
    }
    
    public function testSimpleTag(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42"><span>Link Text</span> <img class="upfront link-img" src="/some/icon.svg" /></a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($result, $expected);
    }
    
    public function testTagWithAttributes(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42"><span>Link Text</span> <img class="upfront link-img" src="/some/icon.svg" /></a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($expected, $result);
    }
    
    public function testClassOrder(){
        $expected = '<div class="zzz 12wer www aaa sss">Some Content</div>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($result, $expected);
        $tag2 = editor_Tag::unparse($expected);
        $this->assertTrue($tag2->isEqual($tag));
    }
    
    public function testTagWithUnescapedChars(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42">"Something" is &lt; "Something" else</a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($expected, $result);
    }
    
    public function testTagJSON(){
        $segmentTag = new editor_Segment_AnyTag(6, 11, 'test', 'div');
        $segmentTag
        ->addClasses('zclass aclass bclass')
        ->addOnEvent('click', "window.open('page');")
        ->addAttribute('rel', 'something')
        ->setData('some-name', 'some "data"')
        ->setData('other-name', 12345);
        $result = $segmentTag->toJson();
        $expected = '{"type":"any","name":"div","category":"test","startIndex":6,"endIndex":11,"classes":["zclass","aclass","bclass"],"attribs":[{"name":"onclick","value":"window.open(\'page\');"},{"name":"rel","value":"something"},{"name":"data-some-name","value":"some \"data\""},{"name":"data-other-name","value":"12345"}]}';
        $this->assertEquals($expected, $result);
    }
    
    public function testSingleTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(6, 11, 'test', 'a'));
        $markup = 'Lorem <a>ipsum</a> dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testMultipleTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(6, 26, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyTag(50, 72, 'test', 'b'));
        $markup = 'Lorem <a>ipsum dolor sit amet</a>, consetetur sadipscing <b>elitr, sed diam nonumy</b> eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testOverlappingTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(6, 26, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyTag(50, 72, 'test', 'b'));
        $tags->addTag(new editor_Segment_AnyTag(18, 60, 'test', 'c'));
        $markup = 'Lorem <a>ipsum dolor </a><c><a>sit amet</a>, consetetur sadipscing </c><b><c>elitr, sed</c> diam nonumy</b> eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testOverlappingNestedTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(6, 26, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyTag(50, 72, 'test', 'b'));
        $tags->addTag(new editor_Segment_AnyTag(50, 55, 'test', 'c'));
        $tags->addTag(new editor_Segment_AnyTag(18, 60, 'test', 'd'));
        $markup = 'Lorem <a>ipsum dolor </a><d><a>sit amet</a>, consetetur sadipscing </d><b><d><c>elitr</c>, sed</d> diam nonumy</b> eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testOverlappingNestedFulllengthTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(0, 80, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyTag(0, 80, 'test', 'b'));
        $tags->addTag(new editor_Segment_AnyTag(6, 26, 'test', 'c'));
        $tags->addTag(new editor_Segment_AnyTag(50, 72, 'test', 'd'));
        $tags->addTag(new editor_Segment_AnyTag(18, 60, 'test', 'e'));
        $markup = '<a><b>Lorem <c>ipsum dolor </c><e><c>sit amet</c>, consetetur sadipscing </e><d><e>elitr, sed</e> diam nonumy</d> eirmod.</b></a>';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testSingularNestedTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(5, 5, 'test', 'div'));
        $tags->addTag(new editor_Segment_AnyTag(5, 5, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyTag(50, 50, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyTag(50, 50, 'test', 'div'));
        $markup = 'Lorem<div><img /></div> ipsum dolor sit amet, consetetur sadipscing <div><img /></div>elitr, sed diam nonumy eirmod.';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testSingularNestedFulllengthTags(){
        $tags = $this->createTags();
        $tags->addTag(new editor_Segment_AnyTag(5, 5, 'test', 'div'));
        $tags->addTag(new editor_Segment_AnyTag(5, 5, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyTag(50, 50, 'test', 'img'));
        $tags->addTag(new editor_Segment_AnyTag(50, 50, 'test', 'div'));
        $tags->addTag(new editor_Segment_AnyTag(0, 80, 'test', 'a'));
        $tags->addTag(new editor_Segment_AnyTag(0, 80, 'test', 'b'));
        $markup = '<a><b>Lorem<div><img /></div> ipsum dolor sit amet, consetetur sadipscing <div><img /></div>elitr, sed diam nonumy eirmod.</b></a>';
        $this->createTagsTest($tags, $markup);
    }
    
    public function testUnescapedChars(){
        // testing "real" segment content
        $segmentId = 677867;
        $markup = '<a href="http://www.google.de" target="blank" data-test="42">"Something" is &lt; "Something" else</a>';
        $this->createDataTest($segmentId, $markup);
    }
    
    public function testRealDataTags1(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '[<div class="open 672069643d22393222 internal-tag ownttip"><span title="&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;</span></div>edit<div class="close 2f67 internal-tag ownttip"><span title="&lt;/a&gt;" class="short">&lt;/1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;/a&gt;</span></div>] Last updated:';
        $markup = '[<div class="672069643d22393222 internal-tag open ownttip"><span class="short" title="&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="92" data-length="-1">&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;</span></div>edit<div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/1&gt;</span><span class="full" data-originalid="92" data-length="-1">&lt;/a&gt;</span></div>] Last updated:';
        $this->createOriginalDataTest($segmentId, $original, $markup);
    }
    
    public function testRealDataTags2(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677836;
        $original = 'cd httpd-2_x_NN<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>./configure --enable-so<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;2/&gt;: Newline" class="short">&lt;2/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>make<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;3/&gt;: Newline" class="short">&lt;3/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>make install';
        $markup = 'cd httpd-2_x_NN<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>./configure --enable-so<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;2/&gt;: Newline">&lt;2/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>make<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;3/&gt;: Newline">&lt;3/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>make install';
        $this->createOriginalDataTest($segmentId, $original, $markup);
    }
    
    public function testRealDataTags3(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '&lt;FilesMatch \.php$&gt;<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div> <div class="single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip"><span title="&lt;2/&gt;: 3 whitespace characters" class="short">&lt;2/&gt;</span><span data-originalid="space" data-length="3" class="full">···</span></div>SetHandler application/x-httpd-php<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;3/&gt;: Newline" class="short">&lt;3/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>&lt;/FilesMatch&gt;';
        $markup = '&lt;FilesMatch \.php$&gt;<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="73706163652074733d2232303230323022206c656e6774683d2233222f internal-tag ownttip single space"><span class="short" title="&lt;2/&gt;: 3 whitespace characters">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="3">···</span></div>SetHandler application/x-httpd-php<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;3/&gt;: Newline">&lt;3/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>&lt;/FilesMatch&gt;';
        $this->createOriginalDataTest($segmentId, $original, $markup);
    }
    
    public function testRealDataTags4(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '<div class="open 672069643d22383022 internal-tag ownttip"><span title="&lt;span class=&quot;next&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="80" data-length="-1" class="full">&lt;span class=&quot;next&quot;&gt;</span></div><div class="open 672069643d22383122 internal-tag ownttip"><span title="&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="81" data-length="-1" class="full">&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;</span></div>Lighttpd 1.4 on Unix systems<div class="single 782069643d22383422207869643d2231333031346134632d323432302d343638342d386466392d623037333034666634306330222f internal-tag ownttip"><span title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;" class="short">&lt;3/&gt;</span><span data-originalid="84" data-length="-1" class="full">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/a&gt;" class="short">&lt;/2&gt;</span><span data-originalid="81" data-length="-1" class="full">&lt;/a&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/span&gt;" class="short">&lt;/1&gt;</span><span data-originalid="80" data-length="-1" class="full">&lt;/span&gt;</span></div> <div class="open 672069643d22383522 internal-tag ownttip"><span title="&lt;span class=&quot;prev&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="85" data-length="-1" class="full">&lt;span class=&quot;prev&quot;&gt;</span></div><div class="open 672069643d22383622 internal-tag ownttip"><span title="&lt;a href=&quot;install.unix.apache.php&quot;&gt;" class="short">&lt;5&gt;</span><span data-originalid="86" data-length="-1" class="full">&lt;a href=&quot;install.unix.apache.php&quot;&gt;</span></div><div class="single 782069643d22383922207869643d2233613165616535382d613363332d346338642d613166342d643135333633343339666330222f internal-tag ownttip"><span title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;" class="short">&lt;6/&gt;</span><span data-originalid="89" data-length="-1" class="full">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div>Apache 1.3.x on Unix systems<div class="close 2f67 internal-tag ownttip"><span title="&lt;/a&gt;" class="short">&lt;/5&gt;</span><span data-originalid="86" data-length="-1" class="full">&lt;/a&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/span&gt;" class="short">&lt;/4&gt;</span><span data-originalid="85" data-length="-1" class="full">&lt;/span&gt;</span></div>';
        $markup = '<div class="672069643d22383022 internal-tag open ownttip"><span class="short" title="&lt;span class=&quot;next&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="80" data-length="-1">&lt;span class=&quot;next&quot;&gt;</span></div><div class="672069643d22383122 internal-tag open ownttip"><span class="short" title="&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="81" data-length="-1">&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;</span></div>Lighttpd 1.4 on Unix systems<div class="782069643d22383422207869643d2231333031346134632d323432302d343638342d386466392d623037333034666634306330222f internal-tag ownttip single"><span class="short" title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;">&lt;3/&gt;</span><span class="full" data-originalid="84" data-length="-1">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div><div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/2&gt;</span><span class="full" data-originalid="81" data-length="-1">&lt;/a&gt;</span></div><div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/span&gt;">&lt;/1&gt;</span><span class="full" data-originalid="80" data-length="-1">&lt;/span&gt;</span></div> <div class="672069643d22383522 internal-tag open ownttip"><span class="short" title="&lt;span class=&quot;prev&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="85" data-length="-1">&lt;span class=&quot;prev&quot;&gt;</span></div><div class="672069643d22383622 internal-tag open ownttip"><span class="short" title="&lt;a href=&quot;install.unix.apache.php&quot;&gt;">&lt;5&gt;</span><span class="full" data-originalid="86" data-length="-1">&lt;a href=&quot;install.unix.apache.php&quot;&gt;</span></div><div class="782069643d22383922207869643d2233613165616535382d613363332d346338642d613166342d643135333633343339666330222f internal-tag ownttip single"><span class="short" title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;">&lt;6/&gt;</span><span class="full" data-originalid="89" data-length="-1">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div>Apache 1.3.x on Unix systems<div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/5&gt;</span><span class="full" data-originalid="86" data-length="-1">&lt;/a&gt;</span></div><div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/span&gt;">&lt;/4&gt;</span><span class="full" data-originalid="85" data-length="-1">&lt;/span&gt;</span></div>';
        $this->createOriginalDataTest($segmentId, $original, $markup);
    }
    
    public function testRealDataTags5(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '&lt;FilesMatch "\.phps$"&gt;<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">\xe2\x86\xb5</span></div> <div class="single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip"><span title="&lt;2/&gt;: 3 whitespace characters" class="short">&lt;2/&gt;</span><span data-originalid="space" data-length="3" class="full">\xc2\xb7\xc2\xb7\xc2\xb7</span></div>SetHandler application/x-httpd-php-source<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;3/&gt;: Newline" class="short">&lt;3/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">\xe2\x86\xb5</span></div>&lt;/FilesMatch&gt;';
        $markup = '&lt;FilesMatch "\.phps$"&gt;<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">\xe2\x86\xb5</span></div> <div class="73706163652074733d2232303230323022206c656e6774683d2233222f internal-tag ownttip single space"><span class="short" title="&lt;2/&gt;: 3 whitespace characters">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="3">\xc2\xb7\xc2\xb7\xc2\xb7</span></div>SetHandler application/x-httpd-php-source<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;3/&gt;: Newline">&lt;3/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">\xe2\x86\xb5</span></div>&lt;/FilesMatch&gt;';
        $this->createOriginalDataTest($segmentId, $original, $markup);
    }
    
    public function testRealDataTags6(){
        // testing "real" segment content
        $segmentId = 677867;
        $markup = 'This file is a based on a part of the php-online-Documentation. It\'s translation is done by a pretranslation based on a very fast winalign-Project and is not at all state of the translation art. It\'s only purpose is the generation of demo-data for translate5.';
        $this->createDataTest($segmentId, $markup);
    }
    
    public function testRealDataTags7(){
        // testing "real" segment content
        $segmentId = 688499;
        $markup = '<div class="open 6270742069643d2231223e266c743b7370616e207374796c653d22666f6e742d7765696768743a3635303b223e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;span style=&quot;font-weight:650;&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;span style="font-weight:650;"></span></div>HOSTED<div class="close 6570742069643d2231223e266c743b2f7370616e3e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/span&gt;">&lt;/1&gt;</span><span class="full" data-originalid="1" data-length="-1">&lt;/span></span></div><div class="single 70682069643d2232223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;2/&gt;</span><span class="full" data-originalid="97fc34569f6c6899fd64ece1dd7d3c62" data-length="-1">&lt;br></span></div><div class="open 6270742069643d2233223e5b232464703138345d3c2f627074 internal-tag ownttip"><span class="short" title="[#$dp184]">&lt;3&gt;</span><span class="full" data-originalid="3" data-length="-1">[#$dp184]</span></div>Team Basic<div class="close 6570742069643d2233223e266c743b2f613e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/3&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;/a></span></div><div class="single 70682069643d2234223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;4/&gt;</span><span class="full" data-originalid="9e401d2dc35e658e375584f4603b571a" data-length="-1">&lt;br></span></div><div class="open 6270742069643d2235223e5b232464703138355d3c2f627074 internal-tag ownttip"><span class="short" title="[#$dp185]">&lt;5&gt;</span><span class="full" data-originalid="5" data-length="-1">[#$dp185]</span></div>Team Visual<div class="close 6570742069643d2235223e266c743b2f613e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/5&gt;</span><span class="full" data-originalid="5" data-length="-1">&lt;/a></span></div><div class="single 70682069643d2236223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;6/&gt;</span><span class="full" data-originalid="f030e73f6576c27a0b91b2ca6531f204" data-length="-1">&lt;br></span></div><div class="open 6270742069643d2237223e5b232464703138365d3c2f627074 internal-tag ownttip"><span class="short" title="[#$dp186]">&lt;7&gt;</span><span class="full" data-originalid="7" data-length="-1">[#$dp186]</span></div>Community Member<div class="close 6570742069643d2237223e266c743b2f613e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/7&gt;</span><span class="full" data-originalid="7" data-length="-1">&lt;/a></span></div> <div class="single 70682069643d2238223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;8/&gt;</span><span class="full" data-originalid="91f73f45f868038dcf6e6e9331ae2395" data-length="-1">&lt;br></span></div><div class="open 6270742069643d2239223e266c743b7370616e207374796c653d22666f6e742d7765696768743a3635303b223e3c2f627074 internal-tag ownttip"><span class="short" title="&lt;span style=&quot;font-weight:650;&quot;&gt;">&lt;9&gt;</span><span class="full" data-originalid="9" data-length="-1">&lt;span style="font-weight:650;"></span></div>ON PREMISE<div class="close 6570742069643d2239223e266c743b2f7370616e3e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/span&gt;">&lt;/9&gt;</span><span class="full" data-originalid="9" data-length="-1">&lt;/span></span></div><div class="single 70682069643d223130223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;10/&gt;</span><span class="full" data-originalid="4313c506b79673e96be8a180ae5c013b" data-length="-1">&lt;br></span></div><div class="open 6270742069643d223131223e5b232464703138375d3c2f627074 internal-tag ownttip"><span class="short" title="[#$dp187]">&lt;11&gt;</span><span class="full" data-originalid="11" data-length="-1">[#$dp187]</span></div>Free<div class="close 6570742069643d223131223e266c743b2f613e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/11&gt;</span><span class="full" data-originalid="11" data-length="-1">&lt;/a></span></div><div class="single 70682069643d223132223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;12/&gt;</span><span class="full" data-originalid="a26f1e58f9df9a4dccd3147e2b35aa8a" data-length="-1">&lt;br></span></div><div class="open 6270742069643d223133223e5b232464703138385d3c2f627074 internal-tag ownttip"><span class="short" title="[#$dp188]">&lt;13&gt;</span><span class="full" data-originalid="13" data-length="-1">[#$dp188]</span></div>Community Member Basic<div class="close 6570742069643d223133223e266c743b2f613e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/13&gt;</span><span class="full" data-originalid="13" data-length="-1">&lt;/a></span></div><div class="single 70682069643d223134223e266c743b62723e3c2f7068 internal-tag ownttip"><span class="short" title="&lt;br&gt;">&lt;14/&gt;</span><span class="full" data-originalid="ec2124fa59c0b8410f4ee9a00eb3ee27" data-length="-1">&lt;br></span></div><div class="open 6270742069643d223135223e5b232464703138395d3c2f627074 internal-tag ownttip"><span class="short" title="[#$dp189]">&lt;15&gt;</span><span class="full" data-originalid="15" data-length="-1">[#$dp189]</span></div>Community Member Visual<div class="close 6570742069643d223135223e266c743b2f613e3c2f657074 internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/15&gt;</span><span class="full" data-originalid="15" data-length="-1">&lt;/a></span></div>';
        $this->createDataTest($segmentId, $markup);
    }
    
    public function testRealDataTags8(){
        // testing "real" segment content
        $segmentId = 688498;
        $original = '<div class="open 672069643d2233313422 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="314" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>Aktualizacja 07-2<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/1&gt;</span><span data-originalid="314" data-length="-1" class="full">&lt;/cf&gt;</span></div><div class="open 672069643d2233313622 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="316" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>0<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/2&gt;</span><span data-originalid="316" data-length="-1" class="full">&lt;/cf&gt;</span></div><div class="open 672069643d2233313722 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;3&gt;</span><span data-originalid="317" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/3&gt;</span><span data-originalid="317" data-length="-1" class="full">&lt;/cf&gt;</span></div><div class="open 672069643d2233313822 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="318" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>6 (akt.<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/4&gt;</span><span data-originalid="318" data-length="-1" class="full">&lt;/cf&gt;</span></div><div class="open 672069643d2233313922 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;5&gt;</span><span data-originalid="319" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div> 0<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/5&gt;</span><span data-originalid="319" data-length="-1" class="full">&lt;/cf&gt;</span></div><div class="open 672069643d2233323022 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;6&gt;</span><span data-originalid="320" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/6&gt;</span><span data-originalid="320" data-length="-1" class="full">&lt;/cf&gt;</span></div><div class="open 672069643d2233323122 internal-tag ownttip"><span title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;" class="short">&lt;7&gt;</span><span data-originalid="321" data-length="-1" class="full">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>)<div class="close 2f67 internal-tag ownttip"><span title="&lt;/cf&gt;" class="short">&lt;/7&gt;</span><span data-originalid="321" data-length="-1" class="full">&lt;/cf&gt;</span></div>';
        $markup = '<div class="open 672069643d2233313422 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="314" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>Aktualizacja 07-2<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/1&gt;</span><span class="full" data-originalid="314" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313622 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="316" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>0<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/2&gt;</span><span class="full" data-originalid="316" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313722 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="317" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/3&gt;</span><span class="full" data-originalid="317" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313822 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="318" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>6 (akt.<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/4&gt;</span><span class="full" data-originalid="318" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313922 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;5&gt;</span><span class="full" data-originalid="319" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div> 0<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/5&gt;</span><span class="full" data-originalid="319" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233323022 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;6&gt;</span><span class="full" data-originalid="320" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/6&gt;</span><span class="full" data-originalid="320" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233323122 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;7&gt;</span><span class="full" data-originalid="321" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>)<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/7&gt;</span><span class="full" data-originalid="321" data-length="-1">&lt;/cf&gt;</span></div>';
        $this->createOriginalDataTest($segmentId, $original, $markup);
    }
    
    public function testInternalTags1(){
        // testing "real" segment content
        $segmentId = 688499;
        $compareState = '';
        $markup = '<div class="open 672069643d2233313422 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="314" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>Aktualizacja 07-2<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/1&gt;</span><span class="full" data-originalid="314" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313622 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="316" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>0<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/2&gt;</span><span class="full" data-originalid="316" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313722 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="317" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/3&gt;</span><span class="full" data-originalid="317" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313822 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="318" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>6 (akt.<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/4&gt;</span><span class="full" data-originalid="318" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233313922 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;5&gt;</span><span class="full" data-originalid="319" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div> 0<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/5&gt;</span><span class="full" data-originalid="319" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233323022 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;6&gt;</span><span class="full" data-originalid="320" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>1<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/6&gt;</span><span class="full" data-originalid="320" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2233323122 internal-tag ownttip"><span class="short" title="&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;">&lt;7&gt;</span><span class="full" data-originalid="321" data-length="-1">&lt;cf size=&quot;5.5&quot; font=&quot;Frutiger Next LT W1G&quot; nfa=&quot;true&quot;&gt;</span></div>)<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/7&gt;</span><span class="full" data-originalid="321" data-length="-1">&lt;/cf&gt;</span></div>';
        $this->createInternalTagDataTest($segmentId, $markup, $compareState);
    }
    
    public function testInternalTags2(){
        // testing "real" segment content
        $segmentId = 688500;
        $compareState = '';
        $markup = 'W<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;13/&gt;: Non breaking space">&lt;13/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>niniejszej dokumentacji przedstawione są zalecenia dotyczące bezpieczeństwa, w<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;14/&gt;: Non breaking space">&lt;14/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>rozdziale<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;15/&gt;: Non breaking space">&lt;15/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="single 70682069643d2231223e266c743b78726566206e616d653d2671756f743b5061726167726170684e756d6265722671756f743b206c696e69643d2671756f743b3131322671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;1&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;112&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;2/&gt;</span><span class="full" data-originalid="953b38d526718e8217f4e2d6a4333ec1" data-length="-1">&lt;ph id=&quot;1&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;112&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div> "<div class="single 70682069643d2232223e266c743b78726566206e616d653d2671756f743b506172616772617068546578742671756f743b206c696e69643d2671756f743b3131332671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;2&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;113&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;3/&gt;</span><span class="full" data-originalid="33f52a76d22832a3e44b45f58bfbffc3" data-length="-1">&lt;ph id=&quot;2&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;113&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div>" na stronie<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;16/&gt;: Non breaking space">&lt;16/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="open 6270742069643d223322207269643d2233223e266c743b756620756663617469643d2671756f743b322671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;3&quot; rid=&quot;3&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;17&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;bpt id=&quot;3&quot; rid=&quot;3&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div><div class="single 70682069643d2234223e266c743b78726566206e616d653d2671756f743b506167654e756d6265722671756f743b206c696e69643d2671756f743b3131342671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;4&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;114&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;6/&gt;</span><span class="full" data-originalid="12e9cebb1d9f5aba3b8c5ca68ad33d01" data-length="-1">&lt;ph id=&quot;4&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;114&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div><div class="close 6570742069643d223522207269643d2233223e266c743b2f75662667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;5&quot; rid=&quot;3&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;">&lt;/17&gt;</span><span class="full" data-originalid="3" data-length="-1">&lt;ept id=&quot;5&quot; rid=&quot;3&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;</span></div> oraz w<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;18/&gt;: Non breaking space">&lt;18/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>rozdziale <div class="single 70682069643d2236223e266c743b78726566206e616d653d2671756f743b5061726167726170684e756d6265722671756f743b206c696e69643d2671756f743b3131352671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;6&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;115&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;8/&gt;</span><span class="full" data-originalid="ab127e462541fe536923e31bfcee1cad" data-length="-1">&lt;ph id=&quot;6&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphNumber&amp;quot; linid=&amp;quot;115&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div> "<div class="single 70682069643d2237223e266c743b78726566206e616d653d2671756f743b506172616772617068546578742671756f743b206c696e69643d2671756f743b3131362671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;7&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;116&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;9/&gt;</span><span class="full" data-originalid="24a854f453c613624ce9ba28ad02a42a" data-length="-1">&lt;ph id=&quot;7&quot;&gt;&amp;lt;xref name=&amp;quot;ParagraphText&amp;quot; linid=&amp;quot;116&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div>" na stronie<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;19/&gt;: Non breaking space">&lt;19/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div><div class="open 6270742069643d223822207269643d2234223e266c743b756620756663617469643d2671756f743b322671756f743b2667743b3c2f627074 internal-tag ownttip"><span class="short" title="&lt;bpt id=&quot;8&quot; rid=&quot;4&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;">&lt;20&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;bpt id=&quot;8&quot; rid=&quot;4&quot;&gt;&amp;lt;uf ufcatid=&amp;quot;2&amp;quot;&amp;gt;&lt;/bpt&gt;</span></div><div class="single 70682069643d2239223e266c743b78726566206e616d653d2671756f743b506167654e756d6265722671756f743b206c696e69643d2671756f743b3131372671756f743b202f2667743b3c2f7068 internal-tag ownttip"><span class="short" title="&lt;ph id=&quot;9&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;117&amp;quot; /&amp;gt;&lt;/ph&gt;">&lt;12/&gt;</span><span class="full" data-originalid="eb7edd2163941ca5554db4d6cac6f432" data-length="-1">&lt;ph id=&quot;9&quot;&gt;&amp;lt;xref name=&amp;quot;PageNumber&amp;quot; linid=&amp;quot;117&amp;quot; /&amp;gt;&lt;/ph&gt;</span></div><div class="close 6570742069643d22313022207269643d2234223e266c743b2f75662667743b3c2f657074 internal-tag ownttip"><span class="short" title="&lt;ept id=&quot;10&quot; rid=&quot;4&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;">&lt;/20&gt;</span><span class="full" data-originalid="4" data-length="-1">&lt;ept id=&quot;10&quot; rid=&quot;4&quot;&gt;&amp;lt;/uf&amp;gt;&lt;/ept&gt;</span></div>, a<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;21/&gt;: Non breaking space">&lt;21/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>także informacje o<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;22/&gt;: Non breaking space">&lt;22/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>procedurze działań lub instrukcje działań, w<div class="single 636861722074733d226332613022206c656e6774683d2231222f nbsp internal-tag ownttip"><span class="short" title="&lt;23/&gt;: Non breaking space">&lt;23/&gt;</span><span class="full" data-originalid="char" data-length="1">⎵</span></div>przypadku których występuje zagrożenie powstania szkód osobowych lub materialnych.';
        $this->createInternalTagDataTest($segmentId, $markup, $compareState);
    }
    
    public function testInternalTags3(){
        // testing "real" segment content
        $segmentId = 688501;
        $compareState = '';
        $markup = '<div class="open 672069643d2232353822 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="258" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>-<div class="single 782069643d22323539222f internal-tag ownttip"><span class="short" title="&lt;char name=&quot;Indent&quot;/&gt;">&lt;2/&gt;</span><span class="full" data-originalid="259" data-length="-1">&lt;char name=&quot;Indent&quot;/&gt;</span></div> Supawash - high pressure washing<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/1&gt;</span><span class="full" data-originalid="258" data-length="-1">&lt;/cf&gt;</span></div><div class="open 672069643d2232363022 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;3&gt;</span><span class="full" data-originalid="260" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;4/&gt;: Newline">&lt;4/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div><div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span class="short" title="&lt;5/&gt;: 1 tab character">&lt;5/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div><div class="open 672069643d2232363122 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;6&gt;</span><span class="full" data-originalid="261" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>system<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/6&gt;</span><span class="full" data-originalid="261" data-length="-1">&lt;/cf&gt;</span></div> <div class="open 672069643d2232363222 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;7&gt;</span><span class="full" data-originalid="262" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>(32 l/min @ 100 bar), c/w<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/7&gt;</span><span class="full" data-originalid="262" data-length="-1">&lt;/cf&gt;</span></div><div class="single 736f667452657475726e2f newline internal-tag ownttip"><span class="short" title="&lt;8/&gt;: Newline">&lt;8/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div><div class="single 7461622074733d22303922206c656e6774683d2231222f tab internal-tag ownttip"><span class="short" title="&lt;9/&gt;: 1 tab character">&lt;9/&gt;</span><span class="full" data-originalid="tab" data-length="1">→</span></div><div class="open 672069643d2232363322 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;10&gt;</span><span class="full" data-originalid="263" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>Handlance,<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/10&gt;</span><span class="full" data-originalid="263" data-length="-1">&lt;/cf&gt;</span></div> <div class="open 672069643d2232363422 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;11&gt;</span><span class="full" data-originalid="264" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>reel,<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/11&gt;</span><span class="full" data-originalid="264" data-length="-1">&lt;/cf&gt;</span></div> <div class="open 672069643d2232363522 internal-tag ownttip"><span class="short" title="&lt;cf nfa=&quot;true&quot;&gt;">&lt;12&gt;</span><span class="full" data-originalid="265" data-length="-1">&lt;cf nfa=&quot;true&quot;&gt;</span></div>15m hose.<div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/12&gt;</span><span class="full" data-originalid="265" data-length="-1">&lt;/cf&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span class="short" title="&lt;/cf&gt;">&lt;/3&gt;</span><span class="full" data-originalid="260" data-length="-1">&lt;/cf&gt;</span></div>';
        $this->createInternalTagDataTest($segmentId, $markup, $compareState);
    }
    
    public function testTagComparision1(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // simple: if input = output we must get no errors
        $this->createInternalTagComparisionTest($original, $original, []);
    }
    
    public function testTagComparision2(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <2>ipsum</2> dolor sit amet, <1>consetetur sadipscing<5/></1> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // changed structure should not change validity
        $this->createInternalTagComparisionTest($original, $edited, []);
    }
    
    public function testTagComparision3(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing</2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // missing tag
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tags_missing']);
    }
    
    public function testTagComparision4(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et dolore magna aliquyam erat</3>, sed diam voluptua.<7/>';
        // missing tag
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tags_missing']);
    }
    
    public function testTagComparision5(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, </2>consetetur sadipscing<5/><2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // wrong order open/close
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision6(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</3> aliquyam erat</4>, sed diam voluptua.<7/>';
        // overlapping tags
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision7(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam <7/>nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>';
        // additional tag
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tags_added']);
    }
    
    public function testTagComparision8(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<7/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        // additional & missing tag
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tags_missing','internal_tags_added']);
    }
    
    public function testTagComparision9(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum<6/> dolor sit amet, <2>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor </1>invidunt ut<3> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        // changed structure
        $this->createInternalTagComparisionTest($original, $edited, []);
    }
    
    public function testTagComparision10(){
        $original = 'Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum<6/> dolor sit amet, <4>consetetur sadipscing</2><5/> elitr, sed diam nonumy eirmod tempor </1>invidunt ut<3> labore et <2>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        // faulty structure
        $this->createInternalTagComparisionTest($original, $edited, ['internal_tag_structure_faulty']);
    }
    
    public function testTagComparision11(){
        $original = '<7/>Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.';
        $edited = 'Lorem <1>ipsum<2> dolor sit amet, <3>consetetur sadipscing<4> elitr, sed diam nonumy eirmod tempor </4>invidunt ut<6/><5/> labore et </3>dolore magna</2> aliquyam erat</1>, sed diam voluptua.<7/>';
        // changed structure
        $this->createInternalTagComparisionTest($original, $edited, []);
    }
    
    /**
     *
     * @return editor_Segment_FieldTags
     */
    private function createTags() : editor_Segment_FieldTags {
        $segmentId = 1234567;
        $segmentText = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.'; // 80 characters
        return new editor_Segment_FieldTags($segmentId, 'target', $segmentText, 'target', 'targetEdit');
    }
    /**
     *
     * @param editor_Segment_FieldTags $tags
     * @param string $expectedMarkup
     */
    private function createTagsTest(editor_Segment_FieldTags $tags, string $expectedMarkup){
        // compare rendered Markup
        $this->assertEquals($expectedMarkup, $tags->render());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
        // unparse test
        $unparseTags = new editor_Segment_FieldTags($tags->getSegmentId(), $tags->getField(), $tags->getFieldText(), $tags->getSaveToFields(), $tags->getTermtaggerName());
        $unparseTags->unparse($expectedMarkup);
        $this->assertEquals($expectedMarkup, $unparseTags->render());
    }
    /**
     *
     * @param int $segmentId
     * @param string $markup
     */
    private function createDataTest($segmentId, $markup){
        $tags = new editor_Segment_FieldTags($segmentId, 'target', $markup, 'target', 'target');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-texts vs stripped markup
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        // error_log($expectedJSON);
        // error_log("\n==================================\n");
        $jsonTags = editor_Segment_FieldTags::fromJson($expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }
    /**
     *
     * @param int $segmentId
     * @param string $original
     * @param string $markup
     */
    private function createOriginalDataTest($segmentId, $original, $markup){
        $originalTags = new editor_Segment_FieldTags($segmentId, 'target', $original, 'target', 'target');
        $tags = new editor_Segment_FieldTags($segmentId, 'target', $markup, 'target', 'target');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-text original vs "sorted" markup
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        // compare field-text vs stripped markup
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
    }
    /**
     *
     * @param int $segmentId
     * @param string $original
     * @param string $markup
     */
    private function createInternalTagDataTest($segmentId, $markup, $compareState){
        $tags = new editor_Segment_FieldTags($segmentId, 'target', $markup, 'target', 'target');
        // compare unparsed markup
        $this->assertEquals($markup, $tags->render());
        // compare field-texts vs stripped markup
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
        // re-create from JSON
        $expectedJSON = $tags->toJson();
        $jsonTags = editor_Segment_FieldTags::fromJson($expectedJSON);
        $this->assertEquals($expectedJSON, $jsonTags->toJson());
        // tag comparision is expected to have no errors
        $tagComparision = new editor_Segment_Internal_TagComparision($tags, $jsonTags);
        $this->assertEquals([], $tagComparision->getStati());
    }
    /**
     * Creates a test for the internal tag comparision. The passed markup will have the following markup replaced with internal tags
     * Lorem <1>ipsum</1> dolor sit amet, <2>consetetur sadipscing<5/></2> elitr, sed diam nonumy eirmod tempor <3>invidunt ut<6/> labore et <4>dolore magna</4> aliquyam erat</3>, sed diam voluptua.<7/>
     * @param string $original
     * @param string $edited
     * @param array|string $expectedState
     */
    private function createInternalTagComparisionTest($original, $edited, $expectedState){
        if(!is_array($expectedState)){
            $expectedState = array($expectedState);
        }
        $originalTags = new editor_Segment_FieldTags(123456, 'target', $this->replaceInternalComparisionTags($original), 'target', 'target');
        $editedTags = new editor_Segment_FieldTags(123456, 'target', $this->replaceInternalComparisionTags($edited), 'target', 'target');
        $tagComparision = new editor_Segment_Internal_TagComparision($editedTags, $originalTags);
        $this->assertEquals($expectedState, $tagComparision->getStati());
    }
    /**
     * Replaces short tags with real internal tags
     * @param string $markup
     * @return string
     */
    private function replaceInternalComparisionTags($markup){
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
}