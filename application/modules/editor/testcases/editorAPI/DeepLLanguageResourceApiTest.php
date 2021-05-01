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
 * - create a DeepL-LanguageReource and a task that we associate it with,
 * - run a query (= for matches) and a translation (= for InstantTranslation),
 * - deleted the LangaugeResource and the task.
 *
 * Testing what users can do with LanguageResources in addition is NOT part of this test.
 */
class DeepLLanguageResourceApiTest extends editor_Test_Segment {
    
    /**
     * ServiceType according the Service's namespace.
     * @var string
     */
    const SERVICE_TYPE = 'editor_Plugins_DeepL';
    
    /**
     * ServiceName according the Service.
     * @var string
     */
    const SERVICE_NAME = 'DeepL';
    
    /**
     * According to addResourceForeachUrl() in editor_Services_ServiceAbstract.
     * @var string
     */
    const RESOURCE_ID = 'editor_Plugins_DeepL_1';
    
    /**
     * Name of the LanguageResource that we create (any name will do).
     * @var string
     */
    const LANGUAGERESOURCE_NAME = 'API Testing (Test DeepL de-en)';
    
    /**
     * Id of the created LanguageResource.
     * @var int
     */
    protected static $languageResourceID;
    
    /**
     * "Settings" for translations.
     */
    const SOURCE_LANG = 'de';
    const SOURCE_LANG_CODE = 4;
    const TARGET_LANG = 'en';
    const TARGET_LANG_CODE = 5;
    /**
     *
     */
    public static function setUpBeforeClass(): void {
        self::$api = $api = new ZfExtended_Test_ApiHelper(__CLASS__);
        
        $task = array(
            'taskName' => 'API Testing::'.__CLASS__,
            'sourceLang' => self::SOURCE_LANG,
            'targetLang' => self::TARGET_LANG
        );
        $appState = self::assertAppState();
        if(!in_array('editor_Plugins_DeepL_Init', $appState->pluginsLoaded)) {
            self::markTestSkipped('DeepL-Plugin must be activated for this test case, which is not the case!');
            return;
        }
        
        self::assertContains('editor_Plugins_InstantTranslate_Init', $appState->pluginsLoaded, 'Plugin InstantTranslate must be activated for this test case!');
        
        $tests = array(
            'runtimeOptions.plugins.DeepL.server' => null, //null checks for no concrete value but if not empty
            'runtimeOptions.plugins.DeepL.authkey' => null, //null checks for no concrete value but if not empty
        );
        self::$api->testConfig($tests);
        
        self::assertNeededUsers(); //last authed user is testmanager
        self::assertLogin('testmanager');
        self::assertCustomer();
        
        $api->addImportFile($api->getFile('testcase-de-en.xlf'));
        $api->import($task);
    }
    
    /**
     * Matches (= see file for task-import): what we expect as result.
     * @var array
     */
    protected $expectedTranslations = [
        [
            'source' => '<div class="open 672069643d22393222 internal-tag ownttip"><span title="&lt;g id=&quot;92&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;g id=&quot;92&quot;&gt;</span></div>Datum:<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;/g&gt;</span></div> PHP Handbuch',
            'translation' => '<div class="open 672069643d22393222 internal-tag ownttip"><span title="&lt;g id=&quot;92&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;g id=&quot;92&quot;&gt;</span></div>Date:<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="92" data-length="-1" class="full">&lt;/g&gt;</span></div> PHP Manual'
        ],[
            'source' => 'Das Haus ist <div class="open 672069643d22393322 internal-tag ownttip"><span title="&lt;g id=&quot;93&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="93" data-length="-1" class="full">&lt;g id=&quot;93&quot;&gt;</span></div>blau<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="93" data-length="-1" class="full">&lt;/g&gt;</span></div>.',
            'translation' => 'The house is <div class="open 672069643d22393322 internal-tag ownttip"><span title="&lt;g id=&quot;93&quot;&gt;" class="short">&lt;1&gt;</span><span data-originalid="93" data-length="-1" class="full">&lt;g id=&quot;93&quot;&gt;</span></div>blue<div class="close 2f67 internal-tag ownttip"><span title="&lt;/g&gt;" class="short">&lt;/1&gt;</span><span data-originalid="93" data-length="-1" class="full">&lt;/g&gt;</span></div>.'
        ]
    ];
    /**
     * InstantTranslate: Translations to check and what we expect as result.
     * @var array
     */
    protected $expectedInstantTranslations = [
        [
            'source' => '[<i>Datum</i>] PHP Handbuch',
            'translation' => '[<i>date</i>] PHP manual'
        ],[
            'source' => 'Das Haus ist <b>blau</b>',
            'translation' => 'The house is <b>blue</b>'
        ]
    ];
    
