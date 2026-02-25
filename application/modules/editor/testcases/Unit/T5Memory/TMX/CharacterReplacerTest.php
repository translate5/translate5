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

namespace MittagQI\Translate5\Test\Unit\T5Memory\TMX;

use MittagQI\Translate5\T5Memory\TMX\CharacterReplacer;
use PHPUnit\Framework\TestCase;

class CharacterReplacerTest extends TestCase
{
    /**
     * @dataProvider replaceInvalidXmlCharactersCases
     */
    public function testReplaceInvalidXmlCharacters(string $text, string $expected): void
    {
        $replacer = CharacterReplacer::create();

        self::assertSame($expected, $replacer->replaceInvalidXmlCharacters($text));
    }

    /**
     * @dataProvider revertInvalidXmlCharactersCases
     */
    public function testRevertToInvalidXmlCharacters(string $text, string $expected): void
    {
        $replacer = CharacterReplacer::create();

        // For control characters, using assertSameWithDebug provides better debugging output
        // But assertSame also works fine since \x00 === \x00 even if invisible
        self::assertSame($expected, $replacer->revertToInvalidXmlCharacters($text));
    }

    public function replaceInvalidXmlCharactersCases(): iterable
    {
        // Valid text - should remain unchanged
        yield 'Valid text with no invalid symbols' => [
            "Valid text with no invalid symbols.",
            "Valid text with no invalid symbols.",
        ];

        // C0 Control Characters (0x00-0x1F) - using raw/literal control bytes
        yield 'Raw NUL byte (\\x00)' => [
            "Text with \x00 NUL here.",
            'Text with <t5:n id="1001" r="utf-char" n="00"/> NUL here.',
        ];

        yield 'Unicode escape for SOH (\\u0001)' => [
            'Text with \\u0001 SOH here.',
            'Text with <t5:n id="1001" r="utf-char" n="01"/> SOH here.',
        ];

        yield 'Unicode escape for ETX (\\u0003)' => [
            'Text with \\u0003 ETX here.',
            'Text with <t5:n id="1001" r="utf-char" n="03"/> ETX here.',
        ];

        yield 'Unicode escape for VT (\\u000B)' => [
            'Another invalid symbol \\u000B in this text.',
            'Another invalid symbol <t5:n id="1001" r="utf-char" n="0b"/> in this text.',
        ];

        yield 'Unicode escape for FF (\\u000C)' => [
            'Text with \\u000C form feed.',
            'Text with <t5:n id="1001" r="utf-char" n="0c"/> form feed.',
        ];

        yield 'Unicode escape for ESC (\\u001B)' => [
            'Text with \\u001B escape.',
            'Text with <t5:n id="1001" r="utf-char" n="1b"/> escape.',
        ];

        yield 'Unicode escape for US (\\u001F)' => [
            'Text with \\u001F unit separator.',
            'Text with <t5:n id="1001" r="utf-char" n="1f"/> unit separator.',
        ];

        // Character references
        yield 'Character reference for ETX (&#x03;)' => [
            "Text with &#x03; ETX.",
            'Text with <t5:n id="1001" r="utf-char" n="03"/> ETX.',
        ];

        yield 'Character reference for VT (&#x0B;)' => [
            "Text with &#x0B; vertical tab.",
            'Text with <t5:n id="1001" r="utf-char" n="0b"/> vertical tab.',
        ];

        yield 'Character reference for NUL (&#x00;)' => [
            "Text with &#x00; NUL.",
            'Text with <t5:n id="1001" r="utf-char" n="00"/> NUL.',
        ];

        yield 'Character reference for DEL (&#x7F;)' => [
            "Text with &#x7F; DEL.",
            'Text with <t5:n id="1001" r="utf-char" n="7f"/> DEL.',
        ];

        // Unicode space characters via character references
        yield 'Character reference for NBSP (&#xA0;)' => [
            "Text with &#xA0; NBSP.",
            'Text with &#xA0; NBSP.',
        ];

        yield 'Character reference for ZWSP (&#x200B;)' => [
            "Text with &#x200B; ZWSP.",
            'Text with &#x200B; ZWSP.',
        ];

        // Valid XML characters should be preserved
        yield 'Text with TAB (0x09)' => [
            "Text\twith\ttabs.",
            "Text\twith\ttabs.",
        ];

        yield 'Text with LF (0x0A)' => [
            "Text\nwith\nnewlines.",
            "Text\nwith\nnewlines.",
        ];

        yield 'Text with CR (0x0D)' => [
            "Text\rwith\rcarriage\rreturns.",
            "Text\rwith\rcarriage\rreturns.",
        ];

        yield 'Unicode escape for TAB (\\u0009) - valid' => [
            'Text with \\u0009 TAB.',
            'Text with \\u0009 TAB.',
        ];

        yield 'Unicode for TAB - valid' => [
            'Text with	 TAB.',
            'Text with	 TAB.',
        ];

        yield 'Unicode escape for LF (\\u000A) - valid' => [
            'Text with \\u000A LF.',
            'Text with \\u000A LF.',
        ];

        yield 'Unicode escape for valid char (\\u00D6 = Ö) should be preserved' => [
            'Text with \\u00D6 character.',
            'Text with \\u00D6 character.',
        ];

        yield 'Character reference for valid char (&#xD6; = Ö)' => [
            "Text with &#xD6; character.",
            "Text with &#xD6; character.",
        ];

        // Multiple invalid characters
        yield 'Multiple Unicode escape sequences' => [
            'Text \\u0001 with \\u0003 multiple \\u000B.',
            'Text <t5:n id="1001" r="utf-char" n="01"/> with <t5:n id="1002" r="utf-char" n="03"/> multiple <t5:n id="1003" r="utf-char" n="0b"/>.',
        ];

        yield 'Mixed Unicode escapes and character references' => [
            'Text \\u0003 and &#x0B; mixed.',
            'Text <t5:n id="1001" r="utf-char" n="03"/> and <t5:n id="1002" r="utf-char" n="0b"/> mixed.',
        ];

        // Real-world examples
        yield 'Real-world with Unicode escape sequence' => [
            'Zusammenfassung: \\u0003- Text',
            'Zusammenfassung: <t5:n id="1001" r="utf-char" n="03"/>- Text',
        ];

        yield 'Real-world with character reference' => [
            "Zusammenfassung: &#x03;- Öffnen Sie den Feed.",
            'Zusammenfassung: <t5:n id="1001" r="utf-char" n="03"/>- Öffnen Sie den Feed.',
        ];

        yield 'Mixed: valid UTF-8 should be preserved' => [
            "Text with Ö and &#x0B; mixed.",
            'Text with Ö and <t5:n id="1001" r="utf-char" n="0b"/> mixed.',
        ];

        yield 'Invalid Unit Separator (0x1F) ' => [
            'Text with Unit Separator (&#x1F;)',
            'Text with Unit Separator (<t5:n id="1001" r="utf-char" n="1f"/>)',
        ];
    }

