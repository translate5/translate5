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

use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;

/**
 * Test whether srx-based text segmentation works as expected
 */
class Translate3408Test extends editor_Test_JsonTest {

    /**
     * @var string
     */
    protected static string $sourceLangRfc = 'de';

    /**
     * @var string
     */
    protected static string $targetLangRfc = 'en';

    /**
     * @var array|string[]
     */
    protected static array $requiredPlugins = [
        'editor_Plugins_InstantTranslate_Init'
    ];

    public function testSegmentation() {

        // Text to be splitted by segments
        $text = 'Herzlich Willkommen zum Verkaufstraining für die barrierefreie Nullschwelle in Verbindung mit dem Schüco AD UP. Nutzen Sie die Abschnitte rechts, um das Produkt kennenzulernen und sich optimal auf die Beratung Ihrer Partner und Kunden vorzubereiten. Am Ende eines Abschnitts werden Sie auf Quizfragen stoßen, die Ihnen helfen, Ihr Wissen zu überprüfen. Beantworten Sie 80% der Fragen richtig für einen erfolgreichen Abschluss. Die Offline-Präsentation wird von David O. McKay und B. H. Roberts durchgeführt. Los geht\'s!';

        // Make request with special mode-param
        $result = static::api()->getJson('editor/instanttranslateapi/translate', [
            'text' => $text,
            'source' => self::$sourceLangRfc,
            'target' => self::$targetLangRfc,
            'mode' => 'segmentation'
        ]);
        $this->assertNotEmpty($result,'No results found for the request');

        // File name
        $file = 'segmented.json';

        // Json-encode actual result
        $actual = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Recreate the file from the api response, if test is running in capture-mode
        if (static::api()->isCapturing()) {
            file_put_contents(static::api()->getFile($file, null, false), $actual);
        }

        // Get expected result
        $expected = json_encode(static::api()->getFileContent($file), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Check for differences between the expected and the actual result
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.");
    }
}