    /**
     * 
     * @param string $source
     * @param array $data
     * @param string $msg
     */
    public function assertSourceExists($source, $data, $msg){
        foreach($data as $item){
            if($this->_adjustFieldText($item['source']) == $this->_adjustFieldText($source)){
                $this->assertTrue(true, $msg);
                return;
            }
        }
        $this->assertTrue(false, $msg);
    }
    /**
     * 
     * @param string $source
     * @param string $translation
     * @param array $data
     * @param string $msg
     */
    public function assertTranslationExists($source, $translation, $data, $msg){
        foreach($data as $item){
            if($this->_adjustFieldText($item['source']) == $this->_adjustFieldText($source)){
                $this->assertEquals($this->_adjustFieldText($item['translation']), $this->_adjustFieldText($translation), $msg);
                return;
            }
        }
        $this->assertEquals('???', $translation, $msg);
    }    
    /**
     * Create a DeepL-LanguageResource with association to the test-customer
     * and store its ID.
     */
    public function testCreateLanguageResource() {
        $customer = self::api()->getCustomer();
        
        $params = [];
        $params['resourceId']  = static::RESOURCE_ID;
        $params['name'] = static::LANGUAGERESOURCE_NAME;
        $params['sourceLang'] = static::SOURCE_LANG_CODE;
        $params['targetLang'] = static::TARGET_LANG_CODE;
        $params['serviceType'] = static::SERVICE_TYPE;
        $params['serviceName'] = static::SERVICE_NAME;
        $params['customerIds'] = [$customer->id];
        $params['customerUseAsDefaultIds'] = [];
        
        $this->api()->requestJson('editor/languageresourceinstance', 'POST', [], $params);
        $responseBody = json_decode($this->api()->getLastResponse()->getBody());
        $this->assertObjectHasAttribute('rows', $responseBody, 'Creating a DeepL-LanguageResource failed. Check configured runtimeOptions for DeepL.');
        self::$languageResourceID = $responseBody->rows->id;
    }
    
    /**
     * Matches:
     * Run a query-search with our DeepL-LanguageResource
     * and check if the result is as expected.
     * @depends testCreateLanguageResource
     */
    public function testQuery() {
        $task = $this->api()->getTask();
        
        // associate languageresource to task
        $params = [];
        $params['languageResourceId'] = self::$languageResourceID;
        $params['taskGuid'] = $task->taskGuid;
        $params['segmentsUpdateable'] = 0;
        $this->api()->requestJson('editor/languageresourcetaskassoc', 'POST', $params);
        
        // open task
        $params = [];
        $params['userState'] = 'edit';
        $params['id'] = $task->id;
        $this->api()->requestJson('editor/task/'.$task->id, 'PUT', $params);
        
        // get segment list
        $this->api()->requestJson('editor/segment?page=1&start=0&limit=200');
        $allSegments = json_decode($this->api()->getLastResponse()->getBody());
        
        foreach ($allSegments->rows as $segment){
            // Do we provide an expected translation at all?
            $this->assertSourceExists($segment->source, $this->expectedTranslations, 'Provide an expected translation for: '.$segment->source);
            // Does the result match our expectations?
            $params = [];
            $params['segmentId'] = $segment->id;
            $params['query'] = $segment->source;
            $this->api()->requestJson('editor/languageresourceinstance/'.self::$languageResourceID.'/query', 'POST', [], $params);
            $responseBody = json_decode($this->api()->getLastResponse()->getBody());
            $translation = $responseBody->rows[0]->target;
            
            $this->assertTranslationExists($segment->source, $translation, $this->expectedTranslations, 'Result of translation is not as expected! Source was:'."\n".$segment->source);
        }
    }
    
    /**
     * InstantTranslate:
     * Run a translation with our DeepL-LanguageResource
     * and check if the result is as expected.
     * @depends testCreateLanguageResource
     */
    public function testTranslation() {
        foreach ($this->expectedInstantTranslations as $item){
            
            $text = $item['source'];
            $expectedTranslation = $item['translation'];
            
            $params = [];
            $params['source']  = static::SOURCE_LANG;
            $params['target'] = static::TARGET_LANG;
            $params['text'] = $text;
            $this->api()->requestJson('editor/instanttranslateapi/translate', 'GET', $params); // (according to Confluence: GET / according to InstantTranslate in Browser: POST)
            $responseBody = json_decode($this->api()->getLastResponse()->getBody());
            // Is anything returned for Deep at all?
            $this->assertIsObject($responseBody, 'InstantTranslate: Response for translation does not return an object, check error log.');
            $this->assertIsObject($responseBody->rows, 'InstantTranslate: Response for translation does not return any rows.');
            $allTranslations = (array)$responseBody->rows;
            $this->assertIsArray($allTranslations);
            $this->assertArrayHasKey('DeepL', $allTranslations, 'InstantTranslate: Translations do not include a response for DeepL.');
            // Does the result match our expectations?
            foreach ($allTranslations['DeepL'] as $translationFromDeepL){
                $translation = htmlspecialchars_decode($translationFromDeepL[0]->target);
                $this->assertEquals($expectedTranslation, $translation, 'Result of translation is not as expected! Text was:'."\n".$text);
            }
        }
    }

    /**
     *
     */
    public static function tearDownAfterClass(): void {
        $task = self::$api->getTask();
        self::$api->login('testmanager');
        self::$api->requestJson('editor/task/'.$task->id, 'PUT', array('userState' => 'open', 'id' => $task->id));
        self::$api->requestJson('editor/task/'.$task->id, 'DELETE');
        self::$api->requestJson('editor/languageresourceinstance'.'/'.self::$languageResourceID, 'DELETE');
    }
}