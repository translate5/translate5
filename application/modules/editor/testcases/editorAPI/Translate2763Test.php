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
 * Testcase for TRANSLATE-2763 - the problem was that the re-import of TBX was deleting all old terms,
 *  also the terms which are again in the re-imported TBX.
 *  This is tested by importing a TBX with Term A B C D - the export should contain all four terms
 *  In a second import a similar TBX is imported into the previous one, with terms A B C Y.
 *  Then the export should contain exactly this terms, not the term D but also the not changed terms A B C.
 *
 * For details see the issue.
 */
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\Import\LanguageResource;

class Translate2763Test extends editor_Test_JsonTest {

    protected static ?LanguageResource $tc = null;

    /**
     * @param Config $config
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    protected static function setupImport(Config $config): void
    {
        static::$tc = $config->addLanguageResource(
            'termcollection',
            'testfiles/term-import-1.tbx',
            static::getTestCustomerId()
        )->setProperty('name', 'TC test');
    }

    /**
     */
    public function test10_InitialTbxImport() {

        $data = static::api()->get('/editor/languageresourceinstance/tbxexport', [
            'collectionId' => static::$tc->getId(),
            'tbxBasicOnly' => '1',
            'exportImages' => '0',
        ]);

        $this->assertFileContents('term-export-1.tbx', $this->sanitizeURL($data->getBody()), 'The exported TBX does not match the content of term-export-1.tbx', static::api()->isCapturing());
    }

    /**
     * Merge in the TBX with one additional term, set deleteTermsOlderThanCurrentImport
     * @depends test10_InitialTbxImport
     */
    public function test20_MergeImport() {

        // Reimport tbx into existing term collection
        static::api()->reimportResource(static::$tc->getId(), 'testfiles/term-import-2.tbx', [
            'deleteTermsOlderThanCurrentImport' => 'on',
            'deleteProposalsLastTouchedOlderThan' => null,
        ]);

        sleep(10);

        // Do tbx-export
        $data = static::api()->get('/editor/languageresourceinstance/tbxexport', [
            'collectionId' => static::$tc->getId(),
            'tbxBasicOnly' => '1',
            'exportImages' => '0',
        ]);

        // Make sure tbx-exported content are as expected
        $this->assertFileContents('term-export-2.tbx', $this->sanitizeURL($data->getBody()), 'The exported TBX does not match the content of term-export-1.tbx', static::api()->isCapturing());
    }

    /**
     * @param string $tbx
     * @return string
     */
    private function sanitizeURL(string $tbx): string {
        return preg_replace('#(<p>[^<]+ at) http[^>]+ (by [^<]+</p>)#', '$1 HTTP_URL $2', $tbx) ?? '';
    }
}
