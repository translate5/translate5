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
class Translate2763Test extends editor_Test_JsonTest {

    protected static $TC_ID;

    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);

        self::$api->login('testmanager');
        self::assertLogin('testmanager');
        self::assertCustomer();
    }

    /**
     */
    public function test10_InitialTbxImport() {
        $this->api()->addFile('tmUpload', $this->api()->getFile('testfiles/term-import-1.tbx'), 'application/xml');
        $result = $this->api()->postJson('editor/languageresourceinstance', [
            //format jsontext???
            'color' => '19737d',
            'serviceName' => 'TermCollection',
            'serviceType' => 'editor_Services_TermCollection',
            'resourceId' => 'editor_Services_TermCollection',
            'customerIds[]' => $this->api()->getCustomer()->id,
            'name' => 'TC test',
        ]);

        self::$TC_ID = $result->id ?? null;

        $data = $this->api()->request('/editor/languageresourceinstance/tbxexport', 'GET', [
            'collectionId' => self::$TC_ID,
            'tbxBasicOnly' => '1',
            'exportImages' => '0',
        ]);

        $this->assertFileContents('term-export-1.tbx', $this->sanitizeURL($data->getBody()), 'The exported TBX does not match the content of term-export-1.tbx', $this->api()->isCapturing());
    }

    /**
     * Merge in the TBX with one additional term, set deleteTermsOlderThanCurrentImport
     * @depends test10_InitialTbxImport
     */
    public function test20_MergeImport() {
        $this->api()->addFile('tmUpload', $this->api()->getFile('testfiles/term-import-2.tbx'), 'application/xml');
        $this->api()->postJson('editor/languageresourceinstance/'.self::$TC_ID.'/import/', [
            'deleteTermsOlderThanCurrentImport' => 'on',
            'deleteProposalsLastTouchedOlderThan' => null,
        ]);

        $data = $this->api()->request('/editor/languageresourceinstance/tbxexport', 'GET', [
            'collectionId' => self::$TC_ID,
            'tbxBasicOnly' => '1',
            'exportImages' => '0',
        ]);

        $this->assertFileContents('term-export-2.tbx', $this->sanitizeURL($data->getBody()), 'The exported TBX does not match the content of term-export-1.tbx', $this->api()->isCapturing());

        $this->api()->delete('editor/languageresourceinstance/'.self::$TC_ID.'');
    }

    /**
     * @param string $tbx
     * @return string
     */
    private function sanitizeURL(string $tbx): string {
        return preg_replace('#(<p>[^<]+ at) http[^>]+ (by [^<]+</p>)#', '$1 HTTP_URL $2', $tbx) ?? '';
    }
}
