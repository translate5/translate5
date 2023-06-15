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
 * Test microsoft translator api for dictonary,normal and segmentation search
 */
class MicrosoftTranslatorTest extends editor_Test_ImportTest {

    protected static LanguageResource $microsoftTranslator;

    protected static string $sourceLangRfc = 'de';

    protected static string $targetLangRfc = 'en';

    protected static array $requiredPlugins = [
        'editor_Plugins_InstantTranslate_Init'
    ];

    protected static array $requiredRuntimeOptions = [
        'runtimeOptions.LanguageResources.microsoft.apiUrl' => null,//null checks for no concrete value but if not empty
        'runtimeOptions.LanguageResources.microsoft.apiKey' => null//null checks for no concrete value but if not empty
    ];

    protected static bool $skipIfOptionsMissing = true; // we skip the tests if the neccessary configs are not there ...

    protected static function setupImport(Config $config): void
    {
        if (!self::isMasterTest()) {
            self::markTestSkipped('Test runs only in master test to reduce usage/costs.');
        } else {
            static::$microsoftTranslator = $config
                ->addLanguageResource('microsofttranslator', null, static::getTestCustomerId(), static::$sourceLangRfc, static::$targetLangRfc)
                ->setProperty('name', static::NAME_PREFIX . static::class);
        }
    }

    public function testResource(){
        $response = static::api()->getJson('editor/languageresourceinstance/'.static::$microsoftTranslator->getId());
        static::assertEquals('available', $response->status, 'Tm import stoped. Tm state is:'.$response->status);
    }
    
    public function testSearch(){
        $this->simpleTextTranslation();
        $this->translateText('wagen','dictonary.txt');
    }

    /***
     * This will search for single text just to test if the translator returns any translation data. This will not
     * check if the translated data is correct since this can be different from time to time
     * @return void
     * @throws Zend_Http_Client_Exception
     */
    private function simpleTextTranslation(){
        $filtered = $this->translateRequest('Tür');

        $this->assertNotEmpty($filtered->target,'Empty translation.');
    }

    /***
     * Translate text and test the results
     * @param string $text
     * @param string $fileName
     */
    private function translateText(string $text, string $fileName){
        //filter only the microsoft results
        $filtered = $this->translateRequest($text);
        
        //this is to recreate the file from the api response
        if(static::api()->isCapturing()){
            file_put_contents(static::api()->getFile($fileName, null, false), json_encode($filtered, JSON_PRETTY_PRINT));
        }
        $expected = static::api()->getFileContent($fileName);
        $actual = json_encode($filtered, JSON_PRETTY_PRINT);
        //check for differences between the expected and the actual content
        $this->assertEquals($expected, $actual, "The expected file an the result file does not match.");
    }

    /**
     * @param string $text
     * @return stdClass
     * @throws Zend_Http_Client_Exception
     */
    private function translateRequest(string $text): stdClass
    {
        $result = static::api()->getJson('editor/instanttranslateapi/translate',[
            'text' => $text,
            'source' => self::$sourceLangRfc,
            'target' => self::$targetLangRfc
        ]);
        $this->assertNotEmpty($result,'No results found for the search request. Search was:'.$text);

        //filter only the microsoft results
        return $this->filterResult($result);
    }

    /***
     * Filter only the required fields for the test
     * @param mixed $result
     */
    private function filterResult($result){
        //filter only microsoft service results
        $serviceName = static::$microsoftTranslator->getServiceName();
        $resourceName = static::$microsoftTranslator->getName();
        if(isset($result->{$serviceName})){
            $result = $result->{$serviceName};
        }
        //for segmentation request only the best matches will be returned
        if(isset($result->translationForSegmentedText)){
            return $result;
        }
        //filter out by language resource name
        if(isset($result->{$resourceName})){
            $result = $result->{$resourceName};
        }
        $filtered = [];
        //remove the result parametars which should not be tested
        foreach ($result as $r) {
            if(isset($r->languageResourceid)){
                unset($r->languageResourceid);
            }
            $filtered = $r;
        }
        return $filtered;
    }
}