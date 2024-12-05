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

use MittagQI\Translate5\Test\UnitTestAbstract;

/**
 * Test if the alt trans element is correctly parsed when self-closing tag exists in all trans
 */
class SelfClosingTargetInAllTransTest extends UnitTestAbstract
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider xmlProvider
     */
    public function test(string $xml): void
    {
        $parser = new editor_Models_Import_FileParser_XmlParser();

        $parser->registerElement('trans-unit alt-trans target', null, function ($tag, $key, $opener) use ($parser) {
            $isSingle = $opener['isSingle'];

            $altTransTarget = implode(
                '',
                $parser->getChunks(
                    $isSingle ? $key : ($opener['openerKey'] + 1), // we don't need to opening tag
                    $isSingle ? 1 : ($key - $opener['openerKey'] - 1) // we don't need the closing tag
                )
            );

            self::assertSame('<target/>', $altTransTarget);
        });

        $parser->parse($xml);
    }

    public function xmlProvider(): array
    {
        return [
            ['<trans-unit id="706QOiwlZfI1RS5D1_dc8:0"><source>“{1}” on page {2}</source>
<target>„{1}“ auf Seite {2}</target><alt-trans match-quality="0.0" origin="machine-trans"><target/></alt-trans></trans-unit>'],
        ];
    }
}
