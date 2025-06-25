<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * Testcase for TRANSLATE-4734
 *
 * For details see the issue.
 */
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;
use MittagQI\Translate5\Test\JsonTestAbstract;

class Translate4734Test extends JsonTestAbstract
{
    /**
     * Imported TermCollection
     */
    protected static ?LanguageResource $tc = null;

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception|ZfExtended_Exception
     */
    protected static function setupImport(Config $config): void
    {
        static::$tc = $config->addLanguageResource(
            'termcollection',
            'testfiles/imported.tbx',
            static::getTestCustomerId()
        )->setProperty('name', self::class);
    }

    /**
     * Test two-step xlsx export for a TermCollection
     *
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testXlsxExport()
    {
        // Prepare xlsx file and print preparation progress as html
        $html = static::api()->get('/editor/languageresourceinstance/xlsxexport', [
            'collectionId' => static::$tc->getId(),
        ]);

        // Compare progress to expected
        $this->assertFileContents(
            $file = 'progress.html',
            $this->sanitizeHtmlContent($html->getBody()),
            "The content of progress output does not match the content of $file",
            static::api()->isCapturing()
        );

        // Download raw contents of prepared xlsx file
        $xlsx = static::api()->get('/editor/languageresourceinstance/xlsxexport', [
            'collectionId' => static::$tc->getId(),
        ]);

        // Write downloaded contents to temporary xlsx file
        $xlsxFile = APPLICATION_DATA . '/api-test-TermCollection.xlsx';
        file_put_contents($xlsxFile, $xlsx->getRawBody());

        // Compare to expected
        $this->assertFileContents(
            $file = 'expected.xlsx--xl-worksheets-sheet1.xml',
            $this->sanitizeXlsxContent(static::api()->getFileContentFromZipPath($xlsxFile, 'xl/worksheets/sheet1.xml')),
            "The data inside exported XLSX-file does not match the content of $file",
            static::api()->isCapturing()
        );

        // Delete tmp file
        unlink($xlsxFile);
    }

    /**
     * Test two-step xlsx export for a TermCollection
     *
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testSearchtermexists()
    {
        // Prepare xlsx file and print preparation progress as html
        $json = static::api()->getJson('/editor/termcollection/searchtermexists', [
            'searchTerms' => json_encode([
                [
                    "text" => "term1-de-admitted",
                    "anyOtherProp1" => "Some other value 1",
                    "anyOtherProp2" => "Some other value 2",
                ],
                [
                    "text" => "term1-de-standardized",
                    "anyOtherProp1" => "Some other value 3",
                    "anyOtherProp2" => "Some other value 4",
                ],
                [
                    "text" => " term1-de-nonexisting",
                    "anyOtherProp1" => "Some other value 5",
                    "anyOtherProp2" => "Some other value 6",
                ],
            ]),
            'targetLang' => 'de',
        ]);

        // Compare to expected
        $this->assertFileContents(
            $file = 'only-non-existing-terms.json',
            json_encode($json),
            "Non-existing terms returned - are different from the expected ones in $file",
            static::api()->isCapturing()
        );
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testListAndDetails()
    {
        // Get info about our TermCollection
        $specific = static::api()->getJson('/editor/termcollection/' . self::$tc->getId());

        // Unset props that prevent compare
        unset($specific->id, $specific->langResUuid, $specific->timestamp);

        // Compare to expected
        $this->assertFileContents(
            $file = 'termcollection-details.json',
            json_encode($specific, JSON_PRETTY_PRINT),
            "TermCollection details are not as expected in $file",
            static::api()->isCapturing()
        );

        // Get TermCollections list
        $json = static::api()->getJson('/editor/termcollection');

        // Find our TermCollection in the list
        $found = false;
        foreach ($json as $item) {
            // Unset props that prevent compare
            unset($item->id, $item->langResUuid, $item->timestamp);

            // If found - exit loop
            if (json_encode($item) === json_encode($specific)) {
                $found = true;

                break;
            }
        }

        // Check if found
        $this->assertEquals(true, $found, 'Specific expected TermCollection is not in the list');
    }

    private function sanitizeXlsxContent(string $xml): string
    {
        return str_replace("\r\n", "\n", $xml);
    }

    private function sanitizeHtmlContent(string $html): string
    {
        return preg_replace('~Done in [0-9]\.[0-9]+ sec~', 'Done in x.xxx sec', $html);
    }
}
