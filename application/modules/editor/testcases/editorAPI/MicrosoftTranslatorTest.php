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
 * Test microsoft translator api for dictionary,normal and segmentation search
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

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws ZfExtended_Exception
     */
    protected static function setupImport(Config $config): void
    {
        if (!self::isMasterTest()) {
            self::markTestSkipped('Test runs only in master test to reduce usage/costs.');
        } else {
            static::$microsoftTranslator = $config
                ->addLanguageResource(
                    'microsofttranslator',
                    null,
                    static::getTestCustomerId(),
                    static::$sourceLangRfc,
                    static::$targetLangRfc
                )->setProperty('name', static::NAME_PREFIX . static::class);
        }
    }

    /**
     * @throws \MittagQI\Translate5\Test\Import\Exception
     * @throws Zend_Http_Client_Exception
     */
    public function testResource(){
        $response = static::api()->getJson('editor/languageresourceinstance/'.static::$microsoftTranslator->getId());
        static::assertEquals('available', $response->status, 'Tm import stoped. Tm state is:'.$response->status);
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testSearch(){
        $this->simpleTextTranslation();
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    public function testDictionary(){
        $this->simpleDictionaryTranslation();
    }

    /**
     * This will search for single text just to test if the translator returns any translation data. This will not
     * check if the translated data is correct since this can be different from time to time
     * @return void
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function simpleTextTranslation(): void
    {
        $filtered = $this->translateRequest('Tür');

        $this->assertNotEmpty($filtered->target,'Empty translation.');
    }

    /**
     * This will search for text with dictionary search just to test if the translator returns any translation data.
     * This will not validate the content of the result because this can be different from time to time
     * @throws Zend_Http_Client_Exception
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function simpleDictionaryTranslation(): void
    {
        $filtered = $this->translateRequest('wagen');
        $this->assertNotEmpty($filtered->target,'Empty dictionary translation.');
        $this->assertNotEmpty($filtered->metaData,'Empty dictionary metaData translation.');
        $this->assertNotEmpty(
            $filtered->metaData->alternativeTranslations,
            'Empty dictionary metaData->alternativeTranslations translation.'
        );
    }

    /**
     * @param string $text
     * @return stdClass
     * @throws Zend_Http_Client_Exception|\MittagQI\Translate5\Test\Import\Exception
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

    /**
     * Filter only the required fields for the test
     * @param mixed $result
     * @return array|mixed
     * @throws \MittagQI\Translate5\Test\Import\Exception
     */
    private function filterResult(mixed $result): mixed
    {
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
