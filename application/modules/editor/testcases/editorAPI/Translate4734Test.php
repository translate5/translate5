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
     *
     * @var LanguageResource|null
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

    private function sanitizeXlsxContent(string $xml): string
    {
        return str_replace("\r\n", "\n", $xml);
    }

    private function sanitizeHtmlContent(string $html): string
    {
        return preg_replace('~Done in [0-9]\.[0-9]+ sec~', 'Done in x.xxx sec', $html);
    }
}
