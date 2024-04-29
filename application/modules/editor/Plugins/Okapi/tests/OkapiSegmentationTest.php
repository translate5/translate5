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
use editor_Plugins_Okapi_Bconf_Segmentation_Srx as Srx;

/**
 * Test whether srx-based text segmentation works as expected
 */
class OkapiSegmentationTest extends editor_Test_UnitTest
{
    protected static string $sourceLangRfc = 'de';

    /**
     * @throws ZfExtended_Exception
     */
    public function testSegmentation()
    {
        // Get Srx-class instance
        $srx = Srx::createSystemTargetSrx();

        // Text to be splitted by segments
        $text = 'Herzlich Willkommen zum Verkaufstraining für die barrierefreie Nullschwelle in Verbindung mit dem Schüco AD UP. Nutzen Sie die Abschnitte rechts, um das Produkt kennenzulernen und sich optimal auf die Beratung Ihrer Partner und Kunden vorzubereiten. Am Ende eines Abschnitts werden Sie auf Quizfragen stoßen, die Ihnen helfen, Ihr Wissen zu überprüfen. Beantworten Sie 80% der Fragen richtig für einen erfolgreichen Abschluss. Die Offline-Präsentation wird von David O. McKay und B. H. Roberts durchgeführt. Los geht\'s!';

        // Get SRX-based splitting-rules
        $rules = $srx->getSegmentationRules(self::$sourceLangRfc);

        // Use SRX-based splitting
        $result = $srx->splitTextToSegments($text, $rules);

        // File name
        $expected = file_get_contents(__DIR__ . '/' . get_class($this) . '/segmented.json');

        // Json-encode actual result
        $actual = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Check for differences between the expected and the actual result
        $this->assertJsonStringEqualsJsonString($expected, $actual, "The actual segmentation result does not match expected one.");
    }
}
