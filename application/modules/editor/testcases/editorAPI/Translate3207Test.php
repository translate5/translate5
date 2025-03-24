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
 * Testcase for TRANSLATE-3207
 *
 * For details see the issue.
 */
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;
use MittagQI\Translate5\Test\JsonTestAbstract;

class Translate3207Test extends JsonTestAbstract
{
    protected static ?LanguageResource $tc = null;

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected static function setupImport(Config $config): void
    {
        static::$tc = $config->addLanguageResource(
            'termcollection',
            'testfiles/tbx-with-images-dir.zip',
            static::getTestCustomerId()
        )->setProperty('name', 'tbx-with-images-dir');
    }

    public function test10_import()
    {
        // Do export as tbx
        $data = static::api()->get('/editor/languageresourceinstance/tbxexport', [
            'collectionId' => static::$tc->getId(),
            'tbxBasicOnly' => '0',
            'exportImages' => 'tbx',
        ]);

        // Compare exported to expected
        $this->assertFileContents(
            $file = 'exported-in-tbx.tbx',
            $this->sanitizeContent($data->getBody()),
            "The exported TBX does not match the content of $file",
            static::api()->isCapturing()
        );

        // Do export as zip
        $data = static::api()->get('/editor/languageresourceinstance/tbxexport', [
            'collectionId' => static::$tc->getId(),
            'tbxBasicOnly' => '0',
            'exportImages' => 'zip',
        ]);

        // Create zip file
        $zipFile = APPLICATION_DATA . '/' . ZfExtended_Test_ApiHelper::TEST_ZIP_FILENAME;
        file_put_contents($zipFile, $data->getRawBody());

        // Compare exported to expected
        $this->assertFileContents(
            $file = 'exported-in-zip.tbx',
            $this->sanitizeContent(static::api()->getFileContentFromZipPath($zipFile, 'exported.tbx')),
            "The exported ZIP's tbx-file does not match the content of $file",
            static::api()->isCapturing()
        );

        // Delete tmp file
        unlink($zipFile);
    }

    private function sanitizeContent(string $tbx): string
    {
        $tbx = preg_replace('/(<item type="email">)(.*?)(<\/item>)/', '$1noreply@translate5.net$3', $tbx);
        $tbx = preg_replace('/(<item type="role">)(.*?)(<\/item>)/', '$1ROLES NOT TESTABLE$3', $tbx);
        return preg_replace('#(<p>[^<]+ at) http[^>]+ (by [^<]+</p>)#', '$1 HTTP_URL $2', $tbx) ?? '';
    }
}
