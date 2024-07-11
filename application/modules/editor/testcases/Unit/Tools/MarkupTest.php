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

namespace MittagQI\Translate5\Test\Unit\Tools;

use MittagQI\ZfExtended\Tools\Markup;
use PHPUnit\Framework\TestCase;

class MarkupTest extends TestCase
{
    public function testEscapeMarkup(): void
    {
        $markup = 'Das <t5:mrk-import mid="mrk-0"/> <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1"><t5:mrk-import mid="mrk-1"/></g> ist toll!';
        self::assertEquals($markup, Markup::escape($markup));

        $markup = 'Das <t5:mrk-import mid="mrk-0"/> <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1">><<>></g> ist toll!';
        $expected = 'Das <t5:mrk-import mid="mrk-0"/> <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1">&gt;&lt;&lt;&gt;&gt;</g> ist toll!';
        self::assertEquals($expected, Markup::escape($markup));

        $markup = 'Das <t5:mrk-import mid="mrk-0"/> <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1"> x >= y && z <= 13 </g> ist toll!';
        $expected = 'Das <t5:mrk-import mid="mrk-0"/> <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1"> x &gt;= y &amp;&amp; z &lt;= 13 </g> ist toll!';
        self::assertEquals($expected, Markup::escape($markup));

        $markup = 'Das <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1"> x >= y && z <= 13 </g> ist <g ctype="cdata"><![CDATA[Unescaped: "\'&<>]]></g> </g>toll!';
        $expected = 'Das <g ctype="underlined" equiv-text="&lt;run1&gt;" id="1"> x &gt;= y &amp;&amp; z &lt;= 13 </g> ist <g ctype="cdata"><![CDATA[Unescaped: "\'&<>]]></g> </g>toll!';
        self::assertEquals($expected, Markup::escape($markup));
    }

    public function testEscapeText(): void
    {
        $text = 'Entities ¶ und Umlaute öäü¶';
        self::assertEquals($text, Markup::escape($text));

        $text = 'x >= y && z <= 13';
        $expected = 'x &gt;= y &amp;&amp; z &lt;= 13';
        self::assertEquals($expected, Markup::escape($text));

        $text = '"x >= y && z <= 13"';
        $expected = '&quot;x &gt;= y &amp;&amp; z &lt;= 13&quot;';
        self::assertEquals($expected, Markup::escape($text));
    }

    public function testPreEscapeAndImportMarkup(): void
    {
        $markup = 'Das <t5:mrk-import t5:mid    =">>>"/> <g ctype = "underlined" equiv-text=\'&lt;run1>\' id="\'>>\'"> und <> noch <g ctype  =   ">complete>" _no-exit=\'">>>"\'> </g><t5:mrk-import mid="/>/mrk-1>"/></g> ist <= 17 <t5:smth-else _t5:_kp =\'/>/>\'/> toll!</g>';
        // check pre-escaping (only attribute-values)
        $expected = 'Das <t5:mrk-import t5:mid="&gt;&gt;&gt;"/> <g ctype="underlined" equiv-text=\'&lt;run1&gt;\' id="\'&gt;&gt;\'"> und <> noch <g ctype="&gt;complete&gt;" _no-exit=\'"&gt;&gt;&gt;"\'> </g><t5:mrk-import mid="/&gt;/mrk-1&gt;"/></g> ist <= 17 <t5:smth-else _t5:_kp=\'/&gt;/&gt;\'/> toll!</g>';
        self::assertEquals($expected, Markup::preEscapeTagAttributes($markup));
        // check full escaping
        $expected = 'Das <t5:mrk-import t5:mid="&gt;&gt;&gt;"/> <g ctype="underlined" equiv-text=\'&lt;run1&gt;\' id="\'&gt;&gt;\'"> und &lt;&gt; noch <g ctype="&gt;complete&gt;" _no-exit=\'"&gt;&gt;&gt;"\'> </g><t5:mrk-import mid="/&gt;/mrk-1&gt;"/></g> ist &lt;= 17 <t5:smth-else _t5:_kp=\'/&gt;/&gt;\'/> toll!</g>';
        self::assertEquals($expected, Markup::escapeStrict($markup));

        $markup = 'Das <g:u u:ctype="underlined" equiv-text="&lt;run1>" id="1"> x >= y && z <= 13 </g:u> ist <g ctype="cdata"><![CDATA[Unescaped: "\'&<>]]></g> </g>toll!';
        $expected = 'Das <g:u u:ctype="underlined" equiv-text="&lt;run1&gt;" id="1"> x &gt;= y &amp;&amp; z &lt;= 13 </g:u> ist <g ctype="cdata"><![CDATA[Unescaped: "\'&<>]]></g> </g>toll!';
        // check full escaping
        self::assertEquals($expected, Markup::escapeStrict($markup));

        $markup = 'A rather complicated tag with CDATA is a >=b "?" <g:u u:ctype="underlined" equiv-text="&lt;run1>" id="a >=b \'?\'"><![CDATA[Tag needs to be<atag att="a>b>c">tagcontent</atag>unescaped]]></g> smth.';
        $expected = 'A rather complicated tag with CDATA is a &gt;=b &quot;?&quot; <g:u u:ctype="underlined" equiv-text="&lt;run1&gt;" id="a &gt;=b \'?\'"><![CDATA[Tag needs to be<atag att="a>b>c">tagcontent</atag>unescaped]]></g> smth.';
        // check full escaping
        self::assertEquals($expected, Markup::escapeStrict($markup));
    }
}