    public function revertInvalidXmlCharactersCases(): iterable
    {
        // Valid text - should remain unchanged
        yield 'Revert valid text' => [
            "Valid text with no invalid symbols.",
            "Valid text with no invalid symbols.",
        ];

        // Revert t5:n tags with utf-char type
        yield 'Revert t5:n utf-char NUL (00)' => [
            'Text with <t5:n id="1001" r="utf-char" n="00"/> NUL here.',
            "Text with \x00 NUL here.",
        ];

        yield 'Revert t5:n utf-char SOH (01)' => [
            'Text with <t5:n id="1001" r="utf-char" n="01"/> SOH here.',
            "Text with \x01 SOH here.",
        ];

        yield 'Revert t5:n utf-char FF (0c)' => [
            'Text with <t5:n id="1001" r="utf-char" n="0c"/> form feed.',
            "Text with \x0C form feed.",
        ];

        // Revert t5:n tags with utf-char type
        yield 'Revert t5:n utf-char ETX (03)' => [
            'Text with <t5:n id="1001" r="utf-char" n="03"/> ETX.',
            "Text with \x03 ETX.",
        ];

        yield 'Revert t5:n utf-char VT (0b)' => [
            'Text with <t5:n id="1001" r="utf-char" n="0b"/> vertical tab.',
            "Text with \x0B vertical tab.",
        ];

        yield 'Revert t5:n utf-char DEL (7f)' => [
            'Text with <t5:n id="1001" r="utf-char" n="7f"/> DEL.',
            "Text with \x7F DEL.",
        ];

        // Valid XML characters - should be preserved
        yield 'Revert: valid TAB should be preserved' => [
            "Text\twith\ttabs.",
            "Text\twith\ttabs.",
        ];

        yield 'Revert: valid LF should be preserved' => [
            "Text\nwith\nnewlines.",
            "Text\nwith\nnewlines.",
        ];

        yield 'Revert: valid CR should be preserved' => [
            "Text\rwith\rcarriage\rreturns.",
            "Text\rwith\rcarriage\rreturns.",
        ];

        yield 'Revert: valid UTF-8 should be preserved' => [
            "Text with Ö character.",
            "Text with Ö character.",
        ];

        // Multiple t5:n tags
        yield 'Revert multiple t5:n utf-char tags' => [
            'Text <t5:n id="1001" r="utf-char" n="01"/> with <t5:n id="1001" r="utf-char" n="03"/> multiple.',
            "Text \x01 with \x03 multiple.",
        ];

        yield 'Revert mixed utf-char and utf-char tags' => [
            'Text <t5:n id="1001" r="utf-char" n="03"/> and <t5:n id="1001" r="utf-char" n="0b"/> mixed.',
            "Text \x03 and \x0B mixed.",
        ];

        // Real-world examples
        yield 'Revert real-world with t5:n tag' => [
            'Zusammenfassung: <t5:n id="1001" r="utf-char" n="03"/>- Text',
            "Zusammenfassung: \x03- Text",
        ];

        yield 'Revert real-world with UTF-8' => [
            'Zusammenfassung: <t5:n id="1001" r="utf-char" n="03"/>- Öffnen Sie den Feed.',
            "Zusammenfassung: \x03- Öffnen Sie den Feed.",
        ];

        yield 'Revert mixed with valid UTF-8' => [
            'Text with Ö and <t5:n id="1001" r="utf-char" n="0b"/> mixed.',
            "Text with Ö and \x0B mixed.",
        ];
    }
}
