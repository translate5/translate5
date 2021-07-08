<?php

/*
 * START LICENSE AND COPYRIGHT
 *
 * This file is part of translate5
 *
 * Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics; All rights reserved.
 *
 * Contact: http://www.MittagQI.com/ / service (ATT) MittagQI.com
 *
 * This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 * as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 * included in the packaging of this file. Please review the following information
 * to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 * http://www.gnu.org/licenses/agpl.html
 *
 * There is a plugin exception available for use with this release of translate5 for
 * translate5: Please see http://www.translate5.net/plugin-exception.txt or
 * plugin-exception.txt in the root folder of translate5.
 *
 * @copyright Marc Mittag, MittagQI - Quality Informatics
 * @author MittagQI - Quality Informatics
 * @license GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 * http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
 *
 * END LICENSE AND COPYRIGHT
 */

/**
 * In order to test if we query the NEC-TM-Api as intended, we:
 * - login into NEC-TM,
 * - create a test-tag in NEC-TM (= "category" in translate5),
 * - import a TMX-file for this tag into NEC-TM,
 * - run a search and a concordance search,
 * - update a TMU,
 * - deleted all our test-data and logout again.
 *
 * Testing what users can do with LanguageResources in addition
 * (assign them to tasks, ...) is NOT part of this test.
 */
class NecTmLanguageResourceApiTest extends \ZfExtended_Test_ApiTestcase {
    
    /**
     * Name of the testfile to import for the LanguageReource (see testfiles).
     * @var string
     */
    const IMPORT_FILENAME = '2018-09-website-rewrite-examples.tmx';
    
    /**
     * ServiceType according the Service's namespace.
     * @var string
     */
    const SERVICE_TYPE = 'editor_Plugins_NecTm';
    
    /**
     * According to addResourceForeachUrl() in editor_Services_ServiceAbstract.
     * @var string
     */
    const RESOURCE_ID = 'editor_Plugins_NecTm_1';
    
    /**
     * languageResourceParams
     * @var object
     */
    protected $languageResourceParams;

    /**
     *
     */
    public static function setUpBeforeClass(): void {
        self::markTestIncomplete("Still in progress!");
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); // last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertCustomer();
    }
    
    /**
     * Test a NEC-TM-LanguageResource:
     * - importfile: none
     * - languages: generic
     */
    public function testNecTmLanguageResourceWithoutImportfile() {
        $this->languageResourceParams = [];
        $this->languageResourceParams['name'] = 'NecTm_Test1';
        $this->languageResourceParams['sourceLang'] = 4; // "de"
        $this->languageResourceParams['targetLang'] = 5; // "en"
        $this->createLanguageResource(false);
    }
    
    /**
     * Test a NEC-TM-LanguageResource:
     * - importfile: yes
     * - languages: with locale
     */
    public function testNecTmLanguageResourceWithImportfile() {
        $this->languageResourceParams = [];
        $this->languageResourceParams['name'] = 'NecTm_Test2';
        $this->languageResourceParams['sourceLang'] = 361; // "de-DE"
        $this->languageResourceParams['targetLang'] = 252; // "en-US"
        $this->createLanguageResource(true);
    }

    /**
     *
     */
    private function createLanguageResource($doImportFile) {
        /*
                    [module] => editor
                    [controller] => languageresourceinstance
                    [action] => post
                    [format] => jsontext
                    [resourceId] => editor_Plugins_NecTm_1
                    [name] => Test
                    [sourceLang] => 4
                    [targetLang] => 5
                    [customerIds] => Array
                    customerUseAsDefaultIds => Array
                    customerWriteAsDefaultIds => Array
                    [tagfield-2178-inputEl] =>
                    [serviceType] => editor_Plugins_NecTm
                    [serviceName] => NEC-TM
                    [specificData] =>
                    [color] => 61bdaa
                    [categories] => [9]
                    [tmUpload] => Array
                        (
                            [name] =>
                            [type] =>
                            [tmp_name] =>
                            [error] => 4
                            [size] => 0
                        )
                    
                    [tmUpload] => Array
                        (
                            [name] => 2018-09-website-rewrite-examples.tmx
                            [type] => application/octet-stream
                            [tmp_name] => /tmp/php4bqlGc
                            [error] => 0
                            [size] => 2811
                        )
         */
        
        $fileName = ($doImportFile) ? static::IMPORT_FILENAME : null;
        $this->api()->addFile('tmUpload', $this->api()->getFile($fileName, null, false), "application/xml");
        
        // AT WORK; next TODO: categories
        
        $params = $this->languageResourceParams;
        $params['resourceId']  = static::RESOURCE_ID;
        $params['serviceType'] = static::SERVICE_TYPE;
        $languageResource = $this->api()->request('editor/languageresourceinstance', 'POST', $params);
         // TODO...
    }

    /**
     *
     */
    public static function tearDownAfterClass(): void {
        self::$api->login('testmanager');
    }
}