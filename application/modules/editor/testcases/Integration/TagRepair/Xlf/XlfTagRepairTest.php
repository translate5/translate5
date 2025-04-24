<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Test\Integration\TagRepair\Xlf;

use MittagQI\Translate5\Segment\TagRepair\Xliff\XliffTagRepairer;
use PHPUnit\Framework\TestCase;

class XlfTagRepairTest extends TestCase
{
    private XliffTagRepairer $xliffTagRepairer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xliffTagRepairer = new XliffTagRepairer();
    }

    /**
     * @dataProvider serviceResults
     */
    public function testRepair(string $query, string $result, string $expected): void
    {
        $actual = $this->xliffTagRepairer->repairTranslation($query, $result);
        self::assertEquals($expected, $actual);
    }

    public function serviceResults(): iterable
    {
        // missing closing tag. Will be added after the opening tag
        yield [
            'query' => 'Diese Funktion ist derzeit <bx id="1" rid="1"/>nicht<ex id="2" rid="1"/> verfügbar.',
            'result' => 'This function is currently <bx id="1" rid="1"/>not available.',
            'expected' => 'This function is currently <bx id="1" rid="1"/><ex id="2" rid="1"/>not available.',
        ];

        // missing opening tag. Will be added before the closing tag
        yield [
            'query' => 'Bitte <bx id="1" rid="1"/>klicken Sie hier<ex id="2" rid="1"/>, um fortzufahren.',
            'result' => 'Please click here<ex id="2" rid="1"/>, to continue.',
            'expected' => 'Please click here<bx id="1" rid="1"/><ex id="2" rid="1"/>, to continue.',
        ];

        // missing paired tags added at the beginning of the sentence
        yield [
            'query' => 'Die <bx id="1" rid="1"/>Änderungen<ex id="2" rid="1"/> wurden <bx id="3" rid="2"/>gespeichert<ex id="4" rid="2"/> und <x id="5"/> archiviert.',
            'result' => 'The changes have been <bx id="3" rid="2"/>saved and <x id="5"/> archived.',
            'expected' => '<bx id="1" rid="1"/><ex id="2" rid="1"/>The changes have been <bx id="3" rid="2"/><ex id="4" rid="2"/>saved and <x id="5"/> archived.',
        ];

        yield [
            'query' => 'Dieses Produkt ist <bx id="1" rid="1"/>nicht<ex id="2" rid="1"/> verfügbar.',
            'result' => 'This product is <ex id="1" rid="1"/>not<bx id="2" rid="1"/> available.',
            'expected' => 'This product is <bx id="1" rid="1"/>not<ex id="2" rid="1"/> available.',
        ];

        yield [
            'query' => '<bx id="1" rid="1"/>Wichtige Information: <bx id="2" rid="2"/>Bitte lesen<ex id="3" rid="2"/><ex id="4" rid="1"/>',
            'result' => '<bx id="1" rid="1"/>Important information: Please read',
            'expected' => '<bx id="2" rid="2"/><ex id="3" rid="2"/><bx id="1" rid="1"/><ex id="4" rid="1"/>Important information: Please read',
        ];

        yield [
            'query' => 'Die <x id="1"/> Dokumente <bx id="2" rid="1"/>müssen<ex id="3" rid="1"/> unterschrieben werden.',
            'result' => 'The documents must be signed.',
            'expected' => '<x id="1"/><bx id="2" rid="1"/><ex id="3" rid="1"/>The documents must be signed.',
        ];
    }
}
