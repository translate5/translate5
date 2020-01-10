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
 * In order to test if we query the DeepL-Api as intended, we:
 * - create a DeepL-LanguageReource,
 * - run a search (= for matches) and a translation (= for InstantTranslation),
 * - deleted the LangaugeResource.
 *
 * Testing what users can do with LanguageResources in addition
 * (assign them to tasks, ...) is NOT part of this test.
 */
class NecTmLanguageResourceApiTest extends \ZfExtended_Test_ApiTestcase {
    
    /**
     * ServiceType according the Service's namespace.
     * @var string
     */
    const SERVICE_TYPE = 'editor_Plugins_DeepL';
    
    /**
     * According to addResourceForeachUrl() in editor_Services_ServiceAbstract.
     * @var string
     */
    const RESOURCE_ID = 'editor_Plugins_DeepL_1';

    /**
     * 
     */
    public static function setUpBeforeClass(): void {
        self::$api = new ZfExtended_Test_ApiHelper(__CLASS__);
        self::assertNeededUsers(); // last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertCustomer();
    }
    
    /**
     * Test a DeepL-LanguageResource
     */
    public function testDeepLLanguageResource() {
        $this->createLanguageResource();
    }

    /**
     * 
     */
    private function createLanguageResource() {
        $languageResourceParams = [];
        $languageResourceParams['name'] = 'DeepL_Test';
        $languageResourceParams['sourceLang'] = 4; // "de"
        $languageResourceParams['targetLang'] = 5; // "en"
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