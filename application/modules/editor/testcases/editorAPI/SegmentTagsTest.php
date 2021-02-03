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
        $this->assertEquals($expected, $result);
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
        $this->assertEquals($expected, $result);
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
    public function testTagWithAttributes(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42"><span>Link Text</span> <img class="link-img" src="/some/icon.svg"/></a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($expected, $result);
    }
    /**
     *
     */
    public function testTagWithUnescapedChars(){
        $expected = '<a href="http://www.google.de" target="blank" data-test="42">"Somethig" is &lt "Something" else</a>';
        $tag = editor_Tag::unparse($expected);
        $result = $tag->render();
        $this->assertEquals($expected, $result);
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
     */
    public function testRealDataTags1(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '[<div class="open 672069643d22393222 internal-tag ownttip"><span title="&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;</span></div>edit<div class="close 2f67 internal-tag ownttip"><span title="&lt;/a&gt;" class="short">&lt;/1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;/a&gt;</span></div>] Last updated:';
        $markup = '[<div class="672069643d22393222 internal-tag open ownttip"><span class="short" title="&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="92" data-length="-1">&lt;a href=&quot;https://edit.php.net/?project=PHP&amp;perm=en/install.unix.apache2.php&quot;&gt;</span></div>edit<div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/1&gt;</span><span class="full" data-originalid="92" data-length="-1">&lt;/a&gt;</span></div>] Last updated:';
        $originalTags = new editor_Segment_FieldTags($segmentId, $original, 'target', 'target');
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        
    }
    /**
     *
     */
    public function testRealDataTags2(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677836;
        $original = 'cd httpd-2_x_NN<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>./configure --enable-so<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;2/&gt;: Newline" class="short">&lt;2/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>make<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;3/&gt;: Newline" class="short">&lt;3/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>make install';
        $markup = 'cd httpd-2_x_NN<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>./configure --enable-so<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;2/&gt;: Newline">&lt;2/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>make<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;3/&gt;: Newline">&lt;3/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>make install';
        $originalTags = new editor_Segment_FieldTags($segmentId, $original, 'target', 'target');
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
    }
    /**
     *
     */
    public function testRealDataTags3(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '&lt;FilesMatch \.php$&gt;<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div> <div class="single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip"><span title="&lt;2/&gt;: 3 whitespace characters" class="short">&lt;2/&gt;</span><span data-originalid="space" data-length="3" class="full">···</span></div>SetHandler application/x-httpd-php<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;3/&gt;: Newline" class="short">&lt;3/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">↵</span></div>&lt;/FilesMatch&gt;';
        $markup = '&lt;FilesMatch \.php$&gt;<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div> <div class="73706163652074733d2232303230323022206c656e6774683d2233222f internal-tag ownttip single space"><span class="short" title="&lt;2/&gt;: 3 whitespace characters">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="3">···</span></div>SetHandler application/x-httpd-php<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;3/&gt;: Newline">&lt;3/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">↵</span></div>&lt;/FilesMatch&gt;';
        $originalTags = new editor_Segment_FieldTags($segmentId, $original, 'target', 'target');
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
    }
    /**
     *
     */
    public function testRealDataTags4(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '<div class="open 672069643d22383022 internal-tag ownttip"><span title="&lt;span class=&quot;next&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="80" data-length="-1" class="full">&lt;span class=&quot;next&quot;&gt;</span></div><div class="open 672069643d22383122 internal-tag ownttip"><span title="&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;" class="short">&lt;2&gt;</span><span data-originalid="81" data-length="-1" class="full">&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;</span></div>Lighttpd 1.4 on Unix systems<div class="single 782069643d22383422207869643d2231333031346134632d323432302d343638342d386466392d623037333034666634306330222f internal-tag ownttip"><span title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;" class="short">&lt;3/&gt;</span><span data-originalid="84" data-length="-1" class="full">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/a&gt;" class="short">&lt;/2&gt;</span><span data-originalid="81" data-length="-1" class="full">&lt;/a&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/span&gt;" class="short">&lt;/1&gt;</span><span data-originalid="80" data-length="-1" class="full">&lt;/span&gt;</span></div> <div class="open 672069643d22383522 internal-tag ownttip"><span title="&lt;span class=&quot;prev&quot;&gt;" class="short">&lt;4&gt;</span><span data-originalid="85" data-length="-1" class="full">&lt;span class=&quot;prev&quot;&gt;</span></div><div class="open 672069643d22383622 internal-tag ownttip"><span title="&lt;a href=&quot;install.unix.apache.php&quot;&gt;" class="short">&lt;5&gt;</span><span data-originalid="86" data-length="-1" class="full">&lt;a href=&quot;install.unix.apache.php&quot;&gt;</span></div><div class="single 782069643d22383922207869643d2233613165616535382d613363332d346338642d613166342d643135333633343339666330222f internal-tag ownttip"><span title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;" class="short">&lt;6/&gt;</span><span data-originalid="89" data-length="-1" class="full">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div>Apache 1.3.x on Unix systems<div class="close 2f67 internal-tag ownttip"><span title="&lt;/a&gt;" class="short">&lt;/5&gt;</span><span data-originalid="86" data-length="-1" class="full">&lt;/a&gt;</span></div><div class="close 2f67 internal-tag ownttip"><span title="&lt;/span&gt;" class="short">&lt;/4&gt;</span><span data-originalid="85" data-length="-1" class="full">&lt;/span&gt;</span></div>';
        $markup = '<div class="672069643d22383022 internal-tag open ownttip"><span class="short" title="&lt;span class=&quot;next&quot;&gt;">&lt;1&gt;</span><span class="full" data-originalid="80" data-length="-1">&lt;span class=&quot;next&quot;&gt;</span></div><div class="672069643d22383122 internal-tag open ownttip"><span class="short" title="&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;">&lt;2&gt;</span><span class="full" data-originalid="81" data-length="-1">&lt;a href=&quot;install.unix.lighttpd-14.php&quot;&gt;</span></div>Lighttpd 1.4 on Unix systems<div class="782069643d22383422207869643d2231333031346134632d323432302d343638342d386466392d623037333034666634306330222f internal-tag ownttip single"><span class="short" title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;">&lt;3/&gt;</span><span class="full" data-originalid="84" data-length="-1">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-r.gif&quot; alt=&quot;&gt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div><div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/2&gt;</span><span class="full" data-originalid="81" data-length="-1">&lt;/a&gt;</span></div><div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/span&gt;">&lt;/1&gt;</span><span class="full" data-originalid="80" data-length="-1">&lt;/span&gt;</span></div> <div class="672069643d22383522 internal-tag open ownttip"><span class="short" title="&lt;span class=&quot;prev&quot;&gt;">&lt;4&gt;</span><span class="full" data-originalid="85" data-length="-1">&lt;span class=&quot;prev&quot;&gt;</span></div><div class="672069643d22383622 internal-tag open ownttip"><span class="short" title="&lt;a href=&quot;install.unix.apache.php&quot;&gt;">&lt;5&gt;</span><span class="full" data-originalid="86" data-length="-1">&lt;a href=&quot;install.unix.apache.php&quot;&gt;</span></div><div class="782069643d22383922207869643d2233613165616535382d613363332d346338642d613166342d643135333633343339666330222f internal-tag ownttip single"><span class="short" title="&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;">&lt;6/&gt;</span><span class="full" data-originalid="89" data-length="-1">&lt;img src=&quot;http://static.php.net/www.php.net/images/caret-l.gif&quot; alt=&quot;&lt;&quot; width=&quot;11&quot; height=&quot;7&quot; /&gt;</span></div>Apache 1.3.x on Unix systems<div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/a&gt;">&lt;/5&gt;</span><span class="full" data-originalid="86" data-length="-1">&lt;/a&gt;</span></div><div class="2f67 close internal-tag ownttip"><span class="short" title="&lt;/span&gt;">&lt;/4&gt;</span><span class="full" data-originalid="85" data-length="-1">&lt;/span&gt;</span></div>';
        $originalTags = new editor_Segment_FieldTags($segmentId, $original, 'target', 'target');
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
    }
    /**
     *
     */
    public function testRealDataTags5(){
        // testing "real" segment content. keep in mind when doing this, that rendered attributes in tags may have a different order so the input needs to be ordered when comparing rendered stuff
        $segmentId = 677867;
        $original = '&lt;FilesMatch "\.phps$"&gt;<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;1/&gt;: Newline" class="short">&lt;1/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">\xe2\x86\xb5</span></div> <div class="single 73706163652074733d2232303230323022206c656e6774683d2233222f space internal-tag ownttip"><span title="&lt;2/&gt;: 3 whitespace characters" class="short">&lt;2/&gt;</span><span data-originalid="space" data-length="3" class="full">\xc2\xb7\xc2\xb7\xc2\xb7</span></div>SetHandler application/x-httpd-php-source<div class="single 736f667452657475726e2f newline internal-tag ownttip"><span title="&lt;3/&gt;: Newline" class="short">&lt;3/&gt;</span><span data-originalid="softReturn" data-length="1" class="full">\xe2\x86\xb5</span></div>&lt;/FilesMatch&gt;';
        $markup = '&lt;FilesMatch "\.phps$"&gt;<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;1/&gt;: Newline">&lt;1/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">\xe2\x86\xb5</span></div> <div class="73706163652074733d2232303230323022206c656e6774683d2233222f internal-tag ownttip single space"><span class="short" title="&lt;2/&gt;: 3 whitespace characters">&lt;2/&gt;</span><span class="full" data-originalid="space" data-length="3">\xc2\xb7\xc2\xb7\xc2\xb7</span></div>SetHandler application/x-httpd-php-source<div class="736f667452657475726e2f internal-tag newline ownttip single"><span class="short" title="&lt;3/&gt;: Newline">&lt;3/&gt;</span><span class="full" data-originalid="softReturn" data-length="1">\xe2\x86\xb5</span></div>&lt;/FilesMatch&gt;';
        $originalTags = new editor_Segment_FieldTags($segmentId, $original, 'target', 'target');
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals($originalTags->getFieldText(), $tags->getFieldText());
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
    }
    /**
     *
     */
    public function testRealDataTags6(){
        // testing "real" segment content
        $segmentId = 677867;
        $markup = 'This file is a based on a part of the php-online-Documentation. It\'s translation is done by a pretranslation based on a very fast winalign-Project and is not at all state of the translation art. It\'s only purpose is the generation of demo-data for translate5.';
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals(strip_tags($markup), $tags->getFieldText());
    }
    /**
     *
     */
    public function testUnescapedChars(){
        // testing "real" segment content
        $segmentId = 677867;
        $markup = '<a href="http://www.google.de" target="blank" data-test="42">"Somethig" is &lt "Something" else</a>';
        $tags = new editor_Segment_FieldTags($segmentId, $markup, 'target', 'target');
        $this->assertEquals($markup, $tags->render());
        $this->assertEquals('"Somethig" is &lt "Something" else', $tags->getFieldText());
    }
    /**
     * 
     * @return editor_Segment_FieldTags
     */
    private function createTags() : editor_Segment_FieldTags {
        $segmentId = 1234567;
        $segmentText = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod.'; // 80 characters
        return new editor_Segment_FieldTags($segmentId, $segmentText, 'target', 'targetEdit');
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
        $unparseTags = new editor_Segment_FieldTags($tags->getSegmentId(), $tags->getFieldText(), $tags->getSaveToFields(), $tags->getTermtaggerName());
        $unparseTags->unparse($expectedMarkup);
        $this->assertEquals($expectedMarkup, $unparseTags->render());
    }
}